<?php

namespace Tests\Feature;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileItemController;
use App\Models\User;
use App\Services\ProfileCompleteness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;

class ProfileBuilderTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    public function test_a_profile_is_created_on_first_visit_with_private_defaults(): void
    {
        $user = User::factory()->create(['name' => 'Fresh Athlete']);

        $this->actingAs($user)->get('/dashboard')->assertOk();

        $profile = $user->fresh()->profile;
        $this->assertSame('Fresh Athlete', $profile->display_name);
        $this->assertSame('soccer', $profile->sport);
        $this->assertEqualsCanonicalizing(['gpa', 'references', 'portal_date'], $profile->hidden_sections);
        $this->assertSame(0, $profile->shareLinks()->count());

        $this->get('/dashboard');
        $this->assertSame(1, $user->profile()->count());
    }

    public function test_every_section_page_renders_and_unknown_sections_are_not_found(): void
    {
        $this->actingAs($this->athlete());

        $this->get('/profile')->assertOk();

        foreach (array_keys(ProfileController::SECTIONS) as $section) {
            $this->get("/profile/{$section}")->assertOk();
        }

        $this->get('/profile/nonsense')->assertNotFound();
        $this->put('/profile/nonsense', ['x' => 1])->assertNotFound();
        $this->put('/profile/history', ['x' => 1])->assertNotFound();
    }

    public function test_basics_playing_school_academics_looking_for_and_statement_save(): void
    {
        $user = $this->athlete();
        $this->actingAs($user);

        $this->put('/profile/basics', ['display_name' => 'Sam Player', 'pronouns' => 'they/them'])->assertSessionHasNoErrors();
        $this->put('/profile/playing', ['positions' => ['GK', 'CB'], 'preferred_foot' => 'both', 'height_cm' => 188])->assertSessionHasNoErrors();
        $this->put('/profile/school', [
            'current_school' => 'Some College', 'division' => 'NCAA Division III', 'conference' => 'A Conference',
            'class_year' => 'Senior', 'eligibility_years_left' => 1, 'portal_entered_on' => now()->subDay()->format('Y-m-d'),
        ])->assertSessionHasNoErrors();
        $this->put('/profile/academics', ['major' => 'Biology', 'gpa' => '3.45', 'gpa_scale' => '4.0'])->assertSessionHasNoErrors();
        $this->put('/profile/looking-for', [
            'levels' => ['NAIA', 'NCAA Division II'], 'regions' => ['Midwest'], 'intended_major' => 'Biology', 'timeline' => 'Next fall',
        ])->assertSessionHasNoErrors();
        $this->put('/profile/statement', ['statement' => 'I work hard.'])->assertSessionHasNoErrors();

        $p = $user->profile->fresh();
        $this->assertSame('Sam Player', $p->display_name);
        $this->assertSame('they/them', $p->pronouns);
        $this->assertEquals(['positions' => ['GK', 'CB'], 'preferred_foot' => 'both'], $p->sport_data);
        $this->assertSame(188, $p->height_cm);
        $this->assertSame('NCAA Division III', $p->division);
        $this->assertSame('Senior', $p->class_year);
        $this->assertSame(1, $p->eligibility_years_left);
        $this->assertSame(now()->subDay()->format('Y-m-d'), $p->portal_entered_on->format('Y-m-d'));
        $this->assertSame('3.45', $p->gpa);
        $this->assertSame('4.0', $p->gpa_scale);
        $this->assertSame(['NAIA', 'NCAA Division II'], $p->looking_for['levels']);
        $this->assertSame('Next fall', $p->looking_for['timeline']);
        $this->assertSame('I work hard.', $p->statement);
    }

    public function test_section_validation_refuses_bad_input(): void
    {
        $user = $this->athlete(['display_name' => 'Keep Me']);
        $this->actingAs($user);

        $cases = [
            ['basics', ['display_name' => ''], 'display_name'],
            ['basics', ['display_name' => str_repeat('a', 121)], 'display_name'],
            ['playing', ['positions' => ['GK', 'CB', 'LB', 'RB']], 'positions'],
            ['playing', ['positions' => ['QB']], 'positions.0'],
            ['playing', ['positions' => ['CM', 'CM']], 'positions.0'],
            ['playing', ['preferred_foot' => 'third'], 'preferred_foot'],
            ['playing', ['height_cm' => 119], 'height_cm'],
            ['playing', ['height_cm' => 246], 'height_cm'],
            ['playing', ['height_cm' => 'tall'], 'height_cm'],
            ['school', ['division' => 'Premier League'], 'division'],
            ['school', ['class_year' => 'Year 9'], 'class_year'],
            ['school', ['eligibility_years_left' => 6], 'eligibility_years_left'],
            ['school', ['eligibility_years_left' => -1], 'eligibility_years_left'],
            ['school', ['portal_entered_on' => now()->addDay()->format('Y-m-d')], 'portal_entered_on'],
            ['academics', ['gpa' => '4.2', 'gpa_scale' => '4.0'], 'gpa'],
            ['academics', ['gpa' => '4.2'], 'gpa'],
            ['academics', ['gpa' => '5.1', 'gpa_scale' => '5.0'], 'gpa'],
            ['academics', ['gpa' => '-1', 'gpa_scale' => '4.0'], 'gpa'],
            ['academics', ['gpa' => '3.0', 'gpa_scale' => '10'], 'gpa_scale'],
            ['looking-for', ['levels' => ['Pro']], 'levels.0'],
            ['looking-for', ['regions' => ['Mars']], 'regions.0'],
            ['looking-for', ['timeline' => str_repeat('a', 81)], 'timeline'],
            ['statement', ['statement' => str_repeat('a', 1501)], 'statement'],
        ];

        foreach ($cases as [$section, $payload, $error]) {
            $this->put("/profile/{$section}", $payload)->assertSessionHasErrors($error);
        }

        $this->assertSame('Keep Me', $user->profile->fresh()->display_name);
        $this->assertNull($user->profile->fresh()->gpa);
    }

    public function test_gpa_on_a_five_point_scale_is_allowed(): void
    {
        $user = $this->athlete();

        $this->actingAs($user)->put('/profile/academics', ['gpa' => '4.6', 'gpa_scale' => '5.0'])->assertSessionHasNoErrors();

        $this->assertSame('4.60', $user->profile->fresh()->gpa);
    }

    public function test_list_entries_can_be_added_changed_and_removed(): void
    {
        $user = $this->athlete();
        $this->actingAs($user);
        $profile = $user->profile;

        $this->post('/profile/items/teams', ['kind' => 'college', 'name' => 'State U', 'detail' => 'D2', 'start_year' => 2024, 'end_year' => null])->assertSessionHasNoErrors();
        $team = $profile->teams()->firstOrFail();
        $this->put("/profile/items/teams/{$team->id}", ['kind' => 'club', 'name' => 'City FC', 'start_year' => 2020, 'end_year' => 2024])->assertSessionHasNoErrors();
        $this->assertSame('City FC', $team->fresh()->name);
        $this->assertSame('club', $team->fresh()->kind);

        $this->post('/profile/items/honours', ['title' => 'Captain', 'year' => 2025])->assertSessionHasNoErrors();
        $this->post('/profile/items/videos', ['kind' => 'highlight', 'title' => 'Reel', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'])->assertSessionHasNoErrors();
        $this->post('/profile/items/references', ['name' => 'Coach Lee', 'role' => 'Club coach', 'organization' => 'City FC', 'contact' => 'lee@example.com', 'consent' => true])->assertSessionHasNoErrors();

        $this->assertSame(1, $profile->honours()->count());
        $this->assertSame(1, $profile->videos()->count());

        $reference = $profile->references()->firstOrFail();
        $this->assertNotNull($reference->consent_confirmed_at);
        $this->assertSame('lee@example.com', $reference->contact);
        $this->assertStringNotContainsString('lee@example.com', \DB::table('profile_references')->value('contact'));

        $confirmed = $reference->consent_confirmed_at;
        $this->travel(1)->days();
        $this->put("/profile/items/references/{$reference->id}", ['name' => 'Coach Lee', 'role' => 'Head coach', 'contact' => 'lee@example.com', 'consent' => true])->assertSessionHasNoErrors();
        $this->assertSame('Head coach', $reference->fresh()->role);
        $this->assertTrue($confirmed->equalTo($reference->fresh()->consent_confirmed_at));

        $this->delete("/profile/items/teams/{$team->id}")->assertSessionHasNoErrors();
        $this->assertNull($team->fresh());
    }

    public function test_season_stats_keep_only_known_fields_as_numbers(): void
    {
        $user = $this->athlete(['sport_data' => ['positions' => ['GK']]]);
        $this->actingAs($user);

        $this->post('/profile/items/stats', [
            'season' => '2025-26', 'team' => 'State U', 'competition' => 'Conference',
            'stats' => [
                'appearances' => '18', 'starts' => 17, 'minutes' => 1530, 'goals' => 0, 'assists' => '',
                'saves' => 71, 'clean_sheets' => 6, 'goals_against_average' => '0.94',
                'made_up_stat' => 999,
            ],
        ])->assertSessionHasNoErrors();

        $this->assertSame([
            'appearances' => 18, 'starts' => 17, 'minutes' => 1530, 'goals' => 0,
            'saves' => 71, 'clean_sheets' => 6, 'goals_against_average' => 0.94,
        ], $user->profile->seasonStats()->firstOrFail()->stats);
    }

    public function test_list_validation_refuses_bad_input(): void
    {
        $user = $this->athlete();
        $this->actingAs($user);

        $stat = ['season' => '2025', 'team' => 'State U'];

        $cases = [
            ['teams', ['kind' => 'pub', 'name' => 'X'], 'kind'],
            ['teams', ['kind' => 'club'], 'name'],
            ['teams', ['kind' => 'club', 'name' => 'X', 'start_year' => 2024, 'end_year' => 2020], 'end_year'],
            ['teams', ['kind' => 'club', 'name' => 'X', 'start_year' => 1800], 'start_year'],
            ['stats', [...$stat, 'stats' => ['goals' => -1]], 'stats.goals'],
            ['stats', [...$stat, 'stats' => ['goals' => 'lots']], 'stats.goals'],
            ['stats', [...$stat, 'stats' => ['goals' => 2.5]], 'stats.goals'],
            ['stats', [...$stat, 'stats' => ['minutes' => 999999]], 'stats.minutes'],
            ['stats', [...$stat, 'stats' => ['goals_against_average' => '1.234']], 'stats.goals_against_average'],
            ['stats', [...$stat, 'stats' => ['goals_against_average' => -0.5]], 'stats.goals_against_average'],
            ['stats', ['season' => 'last year', 'team' => 'X'], 'season'],
            ['stats', ['season' => '25', 'team' => 'X'], 'season'],
            ['stats', ['season' => '2025-2026', 'team' => 'X'], 'season'],
            ['stats', ['season' => '2025'], 'team'],
            ['honours', ['title' => ''], 'title'],
            ['honours', ['title' => 'X', 'year' => 3000], 'year'],
            ['videos', ['kind' => 'highlight', 'title' => 'X', 'url' => 'http://youtube.com/watch?v=dQw4w9WgXcQ'], 'url'],
            ['videos', ['kind' => 'highlight', 'title' => 'X', 'url' => 'javascript:alert(1)'], 'url'],
            ['videos', ['kind' => 'highlight', 'title' => 'X', 'url' => 'not a link'], 'url'],
            ['videos', ['kind' => 'blooper', 'title' => 'X', 'url' => 'https://youtu.be/dQw4w9WgXcQ'], 'kind'],
            ['references', ['name' => 'R', 'role' => 'Coach', 'contact' => 'r@example.com'], 'consent'],
            ['references', ['name' => 'R', 'role' => 'Coach', 'contact' => 'r@example.com', 'consent' => false], 'consent'],
            ['references', ['name' => 'R', 'role' => 'Coach', 'consent' => true], 'contact'],
        ];

        foreach ($cases as [$type, $payload, $error]) {
            $response = $this->post("/profile/items/{$type}", $payload);
            $this->assertTrue(session()->has('errors') && session('errors')->has($error), "Expected [$error] error for $type ".json_encode($payload));
            session()->forget('errors');
        }

        $profile = $user->profile;
        $this->assertSame(0, $profile->teams()->count() + $profile->seasonStats()->count() + $profile->honours()->count() + $profile->videos()->count() + $profile->references()->count());
    }

    public function test_a_reference_cannot_be_saved_without_consent_even_on_update(): void
    {
        $user = $this->athlete();
        $reference = $user->profile->references()->create(['name' => 'R', 'role' => 'Coach', 'contact' => 'r@example.com', 'consent_confirmed_at' => now()]);

        $this->actingAs($user)
            ->put("/profile/items/references/{$reference->id}", ['name' => 'Changed', 'role' => 'Coach', 'contact' => 'r@example.com'])
            ->assertSessionHasErrors('consent');

        $this->assertSame('R', $reference->fresh()->name);
    }

    public function test_each_list_has_an_upper_limit(): void
    {
        $user = $this->athlete();
        $this->actingAs($user);

        for ($i = 0; $i < ProfileItemController::MAX_ITEMS; $i++) {
            $user->profile->honours()->create(['title' => "Honour $i"]);
        }

        $this->post('/profile/items/honours', ['title' => 'One too many'])->assertSessionHasErrors('limit');
        $this->assertSame(ProfileItemController::MAX_ITEMS, $user->profile->honours()->count());
    }

    public function test_completeness_counts_what_is_done(): void
    {
        $user = User::factory()->create();
        $profile = $user->profileOrCreate();

        $empty = ProfileCompleteness::summary($profile);
        $this->assertSame(14, $empty['total']);
        $this->assertSame(1, $empty['done']); // the name is copied from the account

        $profile->update([
            'sport_data' => ['positions' => ['ST'], 'preferred_foot' => 'right'],
            'height_cm' => 180,
            'current_school' => 'State U', 'division' => 'NAIA', 'class_year' => 'Junior',
            'statement' => 'Hello.',
        ]);
        $profile->teams()->create(['kind' => 'club', 'name' => 'City FC']);
        $profile->seasonStats()->create(['season' => '2025', 'team' => 'State U', 'stats' => []]);

        $summary = ProfileCompleteness::summary($profile->fresh());
        $this->assertSame(8, $summary['done']);

        $done = collect($summary['items'])->where('done', true)->pluck('key')->all();
        $this->assertEqualsCanonicalizing(['basics', 'positions', 'foot', 'school', 'class_year', 'history', 'stats', 'statement'], $done);

        $page = $this->actingAs($user)->get('/dashboard')->assertOk();
        $this->assertSame(8, $this->props($page)['completeness']['done']);
    }

    public function test_profile_writes_are_rate_limited(): void
    {
        $this->actingAs($this->athlete());

        for ($i = 0; $i < 60; $i++) {
            $this->put('/profile/statement', ['statement' => 'x']);
        }

        $this->put('/profile/statement', ['statement' => 'x'])->assertStatus(429);
    }
}
