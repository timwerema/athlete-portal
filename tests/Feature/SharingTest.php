<?php

namespace Tests\Feature;

use App\Models\Profile;
use App\Models\ShareLink;
use App\Models\User;
use Database\Seeders\DemoProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;

class SharingTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    private function tokenFromSharingPage(): string
    {
        $url = $this->props($this->get('/sharing')->assertOk())['link']['url'];

        return Str::afterLast($url, '/p/');
    }

    public function test_a_new_profile_is_visible_to_nobody(): void
    {
        $user = $this->athlete();

        $this->assertSame(0, ShareLink::count());
        $this->assertNull($user->profile->currentLink());

        $page = $this->actingAs($user)->get('/sharing')->assertOk();
        $this->assertNull($this->props($page)['link']);
    }

    public function test_guessed_and_malformed_links_are_not_found(): void
    {
        $this->athlete();

        $bad = [
            Str::random(43),
            Str::random(42),
            Str::random(44),
            '1',
            'profile-1',
            str_repeat('a', 43),
            Str::random(42).'%00',
            Str::random(40).'../',
            "1' OR '1'='1",
        ];

        foreach ($bad as $token) {
            $this->get('/p/'.$token)->assertNotFound();
        }

        // Profile and user IDs are never a way in.
        $this->get('/p/'.Profile::first()->id)->assertNotFound();
    }

    public function test_link_lifecycle_create_open_revoke_regenerate(): void
    {
        $user = $this->athlete(['display_name' => 'Link Owner']);
        $this->actingAs($user);

        $this->post('/sharing/link')->assertSessionHasNoErrors();
        $first = $this->tokenFromSharingPage();
        $this->assertMatchesRegularExpression('/\A[A-Za-z0-9]{43}\z/', $first);

        $this->post('/logout');

        $page = $this->get('/p/'.$first)->assertOk();
        $this->assertSame('share/show', $page->viewData('page')['component']);
        $this->assertSame('Link Owner', $this->props($page)['profile']['display_name']);
        $this->assertSame('coach', $this->props($page)['mode']);
        $this->assertNull($this->props($page)['auth']['user']);

        // Revoke.
        $this->actingAs($user)->delete('/sharing/link')->assertSessionHasNoErrors();
        $this->assertNull($this->props($this->get('/sharing'))['link']);
        $this->post('/logout');

        $gone = $this->get('/p/'.$first)->assertNotFound();
        $this->assertSame('share/unavailable', $gone->viewData('page')['component']);
        $this->assertStringNotContainsString('Link Owner', $gone->getContent());

        // Regenerate twice: only the newest works.
        $this->actingAs($user)->post('/sharing/link');
        $second = $this->tokenFromSharingPage();
        $this->post('/sharing/link');
        $third = $this->tokenFromSharingPage();
        $this->post('/logout');

        $this->assertCount(3, array_unique([$first, $second, $third]));
        $this->get('/p/'.$first)->assertNotFound();
        $this->get('/p/'.$second)->assertNotFound();
        $this->get('/p/'.$third)->assertOk();
        $this->assertSame(1, ShareLink::whereNull('revoked_at')->count());
    }

    public function test_the_token_is_not_stored_in_readable_form(): void
    {
        $user = $this->athlete();
        $link = $this->linkFor($user);
        $row = DB::table('share_links')->first();

        $this->assertSame(hash('sha256', $link->token()), $row->token_hash);
        $this->assertStringNotContainsString($link->token(), $row->token_encrypted);
        $this->assertStringNotContainsString($link->token(), json_encode($link->toArray()));
    }

    public function test_one_athletes_link_actions_do_not_touch_another_athletes_link(): void
    {
        $a = $this->athlete();
        $b = $this->athlete();
        $link = $this->linkFor($a);

        $this->actingAs($b)->delete('/sharing/link');
        $this->actingAs($b)->post('/sharing/link');
        $this->post('/logout');

        $this->assertNull($link->fresh()->revoked_at);
        $this->get('/p/'.$link->token())->assertOk();
    }

    public function test_the_coach_page_never_contains_the_athletes_email_or_private_fields(): void
    {
        $user = $this->athlete([
            'gpa' => 3.91, 'gpa_scale' => '4.0', 'major' => 'History',
            'portal_entered_on' => '2026-05-04',
        ], ['email' => 'very.private.athlete@example.com']);
        $user->profile->references()->create([
            'name' => 'Referee Person', 'role' => 'Coach', 'contact' => 'referee.contact@example.com', 'consent_confirmed_at' => now(),
        ]);
        $link = $this->linkFor($user);

        $response = $this->get('/p/'.$link->token())->assertOk();
        $everything = $response->getContent();

        $this->assertStringNotContainsString('very.private.athlete@example.com', $everything);
        $this->assertStringNotContainsString('very.private.athlete', json_encode($this->props($response)));
        $this->assertStringNotContainsString('referee.contact@example.com', $everything);
        $this->assertStringNotContainsString('Referee Person', $everything);
        $this->assertStringNotContainsString('3.91', $everything);
        $this->assertStringNotContainsString('2026-05-04', $everything);
        $this->assertStringNotContainsString('photo_path', $everything);
        $this->assertStringNotContainsString('user_id', $everything);

        $profile = $this->props($response)['profile'];
        $this->assertNull($profile['gpa']);
        $this->assertSame([], $profile['references']);
        $this->assertNull($profile['portal_entered_on']);
        $this->assertSame('History', $profile['major']);
    }

    public function test_gpa_and_references_appear_only_after_the_athlete_switches_them_on(): void
    {
        $user = $this->athlete(['gpa' => 3.91, 'gpa_scale' => '4.0', 'portal_entered_on' => '2026-05-04']);
        $user->profile->references()->create([
            'name' => 'Referee Person', 'role' => 'Coach', 'contact' => 'referee.contact@example.com', 'consent_confirmed_at' => now(),
        ]);
        $link = $this->linkFor($user);

        $this->actingAs($user)->put('/sharing/visibility', ['hidden_sections' => []])->assertSessionHasNoErrors();
        $this->post('/logout');

        $profile = $this->props($this->get('/p/'.$link->token()))['profile'];
        $this->assertSame(['value' => '3.91', 'scale' => '4.0'], $profile['gpa']);
        $this->assertSame('Referee Person', $profile['references'][0]['name']);
        $this->assertSame('referee.contact@example.com', $profile['references'][0]['contact']);
        $this->assertSame('2026-05-04', $profile['portal_entered_on']);
    }

    public function test_every_hidden_section_is_left_out_of_the_coach_page(): void
    {
        $user = $this->athlete([
            'pronouns' => 'he/him', 'height_cm' => 183, 'conference' => 'Hidden Conference',
            'eligibility_years_left' => 2, 'major' => 'Hidden Major', 'gpa' => 3.5,
            'statement' => 'Hidden statement text.', 'portal_entered_on' => '2026-05-04',
            'looking_for' => ['levels' => ['NAIA'], 'timeline' => 'Hidden timeline'],
        ]);
        $p = $user->profile;
        $p->teams()->create(['kind' => 'club', 'name' => 'Hidden Club']);
        $p->seasonStats()->create(['season' => '2025', 'team' => 'Hidden Stat Team', 'stats' => ['goals' => 77]]);
        $p->honours()->create(['title' => 'Hidden Honour']);
        $p->videos()->create(['kind' => 'highlight', 'title' => 'Hidden Video', 'url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $p->references()->create(['name' => 'Hidden Ref', 'role' => 'Coach', 'contact' => 'x@example.com', 'consent_confirmed_at' => now()]);
        $link = $this->linkFor($user);

        // Everything on: all of it is present.
        $p->update(['hidden_sections' => []]);
        $shown = json_encode($this->props($this->get('/p/'.$link->token())), JSON_UNESCAPED_SLASHES);
        foreach (['he/him', 'Hidden Conference', 'Hidden Major', 'Hidden statement', 'Hidden timeline', 'Hidden Club', 'Hidden Stat Team', 'Hidden Honour', 'Hidden Video', 'Hidden Ref'] as $text) {
            $this->assertStringContainsString($text, $shown);
        }

        // Everything off: none of it is.
        $p->update(['hidden_sections' => array_keys(Profile::HIDEABLE)]);
        $response = $this->get('/p/'.$link->token())->assertOk();
        $hidden = json_encode($this->props($response), JSON_UNESCAPED_SLASHES);
        foreach (['he/him', 'Hidden Conference', 'Test College', 'Hidden Major', 'Hidden statement', 'Hidden timeline', 'Hidden Club', 'Hidden Stat Team', 'Hidden Honour', 'Hidden Video', 'Hidden Ref', 'youtu.be', '2026-05-04'] as $text) {
            $this->assertStringNotContainsString($text, $hidden);
        }

        $profile = $this->props($response)['profile'];
        $this->assertNull($profile['height_cm']);
        $this->assertNull($profile['class_year']);
        $this->assertNull($profile['eligibility_years_left']);
        $this->assertNull($profile['gpa']);
        $this->assertNull($profile['photo_url']);
        $this->assertSame([], $profile['season_stats']);
    }

    public function test_visibility_only_accepts_known_section_names(): void
    {
        $user = $this->athlete();
        $this->actingAs($user);

        $this->put('/sharing/visibility', ['hidden_sections' => ['gpa', 'email']])->assertSessionHasErrors('hidden_sections.1');
        $this->put('/sharing/visibility', ['hidden_sections' => 'gpa'])->assertSessionHasErrors('hidden_sections');
        $this->put('/sharing/visibility', [])->assertSessionHasErrors('hidden_sections');
        $this->put('/sharing/visibility', ['hidden_sections' => ['gpa', 'gpa']])->assertSessionHasErrors();
        $this->assertEqualsCanonicalizing(Profile::HIDDEN_BY_DEFAULT, $user->profile->fresh()->hidden_sections);

        $this->put('/sharing/visibility', ['hidden_sections' => ['stats', 'height']])->assertSessionHasNoErrors();
        $this->assertSame(['stats', 'height'], $user->profile->fresh()->hidden_sections);
    }

    public function test_goalkeeper_stat_columns_only_show_for_goalkeepers(): void
    {
        $keeper = $this->athlete(['sport_data' => ['positions' => ['GK']]]);
        $striker = $this->athlete(['sport_data' => ['positions' => ['ST']]]);

        $k = collect($this->props($this->get('/p/'.$this->linkFor($keeper)->token()))['profile']['stat_fields'])->pluck('key');
        $s = collect($this->props($this->get('/p/'.$this->linkFor($striker)->token()))['profile']['stat_fields'])->pluck('key');

        $this->assertContains('saves', $k);
        $this->assertContains('goals_against_average', $k);
        $this->assertNotContains('saves', $s);
        $this->assertContains('goals', $s);
    }

    public function test_video_links_reach_the_coach_page_with_a_safe_embed_address_or_none(): void
    {
        $user = $this->athlete();
        $user->profile->videos()->create(['kind' => 'highlight', 'title' => 'YT', 'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ']);
        $user->profile->videos()->create(['kind' => 'highlight', 'title' => 'Other', 'url' => 'https://evil.example/embed/dQw4w9WgXcQ']);

        $videos = collect($this->props($this->get('/p/'.$this->linkFor($user)->token()))['profile']['videos'])->keyBy('title');

        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $videos['YT']['embed_url']);
        $this->assertNull($videos['Other']['embed_url']);
    }

    public function test_demo_profile_exists_only_after_seeding_and_is_labelled(): void
    {
        $this->get('/demo')->assertNotFound();
        $this->assertFalse($this->props($this->get('/'))['hasDemo']);

        $this->seed(DemoProfileSeeder::class);
        $this->seed(DemoProfileSeeder::class);

        $this->assertSame(1, Profile::where('is_demo', true)->count());
        $this->assertSame(1, User::where('email', DemoProfileSeeder::EMAIL)->count());
        $this->assertSame(3, Profile::where('is_demo', true)->first()->teams()->count());

        $page = $this->get('/demo')->assertOk();
        $this->assertTrue($this->props($page)['profile']['is_demo']);
        $this->assertSame('demo', $this->props($page)['mode']);
        $this->assertNull($this->props($page)['contact']);
        $this->assertStringNotContainsString(DemoProfileSeeder::EMAIL, $page->getContent());
        $this->assertTrue($this->props($this->get('/'))['hasDemo']);

        // Nobody can sign in as the demo athlete, and it has no share link.
        $this->post('/login', ['email' => DemoProfileSeeder::EMAIL, 'password' => 'password']);
        $this->assertGuest();
        $this->assertSame(0, ShareLink::count());
    }

    public function test_a_real_profile_is_never_shown_at_the_demo_address(): void
    {
        $this->athlete(['display_name' => 'Real Person']);

        $this->get('/demo')->assertNotFound();
    }
}
