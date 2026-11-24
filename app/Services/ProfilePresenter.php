<?php

namespace App\Services;

use App\Models\Profile;
use App\Sports\Soccer;

/**
 * Builds the data sent to the browser. The coach view is assembled field by
 * field so that nothing reaches the shared page unless it is listed here and
 * the athlete has left that section switched on. The account email is never
 * included.
 */
class ProfilePresenter
{
    /** Everything, for the athlete editing their own profile. */
    public static function forOwner(Profile $profile): array
    {
        $profile->loadMissing(['teams', 'seasonStats', 'honours', 'videos', 'references']);

        return [
            'display_name' => $profile->display_name,
            'pronouns' => $profile->pronouns,
            'has_photo' => filled($profile->photo_path),
            'photo_version' => $profile->photo_path ? substr(md5($profile->photo_path), 0, 8) : null,
            'height_cm' => $profile->height_cm,
            'current_school' => $profile->current_school,
            'division' => $profile->division,
            'conference' => $profile->conference,
            'class_year' => $profile->class_year,
            'eligibility_years_left' => $profile->eligibility_years_left,
            'portal_entered_on' => $profile->portal_entered_on?->format('Y-m-d'),
            'sport' => $profile->sport,
            'sport_data' => ['positions' => [], 'preferred_foot' => null, ...($profile->sport_data ?? [])],
            'major' => $profile->major,
            'gpa' => $profile->gpa,
            'gpa_scale' => $profile->gpa_scale,
            'looking_for' => ['levels' => [], 'regions' => [], 'intended_major' => null, 'timeline' => null, ...($profile->looking_for ?? [])],
            'statement' => $profile->statement,
            'hidden_sections' => array_values($profile->hidden_sections ?? []),
            'teams' => $profile->teams->map->only(['id', 'kind', 'name', 'detail', 'start_year', 'end_year'])->values(),
            'season_stats' => $profile->seasonStats->map->only(['id', 'season', 'team', 'competition', 'stats'])->values(),
            'honours' => $profile->honours->map->only(['id', 'title', 'year'])->values(),
            'videos' => $profile->videos->map(fn ($v) => [...$v->only(['id', 'kind', 'title', 'url']), ...VideoEmbed::resolve($v->url)])->values(),
            'references' => $profile->references->map->only(['id', 'name', 'role', 'organization', 'contact'])->values(),
        ];
    }

    /** Only what a coach with the link may see. */
    public static function forCoach(Profile $profile, ?string $photoUrl): array
    {
        $profile->loadMissing(['teams', 'seasonStats', 'honours', 'videos', 'references']);

        $sport = $profile->sportDefinition();
        $data = $profile->sport_data ?? [];
        $positions = $sport->positions();
        $show = fn (string $section) => $profile->shows($section);

        $fields = array_values(array_filter(
            $sport->statFields(),
            fn ($f) => $sport->usesGroup($data, $f['group'])
        ));

        return [
            'is_demo' => $profile->is_demo,
            'sport' => $sport->label(),
            'display_name' => $profile->display_name,
            'pronouns' => $show('pronouns') ? $profile->pronouns : null,
            'photo_url' => $show('photo') && $profile->photo_path ? $photoUrl : null,
            'positions' => array_values(array_map(
                fn ($code) => ['code' => $code, 'label' => $positions[$code] ?? $code],
                $data['positions'] ?? []
            )),
            'preferred_foot' => isset($data['preferred_foot']) ? (Soccer::FEET[$data['preferred_foot']] ?? null) : null,
            'height_cm' => $show('height') ? $profile->height_cm : null,
            'current_school' => $show('school') ? $profile->current_school : null,
            'division' => $show('school') ? $profile->division : null,
            'conference' => $show('school') ? $profile->conference : null,
            'class_year' => $show('class_year') ? $profile->class_year : null,
            'eligibility_years_left' => $show('eligibility') ? $profile->eligibility_years_left : null,
            'portal_entered_on' => $show('portal_date') ? $profile->portal_entered_on?->format('Y-m-d') : null,
            'major' => $show('major') ? $profile->major : null,
            'gpa' => $show('gpa') && $profile->gpa !== null
                ? ['value' => $profile->gpa, 'scale' => $profile->gpa_scale]
                : null,
            'statement' => $show('statement') ? $profile->statement : null,
            'looking_for' => $show('looking_for') ? self::lookingFor($profile->looking_for ?? []) : null,
            'teams' => $show('history')
                ? $profile->teams->map->only(['kind', 'name', 'detail', 'start_year', 'end_year'])->values()->all()
                : [],
            'stat_fields' => array_map(fn ($f) => ['key' => $f['key'], 'label' => $f['label'], 'short' => $f['short']], $fields),
            'season_stats' => $show('stats')
                ? $profile->seasonStats->map->only(['season', 'team', 'competition', 'stats'])->values()->all()
                : [],
            'honours' => $show('honours') ? $profile->honours->map->only(['title', 'year'])->values()->all() : [],
            'videos' => $show('videos')
                ? $profile->videos->map(fn ($v) => [...$v->only(['kind', 'title', 'url']), ...VideoEmbed::resolve($v->url)])->values()->all()
                : [],
            'references' => $show('references')
                ? $profile->references->map->only(['name', 'role', 'organization', 'contact'])->values()->all()
                : [],
            'updated_at' => $profile->updated_at?->format('Y-m-d'),
        ];
    }

    private static function lookingFor(array $looking): ?array
    {
        $clean = [
            'levels' => array_values($looking['levels'] ?? []),
            'regions' => array_values($looking['regions'] ?? []),
            'intended_major' => $looking['intended_major'] ?? null,
            'timeline' => $looking['timeline'] ?? null,
        ];

        return array_filter($clean) === [] ? null : $clean;
    }
}
