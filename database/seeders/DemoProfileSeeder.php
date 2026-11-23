<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the one sample profile shown at /demo. The athlete, schools, clubs
 * and numbers are all invented and the page says so. Nobody can sign in to
 * this account: its password is random and is not kept anywhere.
 */
class DemoProfileSeeder extends Seeder
{
    public const EMAIL = 'demo-profile@example.invalid';

    public function run(): void
    {
        $user = User::query()->firstOrNew(['email' => self::EMAIL]);
        $user->forceFill([
            'name' => 'Jordan Avery (demo)',
            'password' => Hash::make(Str::random(64)),
            'email_verified_at' => now(),
            'age_confirmed_at' => now(),
        ])->save();

        $profile = $user->profile()->firstOrNew([]);
        $profile->is_demo = true;
        $profile->fill([
            'display_name' => 'Jordan Avery',
            'pronouns' => 'she/her',
            'height_cm' => 170,
            'current_school' => 'Calder Valley University (fictional)',
            'division' => 'NCAA Division II',
            'conference' => 'Example Conference',
            'class_year' => 'Sophomore',
            'eligibility_years_left' => 2,
            'sport_data' => ['positions' => ['CM', 'CDM'], 'preferred_foot' => 'left'],
            'major' => 'Kinesiology',
            'gpa' => 3.6,
            'gpa_scale' => '4.0',
            'looking_for' => [
                'levels' => ['NCAA Division I', 'NCAA Division II'],
                'regions' => ['Southwest', 'Southeast'],
                'intended_major' => 'Kinesiology or Exercise Science',
                'timeline' => 'Ready to enrol for the fall term',
            ],
            'statement' => "I am a left-footed central midfielder who likes to set the tempo and win the ball back early. Two seasons of regular minutes have taught me how to manage a game, and I want a programme where I can keep growing on the field and finish a Kinesiology degree.\n\nThis is sample text for a fictional athlete.",
            'hidden_sections' => ['references', 'portal_date'],
        ])->save();

        $profile->teams()->delete();
        $profile->teams()->createMany([
            ['kind' => 'college', 'name' => 'Calder Valley University (fictional)', 'detail' => 'NCAA Division II', 'start_year' => 2025, 'end_year' => null],
            ['kind' => 'club', 'name' => 'Harbour City FC (fictional)', 'detail' => 'Under-19 regional league', 'start_year' => 2021, 'end_year' => 2025],
            ['kind' => 'high_school', 'name' => 'Eastfield High School (fictional)', 'detail' => 'Varsity, captain in final year', 'start_year' => 2021, 'end_year' => 2025],
        ]);

        $profile->seasonStats()->delete();
        $profile->seasonStats()->createMany([
            ['season' => '2026', 'team' => 'Calder Valley University', 'competition' => 'Conference and non-conference', 'stats' => ['appearances' => 18, 'starts' => 17, 'minutes' => 1462, 'goals' => 4, 'assists' => 7]],
            ['season' => '2025', 'team' => 'Calder Valley University', 'competition' => 'Conference and non-conference', 'stats' => ['appearances' => 16, 'starts' => 9, 'minutes' => 871, 'goals' => 1, 'assists' => 3]],
        ]);

        $profile->honours()->delete();
        $profile->honours()->createMany([
            ['title' => 'All-Conference Second Team (sample)', 'year' => 2026],
            ['title' => 'Conference Academic Honour Roll (sample)', 'year' => 2026],
            ['title' => 'Team Newcomer of the Year (sample)', 'year' => 2025],
        ]);
    }
}
