<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Password::defaults(fn () => $this->app->environment('production')
            ? Password::min(10)->uncompromised()
            : Password::min(10));

        $byIp = fn (Request $request) => $request->ip();
        $byUser = fn (Request $request) => $request->user()?->id ?: $request->ip();

        RateLimiter::for('login', fn (Request $r) => Limit::perMinute(10)->by($byIp($r)));
        RateLimiter::for('register', fn (Request $r) => [
            Limit::perMinute(5)->by($byIp($r)),
            Limit::perDay(20)->by($byIp($r)),
        ]);
        RateLimiter::for('password-reset', fn (Request $r) => Limit::perMinute(5)->by($byIp($r)));
        RateLimiter::for('profile-write', fn (Request $r) => Limit::perMinute(60)->by($byUser($r)));
        RateLimiter::for('account-write', fn (Request $r) => Limit::perMinute(10)->by($byUser($r)));
        RateLimiter::for('uploads', fn (Request $r) => Limit::perHour(20)->by($byUser($r)));
        RateLimiter::for('export', fn (Request $r) => Limit::perHour(10)->by($byUser($r)));
        RateLimiter::for('share-view', fn (Request $r) => Limit::perMinute(60)->by($byIp($r)));

        // A coach may send a few messages; a script may not flood an athlete.
        RateLimiter::for('contact', fn (Request $r) => [
            Limit::perHour(5)->by('contact-ip:'.$byIp($r)),
            Limit::perDay(30)->by('contact-link:'.hash('sha256', (string) $r->route('token'))),
        ]);
    }
}
