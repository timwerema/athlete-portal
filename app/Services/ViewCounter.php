<?php

namespace App\Services;

use App\Models\ProfileView;
use App\Models\ShareLink;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Counts visits to a shared link honestly:
 *  - the athlete's own visits are not counted,
 *  - obvious bots and link-preview fetchers are not counted,
 *  - the same visitor on the same day counts once.
 * No IP address is stored, only a keyed hash that changes every day.
 */
class ViewCounter
{
    private const BOT_PATTERN = '/bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|slack|discord|telegram|skype|embedly|curl|wget|python|headless|monitor|lighthouse/i';

    public static function record(ShareLink $link, Request $request): bool
    {
        if ($request->user()?->id === $link->profile->user_id) {
            return false;
        }

        $agent = (string) $request->userAgent();

        if ($agent === '' || preg_match(self::BOT_PATTERN, $agent)) {
            return false;
        }

        if ($request->headers->has('Purpose') || str_contains((string) $request->header('Sec-Purpose'), 'prefetch')) {
            return false;
        }

        $day = now()->toDateString();
        $hash = hash_hmac('sha256', implode('|', [$link->id, $day, $request->ip(), $agent]), (string) config('app.key'));

        return ProfileView::query()->insertOrIgnore([
            'share_link_id' => $link->id,
            'viewed_on' => $day,
            'visitor_hash' => $hash,
            'created_at' => now(),
        ]) > 0;
    }

    /** @return array{total: int, last_7_days: int, last_viewed_on: ?string} */
    public static function summary(?ShareLink $link): array
    {
        if (! $link) {
            return ['total' => 0, 'last_7_days' => 0, 'last_viewed_on' => null];
        }

        $views = DB::table('profile_views')->where('share_link_id', $link->id);

        return [
            'total' => (clone $views)->count(),
            'last_7_days' => (clone $views)->where('viewed_on', '>=', now()->subDays(6)->toDateString())->count(),
            'last_viewed_on' => (clone $views)->max('viewed_on'),
        ];
    }
}
