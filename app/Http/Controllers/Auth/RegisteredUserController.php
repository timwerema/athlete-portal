<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\RegistrationAttempted;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public const MINIMUM_AGE = 18;

    public function create(): Response
    {
        return Inertia::render('auth/register', ['minimumAge' => self::MINIMUM_AGE]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|string|lowercase|email|max:255',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'date_of_birth' => ['required', 'date_format:Y-m-d', 'before:today', 'after:1900-01-01'],
            'terms' => ['accepted'],
        ], [
            'terms.accepted' => 'Please confirm you have read the terms and privacy notice.',
        ]);

        // The birth date is used for this one check and then thrown away.
        if (Carbon::createFromFormat('Y-m-d', $request->string('date_of_birth'))->startOfDay()->age < self::MINIMUM_AGE) {
            throw ValidationException::withMessages([
                'date_of_birth' => 'Sorry, this service is only for athletes aged 18 or over right now, so we cannot create an account for you.',
            ]);
        }

        $password = Hash::make($request->password);
        $existing = User::query()->where('email', $request->email)->first();

        // The answer on screen is the same whether or not the email already has an
        // account, so sign-up cannot be used to check if someone is a member.
        if ($existing) {
            $existing->notify(new RegistrationAttempted);
        } else {
            $user = new User(['name' => $request->name, 'email' => $request->email]);
            $user->password = $password;
            $user->age_confirmed_at = now();
            $user->save();

            event(new Registered($user));
        }

        return to_route('register.check-email');
    }

    public function checkEmail(): Response
    {
        return Inertia::render('auth/check-email');
    }
}
