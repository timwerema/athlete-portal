<?php

namespace App\Services;

/**
 * Decides whether a video link may be shown as an embedded player.
 * Only hosts on the allow-list are embedded, and the embed address is built
 * here from a validated ID, never copied from what the athlete typed.
 */
class VideoEmbed
{
    /** @return array{provider: string, label: string, embed_url: ?string} */
    public static function resolve(string $url): array
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $host = preg_replace('/^(www|m)\./', '', $host);
        $path = $parts['path'] ?? '';
        parse_str($parts['query'] ?? '', $query);

        if (($parts['scheme'] ?? '') !== 'https') {
            return self::plain($host);
        }

        if (in_array($host, ['youtube.com', 'youtube-nocookie.com', 'youtu.be'], true)) {
            $id = match (true) {
                $host === 'youtu.be' => ltrim($path, '/'),
                $path === '/watch' => is_string($query['v'] ?? null) ? $query['v'] : '',
                (bool) preg_match('#^/(embed|shorts|live)/([^/]+)#', $path, $m) => $m[2],
                default => '',
            };

            return [
                'provider' => 'youtube',
                'label' => 'YouTube',
                'embed_url' => preg_match('/\A[A-Za-z0-9_-]{11}\z/', $id)
                    ? "https://www.youtube-nocookie.com/embed/{$id}"
                    : null,
            ];
        }

        if (in_array($host, ['vimeo.com', 'player.vimeo.com'], true)) {
            $embed = null;
            if (preg_match('#^/(?:video/)?(\d{6,12})(?:/([a-f0-9]{8,16}))?/?$#', $path, $m)) {
                $hash = $m[2] ?? (is_string($query['h'] ?? null) && preg_match('/\A[a-f0-9]{8,16}\z/', $query['h']) ? $query['h'] : null);
                $embed = "https://player.vimeo.com/video/{$m[1]}".($hash ? "?h={$hash}" : '');
            }

            return ['provider' => 'vimeo', 'label' => 'Vimeo', 'embed_url' => $embed];
        }

        if ($host === 'hudl.com' || str_ends_with($host, '.hudl.com')) {
            // Hudl is recognised, but shown as a link: we have not verified an embed
            // address that works for every Hudl video, so we do not guess one.
            return ['provider' => 'hudl', 'label' => 'Hudl', 'embed_url' => null];
        }

        return self::plain($host);
    }

    private static function plain(string $host): array
    {
        return ['provider' => 'link', 'label' => $host !== '' ? $host : 'Link', 'embed_url' => null];
    }
}
