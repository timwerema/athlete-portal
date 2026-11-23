<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TwoFactor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorChallengeController extends Controller
{
    public function create(Request $request): Response|RedirectResponse
    {
        if (! $this->pendingUser($request)) {
            return to_route('login');
        }

        return Inertia::render('auth/two-factor-challenge');
    }

    public function store(Request $request, TwoFactor $twoFactor): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string', 'max:20'],
            'recovery_code' => ['nullable', 'string', 'max:40'],
        ]);

        $user = $this->pendingUser($request);

        if (! $user) {
            return to_route('login');
        }

        $key = 'two-factor:'.$user->getKey();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Please wait '.RateLimiter::availableIn($key).' seconds and try again.',
            ]);
        }

        $passed = $request->filled('recovery_code')
            ? $twoFactor->useRecoveryCode($user, $request->string('recovery_code'))
            : $twoFactor->verify($user, $user->two_factor_secret, (string) $request->string('code'));

        if (! $passed) {
            RateLimiter::hit($key, 300);

            throw ValidationException::withMessages([
                $request->filled('recovery_code') ? 'recovery_code' : 'code' => 'That code is not right. Please try again.',
            ]);
        }

        RateLimiter::clear($key);
        $remember = (bool) ($request->session()->pull('two_factor')['remember'] ?? false);

        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    private function pendingUser(Request $request): ?User
    {
        $pending = $request->session()->get('two_factor');

        if (! is_array($pending) || ($pending['expires'] ?? 0) < now()->timestamp) {
            $request->session()->forget('two_factor');

            return null;
        }

        return User::find($pending['id']);
    }
}
