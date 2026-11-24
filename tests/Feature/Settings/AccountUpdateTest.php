<?php

namespace Tests\Feature\Settings;

use App\Http\Controllers\Settings\ProfileController;
use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use App\Notifications\EmailChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AccountUpdateTest extends TestCase
{
    use RefreshDatabase;

    private function confirmUrl(User $user, string $email, int $minutes = 60): string
    {
        return URL::temporarySignedRoute('account.email.confirm', now()->addMinutes($minutes), ['id' => $user->id, 'hash' => sha1($email)]);
    }

    public function test_account_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())->get('/settings/account')->assertOk();
    }

    public function test_settings_redirects_to_account(): void
    {
        $this->actingAs(User::factory()->create())->get('/settings')->assertRedirect('/settings/account');
    }

    public function test_name_can_be_updated_without_a_password_or_losing_verification(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/account', ['name' => 'New Name', 'email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/account');

        $this->assertSame('New Name', $user->refresh()->name);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->pending_email);
        Notification::assertNothingSent();
    }

    public function test_changing_email_needs_the_password(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/account', ['name' => $user->name, 'email' => 'new@example.com'])
            ->assertSessionHasErrors('current_password');

        $this->actingAs($user)
            ->patch('/settings/account', ['name' => $user->name, 'email' => 'new@example.com', 'current_password' => 'wrong-password'])
            ->assertSessionHasErrors('current_password');

        $this->assertNull($user->refresh()->pending_email);
        Notification::assertNothingSent();
    }

    public function test_a_new_email_only_takes_effect_after_the_link_is_opened(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'old@example.com']);

        $this->actingAs($user)
            ->patch('/settings/account', ['name' => $user->name, 'email' => 'new@example.com', 'current_password' => 'password'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', ProfileController::EMAIL_NOTICE);

        $user->refresh();
        $this->assertSame('old@example.com', $user->email);
        $this->assertSame('new@example.com', $user->pending_email);
        $this->assertNotNull($user->email_verified_at);
        $this->get('/dashboard')->assertOk();

        $url = null;
        Notification::assertSentTo(new AnonymousNotifiable, ConfirmEmailChange::class, function ($notification, $channels, $notifiable) use (&$url) {
            $url = $notification->url;

            return $notifiable->routes['mail'] === 'new@example.com';
        });

        $this->get($url)->assertRedirect('/settings/account');

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->pending_email);
        $this->assertNotNull($user->email_verified_at);

        Notification::assertSentTo(new AnonymousNotifiable, EmailChanged::class, fn ($n, $c, $notifiable) => $notifiable->routes['mail'] === 'old@example.com');
    }

    public function test_asking_for_an_address_in_use_looks_the_same_and_sends_nothing(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create(['email' => 'mine@example.com']);

        $taken = $this->actingAs($user)
            ->patch('/settings/account', ['name' => $user->name, 'email' => 'taken@example.com', 'current_password' => 'password']);
        $free = $this->actingAs($user)
            ->patch('/settings/account', ['name' => $user->name, 'email' => 'free@example.com', 'current_password' => 'password']);

        foreach ([$taken, $free] as $response) {
            $response->assertSessionHasNoErrors()->assertRedirect('/settings/account')->assertSessionHas('status', ProfileController::EMAIL_NOTICE);
        }

        Notification::assertSentTimes(ConfirmEmailChange::class, 1);
        $this->assertSame('mine@example.com', $user->refresh()->email);
    }

    public function test_the_link_fails_if_the_address_was_taken_in_the_meantime(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'mine@example.com']);
        $user->forceFill(['pending_email' => 'wanted@example.com'])->save();
        User::factory()->create(['email' => 'wanted@example.com']);

        $this->actingAs($user)->get($this->confirmUrl($user, 'wanted@example.com'))->assertRedirect('/settings/account');

        $this->assertSame('mine@example.com', $user->refresh()->email);
        Notification::assertNothingSent();
    }

    public function test_tampered_expired_stale_or_foreign_links_change_nothing(): void
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);
        $user->forceFill(['pending_email' => 'new@example.com'])->save();
        $other = User::factory()->create();

        // unsigned and tampered
        $this->actingAs($user)->get("/settings/account/confirm-email/{$user->id}/".sha1('new@example.com'))->assertForbidden();
        $this->actingAs($user)->get($this->confirmUrl($user, 'new@example.com').'x')->assertForbidden();

        // a valid link for a different address than the one now pending
        $this->actingAs($user)->get($this->confirmUrl($user, 'older-request@example.com'))->assertRedirect('/settings/account');

        // someone else's link
        $this->actingAs($other)->get($this->confirmUrl($user, 'new@example.com'))->assertRedirect('/settings/account');

        // expired
        $url = $this->confirmUrl($user, 'new@example.com', 60);
        $this->travel(61)->minutes();
        $this->actingAs($user)->get($url)->assertForbidden();

        $this->assertSame('mine@example.com', $user->refresh()->email);
        $this->assertSame('new@example.com', $user->pending_email);
        $this->assertNotSame('new@example.com', $other->refresh()->email);
    }

    public function test_the_link_needs_a_logged_in_athlete(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['pending_email' => 'new@example.com'])->save();

        $this->get($this->confirmUrl($user, 'new@example.com'))->assertRedirect('/login');
    }

    public function test_a_pending_change_can_be_cancelled_and_its_link_then_does_nothing(): void
    {
        $user = User::factory()->create(['email' => 'mine@example.com']);
        $user->forceFill(['pending_email' => 'new@example.com'])->save();
        $url = $this->confirmUrl($user, 'new@example.com');

        $this->actingAs($user)->delete('/settings/account/pending-email')->assertRedirect('/settings/account');
        $this->assertNull($user->refresh()->pending_email);

        $this->actingAs($user)->get($url)->assertRedirect('/settings/account');
        $this->assertSame('mine@example.com', $user->refresh()->email);
    }

    public function test_the_pending_address_is_not_shared_with_every_page(): void
    {
        $user = User::factory()->create();
        $user->forceFill(['pending_email' => 'new@example.com'])->save();

        $props = $this->actingAs($user)->get('/dashboard')->viewData('page')['props'];

        $this->assertStringNotContainsString('new@example.com', json_encode($props));
    }

    public function test_guests_cannot_update_an_account(): void
    {
        $this->patch('/settings/account', ['name' => 'x', 'email' => 'x@example.com'])->assertRedirect('/login');
        $this->delete('/settings/account/pending-email')->assertRedirect('/login');
    }
}
