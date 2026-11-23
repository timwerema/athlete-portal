<?php

namespace Tests\Feature;

use App\Http\Middleware\SecurityHeaders;
use App\Services\VideoEmbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    /** @return array<string, string> directive => value */
    private function csp(TestResponse $response): array
    {
        $header = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($header, 'No Content-Security-Policy header.');

        $directives = [];
        foreach (explode(';', $header) as $part) {
            [$name, $value] = array_pad(explode(' ', trim($part), 2), 2, '');
            $directives[$name] = $value;
        }

        return $directives;
    }

    private function assertHardened(TestResponse $response): void
    {
        $csp = $this->csp($response);

        $this->assertMatchesRegularExpression("/'nonce-[A-Za-z0-9+\/=_-]{16,}'/", $csp['script-src']);
        $this->assertStringNotContainsString('unsafe-inline', $csp['script-src']);
        $this->assertStringNotContainsString('unsafe-eval', $csp['script-src']);
        $this->assertStringNotContainsString('*', $csp['script-src']);
        $this->assertStringNotContainsString('http', $csp['script-src']);
        $this->assertStringNotContainsString('unsafe-inline', $csp['style-src']);
        $this->assertSame("'self'", $csp['default-src']);
        $this->assertSame("'none'", $csp['frame-ancestors']);
        $this->assertSame("'none'", $csp['object-src']);
        $this->assertSame("'self'", $csp['base-uri']);
        $this->assertSame("'self'", $csp['form-action']);
        $this->assertSame("'self'", $csp['connect-src']);
        $this->assertSame("'self'", $csp['font-src']);
        $this->assertSame('https://www.youtube-nocookie.com https://player.vimeo.com', $csp['frame-src']);

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $this->assertStringContainsString('geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertStringContainsString('camera=()', $response->headers->get('Permissions-Policy'));
    }

    public function test_public_pages_send_the_security_headers(): void
    {
        foreach (['/', '/login', '/register', '/privacy', '/terms', '/forgot-password'] as $page) {
            $this->assertHardened($this->get($page)->assertOk());
        }
    }

    public function test_signed_in_pages_send_the_security_headers(): void
    {
        $this->actingAs($this->athlete());

        foreach (['/dashboard', '/profile', '/sharing', '/inbox', '/settings/data'] as $page) {
            $this->assertHardened($this->get($page)->assertOk());
        }
    }

    public function test_the_share_page_is_private_uncached_and_not_indexed(): void
    {
        $link = $this->linkFor($this->athlete());

        $response = $this->get('/p/'.$link->token())->assertOk();

        $this->assertHardened($response);
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('private', $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('noindex', $response->headers->get('X-Robots-Tag'));
        $this->assertStringContainsString('noindex', $response->getContent());
    }

    public function test_error_pages_and_redirects_send_the_security_headers_too(): void
    {
        $missing = $this->get('/no-such-page')->assertNotFound();
        $this->assertHardened($missing);
        $this->assertSame('error', $missing->viewData('page')['component']);

        $dead = $this->get('/p/'.str_repeat('z', 43))->assertNotFound();
        $this->assertHardened($dead);
        $this->assertStringContainsString('no-store', $dead->headers->get('Cache-Control'));
        $this->assertStringContainsString('noindex', $dead->headers->get('X-Robots-Tag'));

        $this->assertHardened($this->get('/dashboard')->assertRedirect('/login'));
    }

    public function test_error_pages_do_not_leak_technical_details(): void
    {
        $response = $this->get('/no-such-page');

        $this->assertStringNotContainsString('NotFoundHttpException', $response->getContent());
        $this->assertStringNotContainsString('vendor/laravel', $response->getContent());
        $this->assertStringNotContainsString(base_path(), $response->getContent());
    }

    public function test_the_nonce_changes_on_every_request_and_matches_the_page(): void
    {
        $first = $this->get('/');
        $second = $this->get('/');

        preg_match("/'nonce-([^']+)'/", $this->csp($first)['script-src'], $a);
        preg_match("/'nonce-([^']+)'/", $this->csp($second)['script-src'], $b);

        $this->assertNotSame($a[1], $b[1]);
        $this->assertStringContainsString('nonce="'.$a[1].'"', $first->getContent());

        // Every script tag on the page carries the nonce.
        preg_match_all('/<script\b[^>]*>/', $first->getContent(), $scripts);
        $this->assertNotEmpty($scripts[0]);
        foreach ($scripts[0] as $tag) {
            $this->assertStringContainsString('nonce="'.$a[1].'"', $tag);
        }
    }

    public function test_hsts_is_only_sent_over_https_in_production(): void
    {
        $this->assertFalse($this->get('/')->headers->has('Strict-Transport-Security'));

        $this->app->detectEnvironment(fn () => 'production');
        $secure = $this->get('https://localhost/');

        $this->assertStringContainsString('max-age=', (string) $secure->headers->get('Strict-Transport-Security'));
    }

    public function test_every_embed_host_is_in_the_frame_allow_list_and_nothing_else_is(): void
    {
        foreach (['https://youtu.be/dQw4w9WgXcQ', 'https://vimeo.com/123456789'] as $url) {
            $embed = VideoEmbed::resolve($url)['embed_url'];
            $origin = parse_url($embed, PHP_URL_SCHEME).'://'.parse_url($embed, PHP_URL_HOST);

            $this->assertContains($origin, SecurityHeaders::FRAME_SOURCES);
        }

        $this->assertCount(2, SecurityHeaders::FRAME_SOURCES);
    }

    public function test_session_cookie_is_http_only_and_same_site(): void
    {
        $this->assertTrue(config('session.http_only'));
        $this->assertContains(config('session.same_site'), ['lax', 'strict']);
    }

    public function test_private_storage_is_not_served_by_a_built_in_route(): void
    {
        $this->assertFalse(config('filesystems.disks.local.serve'));
    }
}
