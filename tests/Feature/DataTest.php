<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Profile;
use App\Models\ProfileView;
use App\Models\ShareLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;
use ZipArchive;

class DataTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    /** An athlete with something in every table. */
    private function fullAthlete(string $tag): User
    {
        $user = $this->athlete(['display_name' => "Athlete $tag", 'statement' => "Statement $tag", 'gpa' => 3.2, 'major' => "Major $tag"]);
        $p = $user->profile;
        $p->teams()->create(['kind' => 'club', 'name' => "Club $tag"]);
        $p->seasonStats()->create(['season' => '2025', 'team' => "Team $tag", 'stats' => ['goals' => 5]]);
        $p->honours()->create(['title' => "Honour $tag"]);
        $p->videos()->create(['kind' => 'highlight', 'title' => "Video $tag", 'url' => 'https://youtu.be/dQw4w9WgXcQ']);
        $p->references()->create(['name' => "Ref $tag", 'role' => 'Coach', 'contact' => "ref-$tag@example.com", 'consent_confirmed_at' => now()]);

        $link = ShareLink::issueFor($p);
        ProfileView::create(['share_link_id' => $link->id, 'viewed_on' => now()->toDateString(), 'visitor_hash' => hash('sha256', $tag)]);
        $p->messages()->create([
            'share_link_id' => $link->id, 'sender_name' => "Coach $tag", 'sender_school' => 'School', 'sender_role' => 'Head coach',
            'sender_email' => "coach-$tag@example.edu", 'body' => "Message body $tag",
        ]);

        $this->actingAs($user)->post('/profile/photo', ['photo' => UploadedFile::fake()->image('p.jpg', 200, 200)]);
        $this->post('/logout');

        DB::table('sessions')->insert(['id' => "session-$tag", 'user_id' => $user->id, 'payload' => 'x', 'last_activity' => time()]);
        DB::table('password_reset_tokens')->insert(['email' => $user->email, 'token' => 'x', 'created_at' => now()]);

        return $user->fresh();
    }

    /** @return array{0: array<string, mixed>, 1: list<string>, 2: string} */
    private function export(User $user): array
    {
        $response = $this->actingAs($user)->get('/settings/data/export')->assertOk();
        $response->assertHeader('Content-Type', 'application/zip');
        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($response->baseResponse->getFile()->getPathname()));

        $names = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $names[] = $zip->getNameIndex($i);
        }
        $json = $zip->getFromName('data.json');
        $photo = $zip->getFromName('photo.jpg');
        $zip->close();

        if ($photo !== false) {
            $this->assertSame("\xFF\xD8\xFF", substr($photo, 0, 3));
        }

        return [json_decode($json, true, flags: JSON_THROW_ON_ERROR), $names, $json];
    }

    public function test_the_export_contains_everything_the_athlete_gave_us(): void
    {
        $user = $this->fullAthlete('one');

        [$data, $names, $json] = $this->export($user);

        $this->assertEqualsCanonicalizing(['data.json', 'photo.jpg'], $names);

        $this->assertSame($user->email, $data['account']['email']);
        $this->assertSame($user->name, $data['account']['name']);
        $this->assertNotNull($data['account']['confirmed_18_or_over_at']);
        $this->assertFalse($data['account']['two_step_login']);

        $this->assertSame('Athlete one', $data['profile']['display_name']);
        $this->assertSame('Statement one', $data['profile']['statement']);
        $this->assertSame('Major one', $data['profile']['major']);
        $this->assertSame('3.20', $data['profile']['gpa']);
        $this->assertSame('Club one', $data['profile']['teams'][0]['name']);
        $this->assertSame(5, $data['profile']['season_stats'][0]['stats']['goals']);
        $this->assertSame('Honour one', $data['profile']['honours'][0]['title']);
        $this->assertSame('https://youtu.be/dQw4w9WgXcQ', $data['profile']['videos'][0]['url']);
        $this->assertSame('ref-one@example.com', $data['profile']['references'][0]['contact']);
        $this->assertContains('gpa', $data['profile']['hidden_sections']);

        $this->assertCount(1, $data['share_links']);
        $this->assertSame($user->profile->currentLink()->url(), $data['share_links'][0]['url']);
        $this->assertSame(1, $data['share_links'][0]['unique_daily_visits']);

        $this->assertCount(1, $data['messages_received']);
        $this->assertSame('Message body one', $data['messages_received'][0]['message']);
        $this->assertSame('coach-one@example.edu', $data['messages_received'][0]['sender_email']);
    }

    public function test_the_export_holds_no_secrets_and_nothing_from_other_athletes(): void
    {
        $other = $this->fullAthlete('other');
        $user = $this->fullAthlete('mine');
        $secret = (new Google2FA)->generateSecretKey(32);
        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => ['recov-code1'],
            'two_factor_confirmed_at' => now(),
            'remember_token' => 'remember-token-value',
        ])->save();

        [$data, , $json] = $this->export($user->fresh());

        $this->assertTrue($data['account']['two_step_login']);

        foreach ([$user->password, $secret, 'recov-code1', 'remember-token-value', $user->profile->currentLink()->token_hash, $user->profile->photo_path] as $secretValue) {
            $this->assertStringNotContainsString($secretValue, $json);
        }
        foreach (['password', 'two_factor_secret', 'recovery_codes', 'token_hash', 'token_encrypted', 'visitor_hash', 'remember_token', 'photo_path'] as $key) {
            $this->assertStringNotContainsString('"'.$key.'"', $json);
        }
        // Revoked links are listed but their address is not repeated.
        $this->actingAs($user)->post('/sharing/link');
        [$again] = $this->export($user->fresh());
        $this->assertNull($again['share_links'][0]['url']);
        $this->assertNotNull($again['share_links'][0]['revoked_at']);
        $this->assertNotNull($again['share_links'][1]['url']);

        foreach (['Athlete other', 'Club other', 'Message body other', 'coach-other@example.edu', $other->email] as $foreign) {
            $this->assertStringNotContainsString($foreign, $json);
        }
    }

    public function test_the_export_works_for_a_brand_new_account_without_a_photo(): void
    {
        [$data, $names] = $this->export(User::factory()->create());

        $this->assertSame(['data.json'], $names);
        $this->assertSame([], $data['share_links']);
        $this->assertSame([], $data['messages_received']);
    }

    public function test_the_data_page_renders_and_export_is_rate_limited(): void
    {
        $this->actingAs($this->athlete())->get('/settings/data')->assertOk();

        for ($i = 0; $i < 10; $i++) {
            $this->get('/settings/data/export')->assertOk();
        }
        $this->get('/settings/data/export')->assertStatus(429);
    }

    public function test_deleting_needs_the_right_password(): void
    {
        $user = $this->fullAthlete('keep');

        $this->actingAs($user)->from('/settings/data')->delete('/settings/data', ['password' => 'wrong-password'])
            ->assertSessionHasErrors('password')
            ->assertRedirect('/settings/data');
        $this->delete('/settings/data', [])->assertSessionHasErrors('password');

        $this->assertNotNull($user->fresh());
        $this->assertAuthenticatedAs($user);
        $this->assertSame(1, Profile::count());
        Storage::disk('local')->assertExists($user->profile->photo_path);
    }

    public function test_deleting_the_account_removes_everything_and_leaves_other_athletes_alone(): void
    {
        $other = $this->fullAthlete('other');
        $user = $this->fullAthlete('gone');
        $profileId = $user->profile->id;
        $photo = $user->profile->photo_path;
        $token = $user->profile->currentLink()->token();
        $linkIds = $user->profile->shareLinks()->pluck('id');

        $this->actingAs($user)->delete('/settings/data', ['password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
        $this->assertNull(Profile::find($profileId));

        foreach (['profile_teams', 'season_stats', 'honours', 'videos', 'profile_references', 'share_links', 'contact_messages'] as $table) {
            $this->assertSame(0, DB::table($table)->where('profile_id', $profileId)->count(), "$table still has rows");
            $this->assertSame(1, DB::table($table)->count(), "$table lost the other athlete's row");
        }
        $this->assertSame(0, DB::table('profile_views')->whereIn('share_link_id', $linkIds)->count());
        $this->assertSame(1, ProfileView::count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $other->id)->count());
        $this->assertSame(0, DB::table('password_reset_tokens')->where('email', $user->email)->count());
        $this->assertSame(1, DB::table('password_reset_tokens')->count());

        Storage::disk('local')->assertMissing($photo);
        Storage::disk('local')->assertExists($other->profile->photo_path);
        $this->assertCount(1, Storage::disk('local')->allFiles());

        // The old link is dead, and the email and password no longer work.
        $this->get('/p/'.$token)->assertNotFound();
        $this->get("/p/{$token}/photo")->assertNotFound();
        $this->post('/login', ['email' => $user->email, 'password' => 'password']);
        $this->assertGuest();

        // The other athlete is fully intact.
        $this->assertSame('Athlete other', $other->fresh()->profile->display_name);
        $this->assertSame(1, ContactMessage::count());
        $this->get('/p/'.$other->profile->currentLink()->token())->assertOk();
    }

    public function test_guests_cannot_export_or_delete(): void
    {
        $user = $this->fullAthlete('safe');

        $this->get('/settings/data/export')->assertRedirect('/login');
        $this->delete('/settings/data', ['password' => 'password'])->assertRedirect('/login');

        $this->assertNotNull($user->fresh());
    }
}
