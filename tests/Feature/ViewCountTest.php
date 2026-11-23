<?php

namespace Tests\Feature;

use App\Models\ProfileView;
use App\Services\ViewCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;

class ViewCountTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    public function test_views_are_counted_once_per_visitor_per_day(): void
    {
        $user = $this->athlete();
        $link = $this->linkFor($user);
        $url = '/p/'.$link->token();

        $this->asVisitor('203.0.113.10')->get($url)->assertOk();
        $this->assertSame(1, ProfileView::count());

        $this->asVisitor('203.0.113.10')->get($url);
        $this->asVisitor('203.0.113.10')->get($url);
        $this->assertSame(1, ProfileView::count());

        $this->asVisitor('203.0.113.99')->get($url);
        $this->assertSame(2, ProfileView::count());

        $this->travel(1)->days();
        $this->asVisitor('203.0.113.10')->get($url);
        $this->assertSame(3, ProfileView::count());

        $summary = ViewCounter::summary($link);
        $this->assertSame(3, $summary['total']);
        $this->assertSame(3, $summary['last_7_days']);
        $this->assertSame(now()->toDateString(), substr((string) $summary['last_viewed_on'], 0, 10));

        $this->travel(10)->days();
        $this->assertSame(0, ViewCounter::summary($link)['last_7_days']);
        $this->assertSame(3, ViewCounter::summary($link)['total']);
    }

    public function test_the_athletes_own_visits_are_not_counted(): void
    {
        $user = $this->athlete();
        $link = $this->linkFor($user);

        $response = $this->actingAs($user)->asVisitor()->get('/p/'.$link->token())->assertOk();

        $this->assertSame('owner', $this->props($response)['mode']);
        $this->assertSame(0, ProfileView::count());
    }

    public function test_another_signed_in_athlete_counts_as_a_visitor(): void
    {
        $link = $this->linkFor($this->athlete());

        $this->actingAs($this->athlete())->asVisitor()->get('/p/'.$link->token())->assertOk();

        $this->assertSame(1, ProfileView::count());
    }

    public function test_bots_previews_and_prefetches_are_not_counted(): void
    {
        $link = $this->linkFor($this->athlete());
        $url = '/p/'.$link->token();

        $agents = [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'facebookexternalhit/1.1',
            'WhatsApp/2.23.20 A',
            'Slackbot-LinkExpanding 1.0 (+https://api.slack.com/robots)',
            'Mozilla/5.0 (compatible; Discordbot/2.0; +https://discordapp.com)',
            'curl/8.5.0',
            'python-requests/2.31',
            '',
        ];

        foreach ($agents as $i => $agent) {
            $this->asVisitor("198.51.100.{$i}", $agent)->get($url)->assertOk();
        }

        $this->asVisitor('198.51.100.50')->withHeader('Sec-Purpose', 'prefetch;prerender')->get($url)->assertOk();
        $this->asVisitor('198.51.100.51')->withHeader('Purpose', 'prefetch')->get($url)->assertOk();

        $this->assertSame(0, ProfileView::count());
    }

    public function test_no_ip_address_is_stored_with_a_view(): void
    {
        $link = $this->linkFor($this->athlete());

        $this->asVisitor('203.0.113.77')->get('/p/'.$link->token());

        $row = json_encode(DB::table('profile_views')->first());
        $this->assertStringNotContainsString('203.0.113.77', $row);
        $this->assertStringNotContainsString('Safari', $row);
        $this->assertMatchesRegularExpression('/\A[a-f0-9]{64}\z/', DB::table('profile_views')->value('visitor_hash'));
    }

    public function test_views_of_a_missing_or_revoked_link_are_not_counted_and_counts_are_per_link(): void
    {
        $user = $this->athlete();
        $old = $this->linkFor($user);
        $this->asVisitor()->get('/p/'.$old->token());

        $this->actingAs($user)->post('/sharing/link');
        $this->post('/logout');

        $this->asVisitor()->get('/p/'.$old->token())->assertNotFound();
        $this->assertSame(1, ProfileView::count());

        // The dashboard shows the count for the current link, which nobody has opened yet.
        $page = $this->actingAs($user)->get('/dashboard');
        $this->assertSame(0, $this->props($page)['views']['total']);
    }

    public function test_the_athlete_sees_the_real_count(): void
    {
        $user = $this->athlete();
        $link = $this->linkFor($user);

        $this->asVisitor('203.0.113.1')->get('/p/'.$link->token());
        $this->asVisitor('203.0.113.2')->get('/p/'.$link->token());

        $this->assertSame(2, $this->props($this->actingAs($user)->get('/sharing'))['views']['total']);
        $this->assertSame(2, $this->props($this->get('/dashboard'))['views']['total']);
    }
}
