<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ConfirmEmailChange;
use App\Notifications\EmailChanged;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public const EMAIL_NOTICE = 'If that address can be used, we have sent a confirmation link to it. Your email stays as it is until you open the link.';

    public function edit(Request $request): Response
    {
        return Inertia::render('settings/account', [
            'pendingEmail' => $request->user()->pending_email,
        ]);
    }

    /**
     * Save the name straight away. A new email is only a request: it takes
     * effect after the athlete opens a link sent to that address. The answer on
     * screen is the same whether or not the address belongs to another account.
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
        ]);

        $emailChanging = $data['email'] !== $user->email;

        if ($emailChanging) {
            $request->validate(['current_password' => ['required', 'current_password']], [
                'current_password.required' => 'Enter your password to change your email address.',
            ]);
        }

        $user->forceFill(['name' => $data['name']]);

        if (! $emailChanging) {
            $user->save();

            return to_route('account.edit')->with('status', 'Saved.');
        }

        $user->forceFill(['pending_email' => $data['email']])->save();

        if (! User::query()->where('email', $data['email'])->exists()) {
            $url = URL::temporarySignedRoute('account.email.confirm', now()->addMinutes(60), [
                'id' => $user->id,
                'hash' => sha1($data['email']),
            ]);

            Notification::route('mail', $data['email'])->notify(new ConfirmEmailChange($url));
        }

        return to_route('account.edit')->with('status', self::EMAIL_NOTICE);
    }

    public function confirmEmail(Request $request, int $id, string $hash): RedirectResponse
    {
        $user = $request->user();
        $pending = $user->pending_email;

        $valid = $user->id === $id
            && $pending !== null
            && hash_equals(sha1($pending), $hash)
            && ! User::query()->where('email', $pending)->exists();

        if (! $valid) {
            return to_route('account.edit')->with('status', 'That link is no longer valid. Nothing was changed.');
        }

        $old = $user->email;

        $user->forceFill([
            'email' => $pending,
            'pending_email' => null,
            'email_verified_at' => now(), // opening the link proved the new address is theirs
        ])->save();

        Notification::route('mail', $old)->notify(new EmailChanged);

        return to_route('account.edit')->with('status', 'Your email address has been changed.');
    }

    public function cancelEmail(Request $request): RedirectResponse
    {
        $request->user()->forceFill(['pending_email' => null])->save();

        return to_route('account.edit')->with('status', 'Email change cancelled.');
    }
}
