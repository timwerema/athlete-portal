<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /** The only places an embedded video player may come from. */
    public const FRAME_SOURCES = ['https://www.youtube-nocookie.com', 'https://player.vimeo.com'];

    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Vite::useCspNonce();

        $response = $next($request);

        $script = ["'self'", "'nonce-{$nonce}'"];
        $style = ["'self'", "'nonce-{$nonce}'"];
        $connect = ["'self'"];

        if (app()->environment('local') && Vite::isRunningHot()) {
            // The Vite dev server, only while developing on this machine.
            $dev = rtrim(file_get_contents(public_path('hot')));
            $script[] = $dev;
            $style[] = $dev;
            $style[] = "'unsafe-inline'";
            $connect[] = $dev;
            $connect[] = str_replace('http', 'ws', $dev);
            $style = array_values(array_filter($style, fn ($s) => ! str_starts_with($s, "'nonce-")));
        }

        $policy = [
            "default-src 'self'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-ancestors 'none'",
            "object-src 'none'",
            'script-src '.implode(' ', $script),
            'style-src '.implode(' ', $style),
            "style-src-attr 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self'",
            'connect-src '.implode(' ', $connect),
            'frame-src '.implode(' ', self::FRAME_SOURCES),
            "manifest-src 'self'",
        ];

        $headers = [
            'Content-Security-Policy' => implode('; ', $policy),
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            // The share link is a secret in the address, so never pass it on to other sites.
            'Referrer-Policy' => 'no-referrer',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), browsing-topics=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        if ($request->isSecure() && app()->environment('production')) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000';
        }

        foreach ($headers as $name => $value) {
            $response->headers->set($name, $value);
        }

        return $response;
    }
}
