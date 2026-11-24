<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PhotoStorage;
use App\Services\ProfilePresenter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class DataController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('settings/data');
    }

    /** A zip with everything we hold about the athlete: data.json and their photo. */
    public function export(Request $request): BinaryFileResponse
    {
        $user = $request->user();
        $path = tempnam(sys_get_temp_dir(), 'export');

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $zip->addFromString('data.json', json_encode(self::everything($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $photo = $user->profile?->photo_path;
        if ($photo && Storage::disk('local')->exists($photo)) {
            $zip->addFromString('photo.jpg', Storage::disk('local')->get($photo));
        }
        $zip->close();

        return response()->download($path, 'my-data-'.now()->toDateString().'.zip', [
            'Content-Type' => 'application/zip',
            'Cache-Control' => 'no-store, private',
        ])->deleteFileAfterSend();
    }

    public static function everything(User $user): array
    {
        $profile = $user->profileOrCreate();
        $owner = ProfilePresenter::forOwner($profile);
        unset($owner['has_photo'], $owner['photo_version']);

        return [
            'exported_at' => now()->toIso8601String(),
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'email_change_waiting_for_confirmation' => $user->pending_email,
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'confirmed_18_or_over_at' => $user->age_confirmed_at?->toIso8601String(),
                'two_step_login' => $user->hasTwoFactorEnabled(),
                'created_at' => $user->created_at?->toIso8601String(),
            ],
            'profile' => $owner,
            'share_links' => $profile->shareLinks()->orderBy('id')->get()->map(fn ($link) => [
                'created_at' => $link->created_at?->toIso8601String(),
                'revoked_at' => $link->revoked_at?->toIso8601String(),
                'url' => $link->revoked_at ? null : $link->url(),
                'unique_daily_visits' => $link->views()->count(),
                'visit_days' => $link->views()->orderBy('viewed_on')->pluck('viewed_on')->countBy()->all(),
            ])->all(),
            'messages_received' => $profile->messages()->get()->map(fn ($m) => [
                'received_at' => $m->created_at?->toIso8601String(),
                'read_at' => $m->read_at?->toIso8601String(),
                'sender_name' => $m->sender_name,
                'sender_school' => $m->sender_school,
                'sender_role' => $m->sender_role,
                'sender_email' => $m->sender_email,
                'message' => $m->body,
            ])->all(),
        ];
    }

    /** Delete the account and everything attached to it. This cannot be undone. */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();

        Auth::logout();

        if ($user->profile) {
            PhotoStorage::remove($user->profile);
        }

        DB::transaction(function () use ($user) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
            $user->delete(); // profile, lists, links, view counts and messages go with it
        });

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Your account and all of your data have been deleted.');
    }
}
