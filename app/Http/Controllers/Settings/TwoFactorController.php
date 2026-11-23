<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorController extends Controller
{
    public function edit(Request $request, TwoFactor $twoFactor): Response
    {
        $user = $request->user();
        $pending = ! $user->hasTwoFactorEnabled() && $user->two_factor_secret;

        return Inertia::render('settings/two-factor', [
            'enabled' => $user->hasTwoFactorEnabled(),
            'setup' => $pending ? [
                'qr' => $twoFactor->qrCodeDataUri($user, $user->two_factor_secret),
                'secret' => $user->two_factor_secret,
            ] : null,
            // Recovery codes are shown once, straight after switching on or making new ones.
            'recoveryCodes' => $request->session()->get('recovery_codes'),
            'recoveryCodesLeft' => $user->hasTwoFactorEnabled() ? count($user->two_factor_recovery_codes ?? []) : null,
        ]);
    }

    /** Step one: check the password and prepare a secret to scan. */
    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            $user->forceFill([
                'two_factor_secret' => $twoFactor->newSecret(),
                'two_factor_confirmed_at' => null,
                'two_factor_last_step' => null,
            ])->save();
        }

        return back();
    }

    /** Step two: prove the authenticator app works, then switch it on. */
    public function confirm(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'max:20']]);

        $user = $request->user();

        if ($user->hasTwoFactorEnabled() || ! $user->two_factor_secret) {
            return back();
        }

        if (! $twoFactor->verify($user, $user->two_factor_secret, (string) $request->string('code'))) {
            throw ValidationException::withMessages(['code' => 'That code is not right. Check the time on your phone and try again.']);
        }

        $codes = $twoFactor->newRecoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();

        return back()->with('recovery_codes', $codes)->with('status', 'Two-step login is on.');
    }

    public function recoveryCodes(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        abort_unless($request->user()->hasTwoFactorEnabled(), 404);

        $codes = $twoFactor->newRecoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return back()->with('recovery_codes', $codes)->with('status', 'New recovery codes made. The old ones no longer work.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_last_step' => null,
        ])->save();

        return back()->with('status', 'Two-step login is off.');
    }
}
