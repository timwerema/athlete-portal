<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\ProfileTeam;
use App\Models\Video;
use App\Services\PhotoStorage;
use App\Services\ProfileCompleteness;
use App\Services\ProfilePresenter;
use App\Sports\Soccer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    /** Builder sections in the order the athlete walks through them. */
    public const SECTIONS = [
        'basics' => 'Name and photo',
        'playing' => 'Position, foot and height',
        'school' => 'Current school',
        'history' => 'Club and school history',
        'stats' => 'Season stats',
        'honours' => 'Honours',
        'academics' => 'Academics',
        'videos' => 'Video',
        'references' => 'References',
        'looking-for' => 'What I\'m looking for',
        'statement' => 'Personal statement',
    ];

    public function index(Request $request): Response
    {
        $profile = $request->user()->profileOrCreate();

        return Inertia::render('profile/index', [
            'sections' => self::SECTIONS,
            'completeness' => ProfileCompleteness::summary($profile),
            'displayName' => $profile->display_name,
        ]);
    }

    public function edit(Request $request, string $section): Response
    {
        abort_unless(isset(self::SECTIONS[$section]), 404);

        $profile = $request->user()->profileOrCreate();
        $sport = $profile->sportDefinition();
        $keys = array_keys(self::SECTIONS);
        $position = array_search($section, $keys, true);

        return Inertia::render('profile/edit', [
            'section' => $section,
            'sectionTitle' => self::SECTIONS[$section],
            'step' => $position + 1,
            'stepCount' => count($keys),
            'previous' => $keys[$position - 1] ?? null,
            'next' => $keys[$position + 1] ?? null,
            'profile' => ProfilePresenter::forOwner($profile),
            'options' => [
                'positions' => $sport->positions(),
                'feet' => Soccer::FEET,
                'statFields' => $sport->statFields(),
                'divisions' => Profile::DIVISIONS,
                'classYears' => Profile::CLASS_YEARS,
                'regions' => Profile::REGIONS,
                'teamKinds' => ProfileTeam::KINDS,
                'videoKinds' => Video::KINDS,
                'photoMaxKilobytes' => PhotoStorage::MAX_KILOBYTES,
            ],
        ]);
    }

    public function update(Request $request, string $section): RedirectResponse
    {
        $profile = $request->user()->profileOrCreate();
        $sport = $profile->sportDefinition();

        $data = match ($section) {
            'basics' => $request->validate([
                'display_name' => ['required', 'string', 'max:120'],
                'pronouns' => ['nullable', 'string', 'max:40'],
            ]),
            'playing' => [
                'sport_data' => $request->validate($sport->profileRules()),
                ...$request->validate(['height_cm' => ['nullable', 'integer', 'between:120,245']]),
            ],
            'school' => $request->validate([
                'current_school' => ['nullable', 'string', 'max:160'],
                'division' => ['nullable', Rule::in(Profile::DIVISIONS)],
                'conference' => ['nullable', 'string', 'max:120'],
                'class_year' => ['nullable', Rule::in(Profile::CLASS_YEARS)],
                'eligibility_years_left' => ['nullable', 'integer', 'between:0,5'],
                'portal_entered_on' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today', 'after:2018-01-01'],
            ]),
            'academics' => $request->validate([
                'major' => ['nullable', 'string', 'max:160'],
                'gpa_scale' => ['nullable', Rule::in(['4.0', '5.0'])],
                'gpa' => ['nullable', 'numeric', 'min:0', 'max:'.($request->input('gpa_scale') === '5.0' ? '5' : '4')],
            ]),
            'looking-for' => ['looking_for' => $request->validate([
                'levels' => ['nullable', 'array'],
                'levels.*' => ['string', 'distinct', Rule::in(Profile::DIVISIONS)],
                'regions' => ['nullable', 'array'],
                'regions.*' => ['string', 'distinct', Rule::in(Profile::REGIONS)],
                'intended_major' => ['nullable', 'string', 'max:160'],
                'timeline' => ['nullable', 'string', 'max:80'],
            ])],
            'statement' => $request->validate([
                'statement' => ['nullable', 'string', 'max:1500'],
            ]),
            default => abort(404),
        };

        if ($section === 'academics' && ($data['gpa'] ?? null) !== null) {
            $data['gpa_scale'] ??= '4.0';
        }

        $profile->update($data);

        return back()->with('status', 'Saved.');
    }

    public function storePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'photo' => ['required', 'file', 'max:'.PhotoStorage::MAX_KILOBYTES, 'mimetypes:image/jpeg,image/png,image/webp'],
        ], [
            'photo.max' => 'That photo is too big. Please use one under 6 MB.',
            'photo.mimetypes' => 'Please choose a JPEG, PNG or WebP image.',
        ]);

        PhotoStorage::replace($request->user()->profileOrCreate(), $request->file('photo'));

        return back()->with('status', 'Photo saved.');
    }

    public function destroyPhoto(Request $request): RedirectResponse
    {
        PhotoStorage::remove($request->user()->profileOrCreate());

        return back()->with('status', 'Photo removed.');
    }

    public function photo(Request $request): StreamedResponse
    {
        return self::servePhoto($request->user()->profileOrCreate());
    }

    public static function servePhoto(Profile $profile): StreamedResponse
    {
        abort_unless($profile->photo_path && Storage::disk('local')->exists($profile->photo_path), 404);

        return Storage::disk('local')->response($profile->photo_path, 'photo.jpg', [
            'Content-Type' => 'image/jpeg',
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
