<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function userWithTwoFactor(array $codes = ['aaaaa-bbbbb', 'ccccc-ddddd']): User
    {
        $user = User::factory()->create();
        $user->forceFill([
            'two_factor_secret' => (new Google2FA)->generateSecretKey(32),
            'two_factor_recovery_codes' => $codes,
            'two_factor_confirmed_at' => now(),
        ])->save();

        return $user;
    }

    private function code(User $user): string
    {
        return (new Google2FA)->getCurrentOtp($user->fresh()->two_factor_secret);
    }

    private function passwordStep(User $user)
    {
        return $this->post('/login', ['email' => $user->email, 'password' => 'password']);
    }

    public function test_password_alone_does_not_sign_in_an_account_with_two_step_login(): void
    {
        $user = $this->userWithTwoFactor();

        $this->passwordStep($user)->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();

        $this->get('/two-factor-challenge')->assertOk();
        $this->get('/dashboard')->assertRedirect(route('login'));
    }

    public function test_challenge_page_without_a_password_step_goes_back_to_login(): void
    {
        $this->get('/two-factor-challenge')->assertRedirect(route('login'));
        $this->post('/two-factor-challenge', ['code' => '123456'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_correct_code_signs_in(): void
    {
        $user = $this->userWithTwoFactor();
        $this->passwordStep($user);

        $this->post('/two-factor-challenge', ['code' => $this->code($user)])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_code_is_refused(): void
    {
        $user = $this->userWithTwoFactor();
        $this->passwordStep($user);

        $this->post('/two-factor-challenge', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->post('/two-factor-challenge', ['code' => 'abcdef'])->assertSessionHasErrors('code');
        $this->post('/two-factor-challenge', [])->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $user = $this->userWithTwoFactor();
        $code = $this->code($user);

        $this->passwordStep($user);
        $this->post('/two-factor-challenge', ['code' => $code]);
        $this->assertAuthenticatedAs($user);

        $this->post('/logout');
        $this->assertGuest();

        $this->passwordStep($user);
        $this->post('/two-factor-challenge', ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_a_recovery_code_works_once(): void
    {
        $user = $this->userWithTwoFactor();

        $this->passwordStep($user);
        $this->post('/two-factor-challenge', ['recovery_code' => 'AAAAA-BBBBB '])->assertSessionHasNoErrors();
        $this->assertAuthenticatedAs($user);
        $this->assertSame(['ccccc-ddddd'], $user->fresh()->two_factor_recovery_codes);

        $this->post('/logout');
        $this->passwordStep($user);
        $this->post('/two-factor-challenge', ['recovery_code' => 'aaaaa-bbbbb'])->assertSessionHasErrors('recovery_code');
        $this->assertGuest();
    }

    public function test_challenge_is_rate_limited_after_five_failures(): void
    {
        $user = $this->userWithTwoFactor();
        $this->passwordStep($user);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/two-factor-challenge', ['code' => '000000']);
        }

        // A correct code is refused while the limit is active.
        $this->post('/two-factor-challenge', ['code' => $this->code($user)])->assertSessionHasErrors('code');
        $this->assertStringContainsString('Too many attempts', session('errors')->first('code'));
        $this->assertGuest();
    }

    public function test_pending_challenge_expires(): void
    {
        $user = $this->userWithTwoFactor();
        $this->passwordStep($user);

        $this->travel(11)->minutes();

        $this->post('/two-factor-challenge', ['recovery_code' => 'aaaaa-bbbbb'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_switching_on_needs_the_password_then_a_working_code(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post('/settings/two-factor', ['password' => 'nope'])->assertSessionHasErrors('password');
        $this->assertNull($user->fresh()->two_factor_secret);

        $this->post('/settings/two-factor', ['password' => 'password'])->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $this->get('/settings/two-factor')->assertOk();

        $this->post('/settings/two-factor/confirm', ['code' => '000000'])->assertSessionHasErrors('code');
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());

        $response = $this->post('/settings/two-factor/confirm', ['code' => $this->code($user)]);
        $response->assertSessionHasNoErrors()->assertSessionHas('recovery_codes');

        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());
        $this->assertCount(8, session('recovery_codes'));
        $this->assertSame(session('recovery_codes'), $user->fresh()->two_factor_recovery_codes);
    }

    public function test_secret_and_recovery_codes_are_encrypted_in_the_database(): void
    {
        $user = $this->userWithTwoFactor();
        $raw = \DB::table('users')->where('id', $user->id)->first();

        $this->assertStringNotContainsString($user->two_factor_secret, $raw->two_factor_secret);
        $this->assertStringNotContainsString('aaaaa-bbbbb', $raw->two_factor_recovery_codes);
    }

    public function test_new_recovery_codes_and_switching_off_need_the_password(): void
    {
        $user = $this->userWithTwoFactor();
        $this->actingAs($user);

        $this->post('/settings/two-factor/recovery-codes', ['password' => 'nope'])->assertSessionHasErrors('password');
        $this->assertSame(['aaaaa-bbbbb', 'ccccc-ddddd'], $user->fresh()->two_factor_recovery_codes);

        $this->post('/settings/two-factor/recovery-codes', ['password' => 'password'])->assertSessionHas('recovery_codes');
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);

        $this->delete('/settings/two-factor', ['password' => 'nope'])->assertSessionHasErrors('password');
        $this->assertTrue($user->fresh()->hasTwoFactorEnabled());

        $this->delete('/settings/two-factor', ['password' => 'password'])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->hasTwoFactorEnabled());
        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_two_factor_secrets_are_never_sent_to_the_browser_in_shared_props(): void
    {
        $user = $this->userWithTwoFactor();

        $page = $this->actingAs($user)->get('/settings/account')->viewData('page');

        $json = json_encode($page);
        $this->assertStringNotContainsString($user->two_factor_secret, $json);
        $this->assertStringNotContainsString('aaaaa-bbbbb', $json);
        $this->assertStringNotContainsString('two_factor', json_encode($page['props']['auth']));
    }
}
