<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Notifications\RegistrationAttempted;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private function form(array $overrides = []): array
    {
        return [
            'name' => 'Test Athlete',
            'email' => 'athlete@example.com',
            'password' => 'long-enough-password',
            'password_confirmation' => 'long-enough-password',
            'date_of_birth' => now()->subYears(20)->format('Y-m-d'),
            'terms' => true,
            ...$overrides,
        ];
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $this->get('/register')->assertStatus(200);
    }

    public function test_an_adult_can_register_and_only_the_confirmation_time_is_kept(): void
    {
        Notification::fake();
        $dob = now()->subYears(20)->format('Y-m-d');

        $response = $this->post('/register', $this->form(['date_of_birth' => $dob]));

        // Not signed in yet: the email address has to be confirmed first.
        $this->assertGuest();
        $response->assertSessionHasNoErrors()->assertRedirect(route('register.check-email'));
        $this->get('/register/check-email')->assertOk();

        $user = User::firstOrFail();
        $this->assertSame('athlete@example.com', $user->email);
        $this->assertNotNull($user->age_confirmed_at);
        $this->assertNull($user->email_verified_at);
        $this->assertTrue(Hash::check('long-enough-password', $user->password));
        Notification::assertSentTo($user, VerifyEmail::class);
        Notification::assertNotSentTo($user, RegistrationAttempted::class);

        $row = (array) DB::table('users')->first();
        $this->assertArrayNotHasKey('date_of_birth', $row);
        foreach ($row as $column => $value) {
            $this->assertStringNotContainsString($dob, (string) $value, "Birth date found in users.$column");
        }
    }

    public function test_a_new_account_cannot_reach_private_pages_until_the_email_is_verified(): void
    {
        Notification::fake();
        $this->post('/register', $this->form());
        $user = User::firstOrFail();

        $this->post('/login', ['email' => $user->email, 'password' => 'long-enough-password']);
        $this->assertAuthenticatedAs($user);
        $this->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    public function test_signing_up_with_an_existing_email_looks_identical_and_changes_nothing(): void
    {
        Notification::fake();
        $existing = User::factory()->create(['email' => 'taken@example.com', 'name' => 'Original Name']);
        $originalHash = $existing->password;

        $fresh = $this->post('/register', $this->form(['email' => 'brand-new@example.com']));
        $taken = $this->post('/register', $this->form(['email' => 'taken@example.com', 'name' => 'Impostor']));

        // Same status, same destination, same session outcome: no way to tell them apart.
        $this->assertSame($fresh->getStatusCode(), $taken->getStatusCode());
        $this->assertSame($fresh->headers->get('Location'), $taken->headers->get('Location'));
        $taken->assertSessionHasNoErrors()->assertRedirect(route('register.check-email'));
        $this->assertSame($fresh->getContent(), $taken->getContent());
        $fresh->assertSessionHasNoErrors();
        $this->assertGuest();

        // No second account, and the real owner's details are untouched.
        $this->assertSame(1, User::where('email', 'taken@example.com')->count());
        $this->assertSame(2, User::count());
        $existing->refresh();
        $this->assertSame('Original Name', $existing->name);
        $this->assertSame($originalHash, $existing->password);
        $this->assertTrue(Hash::check('password', $existing->password));
        $this->assertFalse(Hash::check('long-enough-password', $existing->password));
        $this->assertNotNull($existing->email_verified_at);

        // The real owner is told someone tried; no verification link is sent for them.
        Notification::assertSentTo($existing, RegistrationAttempted::class);
        Notification::assertNotSentTo($existing, VerifyEmail::class);
        Notification::assertSentTo(User::where('email', 'brand-new@example.com')->firstOrFail(), VerifyEmail::class);
    }

    public function test_the_existing_email_check_ignores_letter_case(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'taken@example.com']);

        // Mixed case is refused by validation (the form lower-cases it), so no duplicate can sneak in.
        $this->post('/register', $this->form(['email' => 'Taken@Example.com']))->assertSessionHasErrors('email');
        $this->assertSame(1, User::count());
    }

    public function test_the_notice_to_an_existing_member_can_be_rendered_and_has_no_secrets(): void
    {
        $user = User::factory()->create();

        $mail = (new RegistrationAttempted)->toMail($user);
        $html = (string) $mail->render();

        $this->assertNotSame('', $html);
        $this->assertStringNotContainsString($user->password, $html);
    }

    public function test_someone_one_day_short_of_18_is_refused_and_nothing_is_stored(): void
    {
        $dob = now()->subYears(18)->addDay()->format('Y-m-d');

        $this->post('/register', $this->form(['date_of_birth' => $dob]))
            ->assertSessionHasErrors('date_of_birth');

        $this->assertGuest();
        $this->assertSame(0, User::count());
    }

    public function test_someone_who_turns_18_today_is_accepted(): void
    {
        Notification::fake();
        $this->post('/register', $this->form(['date_of_birth' => now()->subYears(18)->format('Y-m-d')]))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, User::count());
    }

    public function test_a_birth_date_is_required_and_must_be_a_real_past_date(): void
    {
        foreach ([null, '', 'yesterday', now()->addDay()->format('Y-m-d'), '1850-01-01'] as $bad) {
            $this->post('/register', $this->form(['date_of_birth' => $bad]))->assertSessionHasErrors('date_of_birth');
        }

        $this->assertSame(0, User::count());
    }

    public function test_terms_must_be_accepted(): void
    {
        $this->post('/register', $this->form(['terms' => false]))->assertSessionHasErrors('terms');

        $this->assertSame(0, User::count());
    }

    public function test_short_passwords_are_refused(): void
    {
        $this->post('/register', $this->form(['password' => 'short123', 'password_confirmation' => 'short123']))
            ->assertSessionHasErrors('password');
    }

    public function test_registration_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/register', $this->form(['terms' => false]));
        }

        $this->post('/register', $this->form(['terms' => false]))->assertStatus(429);
    }
}
