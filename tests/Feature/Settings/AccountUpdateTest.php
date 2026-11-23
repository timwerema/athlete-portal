<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_page_is_displayed(): void
    {
        $this->actingAs(User::factory()->create())->get('/settings/account')->assertOk();
    }

    public function test_settings_redirects_to_account(): void
    {
        $this->actingAs(User::factory()->create())->get('/settings')->assertRedirect('/settings/account');
    }

    public function test_name_can_be_updated_without_losing_verification(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/account', ['name' => 'New Name', 'email' => $user->email])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/settings/account');

        $this->assertSame('New Name', $user->refresh()->name);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_changing_email_needs_verifying_again_and_sends_the_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/account', ['name' => $user->name, 'email' => 'new@example.com'])
            ->assertSessionHasNoErrors();

        $this->assertSame('new@example.com', $user->refresh()->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);

        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_email_cannot_be_changed_to_one_already_in_use(): void
    {
        $other = User::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch('/settings/account', ['name' => $user->name, 'email' => $other->email])
            ->assertSessionHasErrors('email');
    }

    public function test_guests_cannot_update_an_account(): void
    {
        $this->patch('/settings/account', ['name' => 'x', 'email' => 'x@example.com'])->assertRedirect('/login');
    }
}
