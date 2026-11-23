<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    public function test_guests_are_sent_to_login_from_every_private_page(): void
    {
        $pages = [
            '/dashboard', '/profile', '/profile/basics', '/profile/stats', '/profile/photo', '/profile/preview',
            '/sharing', '/inbox', '/inbox/1',
            '/settings', '/settings/account', '/settings/password', '/settings/two-factor',
            '/settings/appearance', '/settings/data', '/settings/data/export',
        ];

        foreach ($pages as $page) {
            $this->get($page)->assertRedirect('/login');
        }
    }

    public function test_guests_cannot_change_anything(): void
    {
        $owner = $this->athlete();
        $team = $owner->profile->teams()->create(['kind' => 'club', 'name' => 'Original FC']);

        $writes = [
            ['put', '/profile/basics', ['display_name' => 'Hacked']],
            ['post', '/profile/photo', []],
            ['delete', '/profile/photo', []],
            ['post', '/profile/items/teams', ['kind' => 'club', 'name' => 'X']],
            ['put', "/profile/items/teams/{$team->id}", ['kind' => 'club', 'name' => 'Hacked']],
            ['delete', "/profile/items/teams/{$team->id}", []],
            ['post', '/sharing/link', []],
            ['delete', '/sharing/link', []],
            ['put', '/sharing/visibility', ['hidden_sections' => []]],
            ['delete', '/inbox/1', []],
            ['patch', '/settings/account', ['name' => 'x', 'email' => 'x@example.com']],
            ['put', '/settings/password', []],
            ['post', '/settings/two-factor', []],
            ['post', '/settings/two-factor/confirm', []],
            ['post', '/settings/two-factor/recovery-codes', []],
            ['delete', '/settings/two-factor', []],
            ['delete', '/settings/data', ['password' => 'password']],
        ];

        foreach ($writes as [$method, $url, $data]) {
            $this->{$method}($url, $data)->assertRedirect('/login');
        }

        $this->assertSame('Original FC', $team->fresh()->name);
        $this->assertSame(1, User::count());
    }

    public function test_unverified_athletes_are_held_at_the_verify_email_page(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);

        foreach (['/dashboard', '/profile', '/profile/basics', '/profile/preview', '/sharing', '/inbox'] as $page) {
            $this->get($page)->assertRedirect(route('verification.notice'));
        }

        $this->put('/profile/basics', ['display_name' => 'Nope'])->assertRedirect(route('verification.notice'));
        $this->post('/sharing/link')->assertRedirect(route('verification.notice'));
        $this->assertNull($user->fresh()->profile);
    }

    public function test_one_athlete_cannot_change_or_delete_another_athletes_entries(): void
    {
        $a = $this->athlete();
        $b = $this->athlete();

        $items = [
            'teams' => [$a->profile->teams()->create(['kind' => 'club', 'name' => 'A Club']), ['kind' => 'club', 'name' => 'Hacked'], 'name', 'A Club'],
            'stats' => [$a->profile->seasonStats()->create(['season' => '2025', 'team' => 'A Team', 'stats' => ['goals' => 3]]), ['season' => '2025', 'team' => 'Hacked', 'stats' => ['goals' => 99]], 'team', 'A Team'],
            'honours' => [$a->profile->honours()->create(['title' => 'A Honour']), ['title' => 'Hacked'], 'title', 'A Honour'],
            'videos' => [$a->profile->videos()->create(['kind' => 'highlight', 'title' => 'A Video', 'url' => 'https://youtu.be/dQw4w9WgXcQ']), ['kind' => 'highlight', 'title' => 'Hacked', 'url' => 'https://example.com/x'], 'title', 'A Video'],
            'references' => [$a->profile->references()->create(['name' => 'A Ref', 'role' => 'Coach', 'contact' => 'ref@example.com', 'consent_confirmed_at' => now()]), ['name' => 'Hacked', 'role' => 'x', 'contact' => 'x', 'consent' => true], 'name', 'A Ref'],
        ];

        $this->actingAs($b);

        foreach ($items as $type => [$item, $payload, $column, $original]) {
            $this->put("/profile/items/{$type}/{$item->id}", $payload)->assertNotFound();
            $this->assertSame($original, $item->fresh()->{$column}, "$type was changed by another athlete");

            $this->delete("/profile/items/{$type}/{$item->id}")->assertNotFound();
            $this->assertNotNull($item->fresh(), "$type was deleted by another athlete");
        }

        // B's own profile gained nothing from the attempts.
        $this->assertSame(0, $b->profile->teams()->count());
    }

    public function test_an_id_from_a_different_list_type_does_not_cross_over(): void
    {
        $a = $this->athlete();
        $honour = $a->profile->honours()->create(['title' => 'Mine']);

        $this->actingAs($a)->delete("/profile/items/teams/{$honour->id}")->assertNotFound();
        $this->assertNotNull($honour->fresh());

        $this->actingAs($a)->post('/profile/items/secrets', ['x' => 1])->assertNotFound();
    }

    public function test_one_athlete_cannot_read_or_delete_another_athletes_messages(): void
    {
        $a = $this->athlete();
        $b = $this->athlete();
        $message = $a->profile->messages()->create([
            'sender_name' => 'Coach', 'sender_school' => 'School', 'sender_role' => 'Head coach',
            'sender_email' => 'coach@example.edu', 'body' => 'Private words for athlete A only.',
        ]);

        $this->actingAs($b);

        $this->get("/inbox/{$message->id}")->assertNotFound();
        $this->assertNull($message->fresh()->read_at);

        $this->delete("/inbox/{$message->id}")->assertNotFound();
        $this->assertNotNull($message->fresh());

        $inbox = $this->get('/inbox')->assertOk();
        $this->assertCount(0, $this->props($inbox)['messages']);
        $this->assertStringNotContainsString('Private words', json_encode($this->props($inbox)));
        $this->assertSame(0, $this->props($inbox)['unread']);
    }

    public function test_the_photo_route_only_ever_serves_the_signed_in_athletes_own_photo(): void
    {
        Storage::fake('local');
        $a = $this->athlete();
        $b = $this->athlete();

        $this->actingAs($a)->post('/profile/photo', ['photo' => UploadedFile::fake()->image('me.jpg', 300, 300)])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($a->profile->fresh()->photo_path);

        $this->actingAs($a)->get('/profile/photo')->assertOk()->assertHeader('Content-Type', 'image/jpeg');

        // B has no photo, and there is no way to name A's photo in the address.
        $this->actingAs($b)->get('/profile/photo')->assertNotFound();
        $this->actingAs($b)->get('/profile/photo?profile='.$a->profile->id.'&user_id='.$a->id)->assertNotFound();

        // The stored file is not reachable as a public file either.
        $this->get('/storage/'.$a->profile->fresh()->photo_path)->assertNotFound();
        $this->get('/'.$a->profile->fresh()->photo_path)->assertNotFound();
    }

    public function test_extra_fields_in_a_request_cannot_move_a_profile_or_mark_it_as_demo(): void
    {
        $a = $this->athlete();
        $b = $this->athlete();

        $this->actingAs($b)->put('/profile/basics', [
            'display_name' => 'B Name',
            'user_id' => $a->id,
            'is_demo' => true,
            'photo_path' => 'photos/elsewhere.jpg',
            'hidden_sections' => [],
        ])->assertSessionHasNoErrors();

        $profile = $b->profile->fresh();
        $this->assertSame($b->id, $profile->user_id);
        $this->assertFalse($profile->is_demo);
        $this->assertNull($profile->photo_path);
        $this->assertContains('gpa', $profile->hidden_sections);
    }

    public function test_the_preview_shows_only_the_signed_in_athletes_profile(): void
    {
        $a = $this->athlete(['display_name' => 'Athlete Aaa']);
        $b = $this->athlete(['display_name' => 'Athlete Bbb']);

        $response = $this->actingAs($b)->get('/profile/preview?profile='.$a->profile->id)->assertOk();

        $this->assertSame('Athlete Bbb', $this->props($response)['profile']['display_name']);
        $this->assertSame('preview', $this->props($response)['mode']);
    }
}
