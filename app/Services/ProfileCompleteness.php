<?php

namespace App\Services;

use App\Models\Profile;

class ProfileCompleteness
{
    /**
     * @return list<array{key: string, label: string, section: string, done: bool, optional: bool}>
     */
    public static function items(Profile $profile): array
    {
        $data = $profile->sport_data ?? [];
        $looking = $profile->looking_for ?? [];

        $items = [
            ['basics', 'Add your name', 'basics', filled($profile->display_name), false],
            ['photo', 'Add a photo', 'basics', filled($profile->photo_path), true],
            ['positions', 'Choose your positions', 'playing', ! empty($data['positions']), false],
            ['foot', 'Add preferred foot and height', 'playing', filled($data['preferred_foot'] ?? null) && filled($profile->height_cm), true],
            ['school', 'Add your current school', 'school', filled($profile->current_school) && filled($profile->division), false],
            ['class_year', 'Add class year', 'school', filled($profile->class_year), false],
            ['history', 'Add club or school history', 'history', $profile->teams()->exists(), false],
            ['stats', 'Add at least one season of stats', 'stats', $profile->seasonStats()->exists(), false],
            ['video', 'Add a highlight video link', 'videos', $profile->videos()->exists(), false],
            ['major', 'Add your major', 'academics', filled($profile->major), true],
            ['looking_for', 'Say what you are looking for', 'looking-for', ! empty($looking['levels']) || filled($looking['timeline'] ?? null), false],
            ['statement', 'Write a short personal statement', 'statement', filled($profile->statement), false],
            ['honours', 'Add honours', 'honours', $profile->honours()->exists(), true],
            ['references', 'Add a reference', 'references', $profile->references()->exists(), true],
        ];

        return array_map(fn ($i) => [
            'key' => $i[0], 'label' => $i[1], 'section' => $i[2], 'done' => (bool) $i[3], 'optional' => $i[4],
        ], $items);
    }

    /** @return array{done: int, total: int, items: list<array<string, mixed>>} */
    public static function summary(Profile $profile): array
    {
        $items = self::items($profile);

        return [
            'done' => count(array_filter($items, fn ($i) => $i['done'])),
            'total' => count($items),
            'items' => $items,
        ];
    }
}
