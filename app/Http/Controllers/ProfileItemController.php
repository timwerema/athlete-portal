<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\ProfileTeam;
use App\Models\Video;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Add, change and remove the list parts of a profile (history, stats, honours,
 * video links, references). Every lookup goes through the signed-in athlete's
 * own profile, so an ID belonging to someone else is simply "not found".
 */
class ProfileItemController extends Controller
{
    public const MAX_ITEMS = 30;

    public const TYPES = ['teams', 'stats', 'honours', 'videos', 'references'];

    public function store(Request $request, string $type): RedirectResponse
    {
        $profile = $request->user()->profileOrCreate();
        $relation = $this->relation($profile, $type);

        if ($relation->count() >= self::MAX_ITEMS) {
            throw ValidationException::withMessages(['limit' => 'You have reached the limit of '.self::MAX_ITEMS.' entries here.']);
        }

        $relation->create($this->validated($request, $profile, $type));

        return back()->with('status', 'Added.');
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $profile = $request->user()->profileOrCreate();
        $item = $this->relation($profile, $type)->findOrFail($id);

        $data = $this->validated($request, $profile, $type);
        unset($data['consent_confirmed_at']);
        $item->update($data);

        return back()->with('status', 'Saved.');
    }

    public function destroy(Request $request, string $type, int $id): RedirectResponse
    {
        $profile = $request->user()->profileOrCreate();
        $this->relation($profile, $type)->findOrFail($id)->delete();

        return back()->with('status', 'Removed.');
    }

    private function relation(Profile $profile, string $type): HasMany
    {
        return match ($type) {
            'teams' => $profile->teams(),
            'stats' => $profile->seasonStats(),
            'honours' => $profile->honours(),
            'videos' => $profile->videos(),
            'references' => $profile->references(),
            default => abort(404),
        };
    }

    private function validated(Request $request, Profile $profile, string $type): array
    {
        $thisYear = (int) now()->format('Y');
        $year = ['nullable', 'integer', 'between:1990,'.($thisYear + 1)];

        switch ($type) {
            case 'teams':
                return $request->validate([
                    'kind' => ['required', Rule::in(array_keys(ProfileTeam::KINDS))],
                    'name' => ['required', 'string', 'max:160'],
                    'detail' => ['nullable', 'string', 'max:160'],
                    'start_year' => $year,
                    'end_year' => [...$year, 'gte:start_year'],
                ], ['end_year.gte' => 'The end year cannot be before the start year.']);

            case 'stats':
                $rules = [
                    'season' => ['required', 'string', 'regex:/\A(19|20)\d{2}(-\d{2})?\z/'],
                    'team' => ['required', 'string', 'max:160'],
                    'competition' => ['nullable', 'string', 'max:120'],
                    'stats' => ['nullable', 'array'],
                ];
                foreach ($profile->sportDefinition()->statFields() as $field) {
                    $rules['stats.'.$field['key']] = $field['type'] === 'int'
                        ? ['nullable', 'integer', 'min:0', 'max:'.$field['max']]
                        : ['nullable', 'numeric', 'min:0', 'max:'.$field['max'], 'decimal:0,2'];
                }
                $data = $request->validate($rules, ['season.regex' => 'Write the season as 2025 or 2025-26.']);
                $stats = [];
                foreach ($profile->sportDefinition()->statFields() as $field) {
                    $value = $data['stats'][$field['key']] ?? null;
                    if ($value !== null && $value !== '') {
                        $stats[$field['key']] = $field['type'] === 'int' ? (int) $value : round((float) $value, 2);
                    }
                }
                $data['stats'] = $stats;

                return $data;

            case 'honours':
                return $request->validate([
                    'title' => ['required', 'string', 'max:200'],
                    'year' => $year,
                ]);

            case 'videos':
                return $request->validate([
                    'kind' => ['required', Rule::in(array_keys(Video::KINDS))],
                    'title' => ['required', 'string', 'max:160'],
                    'url' => ['required', 'string', 'max:500', 'url:https'],
                ], ['url.url' => 'Please paste a full link that starts with https://']);

            case 'references':
                $data = $request->validate([
                    'name' => ['required', 'string', 'max:120'],
                    'role' => ['required', 'string', 'max:120'],
                    'organization' => ['nullable', 'string', 'max:160'],
                    'contact' => ['required', 'string', 'max:200'],
                    'consent' => ['accepted'],
                ], ['consent.accepted' => 'Please only add someone who has agreed to be listed as your reference.']);
                unset($data['consent']);
                $data['consent_confirmed_at'] = now();

                return $data;
        }

        abort(404);
    }
}
