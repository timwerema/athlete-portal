<?php

namespace App\Http\Controllers;

use App\Mail\CoachMessageReceived;
use App\Models\Profile;
use App\Models\ShareLink;
use App\Services\ProfilePresenter;
use App\Services\ViewCounter;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class PublicProfileController extends Controller
{
    /** Seconds a person needs, at the very least, to fill in the contact form. */
    public const MINIMUM_FORM_SECONDS = 3;

    public function show(Request $request, string $token): Response
    {
        $link = ShareLink::findActive($token);

        if (! $link) {
            return $this->unavailable($request);
        }

        ViewCounter::record($link, $request);

        return $this->sealed(Inertia::render('share/show', [
            'profile' => ProfilePresenter::forCoach($link->profile, route('share.photo', ['token' => $token])),
            'mode' => $request->user()?->id === $link->profile->user_id ? 'owner' : 'coach',
            'contact' => [
                'action' => route('share.contact', ['token' => $token]),
                'started' => Crypt::encryptString((string) now()->timestamp),
            ],
        ])->toResponse($request));
    }

    public function photo(string $token): StreamedResponse
    {
        $link = ShareLink::findActive($token);

        abort_unless($link && $link->profile->shows('photo'), 404);

        return ProfileController::servePhoto($link->profile);
    }

    public function contact(Request $request, string $token): RedirectResponse
    {
        $link = ShareLink::findActive($token);

        abort_unless($link, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'school' => ['required', 'string', 'max:160'],
            'role' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'message' => ['required', 'string', 'min:20', 'max:3000'],
            'website' => ['nullable', 'string', 'max:0'], // bot trap: people never see this field
            'started' => ['required', 'string', 'max:500'],
        ], [
            'website.max' => 'We could not send that. Please reload the page and try again.',
            'message.min' => 'Please write a little more so the athlete knows why you are getting in touch.',
        ]);

        try {
            $started = (int) Crypt::decryptString($data['started']);
        } catch (DecryptException) {
            $started = 0;
        }

        $age = now()->timestamp - $started;

        if ($age < self::MINIMUM_FORM_SECONDS || $age > 86400) {
            throw ValidationException::withMessages([
                'message' => 'We could not send that. Please reload the page and try again.',
            ]);
        }

        $message = $link->profile->messages()->create([
            'share_link_id' => $link->id,
            'sender_name' => $data['name'],
            'sender_school' => $data['school'],
            'sender_role' => $data['role'],
            'sender_email' => $data['email'],
            'body' => $data['message'],
        ]);

        // The message is already in the athlete's inbox, so a mail problem must not lose it.
        try {
            Mail::to($link->profile->user)->send(new CoachMessageReceived($message));
        } catch (Throwable $e) {
            report($e);
        }

        return back()->with('status', 'contact-sent');
    }

    /** The one labelled sample profile, for a fictional athlete, created by the seeder. */
    public function demo(Request $request): Response
    {
        $profile = Profile::query()->where('is_demo', true)->first();

        abort_unless($profile, 404);

        return Inertia::render('share/show', [
            'profile' => ProfilePresenter::forCoach($profile, null),
            'mode' => 'demo',
            'contact' => null,
        ])->toResponse($request);
    }

    private function unavailable(Request $request): Response
    {
        return $this->sealed(
            Inertia::render('share/unavailable')->toResponse($request)->setStatusCode(404)
        );
    }

    private function sealed(Response $response): Response
    {
        $response->headers->set('Cache-Control', 'no-store, private');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow, noarchive');

        return $response;
    }
}
