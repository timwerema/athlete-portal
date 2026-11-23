<?php

namespace Tests\Unit;

use App\Services\VideoEmbed;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class VideoEmbedTest extends TestCase
{
    private const YT = 'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ';

    public static function embeddable(): array
    {
        return [
            'youtube watch' => ['https://www.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube', self::YT],
            'youtube watch with extras' => ['https://www.youtube.com/watch?t=30&v=dQw4w9WgXcQ&list=PL123', 'youtube', self::YT],
            'youtube no www' => ['https://youtube.com/watch?v=dQw4w9WgXcQ', 'youtube', self::YT],
            'youtube mobile' => ['https://m.youtube.com/watch?v=dQw4w9WgXcQ', 'youtube', self::YT],
            'youtu.be' => ['https://youtu.be/dQw4w9WgXcQ', 'youtube', self::YT],
            'youtu.be with query' => ['https://youtu.be/dQw4w9WgXcQ?si=abc', 'youtube', self::YT],
            'shorts' => ['https://www.youtube.com/shorts/dQw4w9WgXcQ', 'youtube', self::YT],
            'embed' => ['https://www.youtube.com/embed/dQw4w9WgXcQ', 'youtube', self::YT],
            'upper-case host' => ['https://WWW.YOUTUBE.COM/watch?v=dQw4w9WgXcQ', 'youtube', self::YT],
            'vimeo' => ['https://vimeo.com/123456789', 'vimeo', 'https://player.vimeo.com/video/123456789'],
            'vimeo unlisted' => ['https://vimeo.com/123456789/abcdef0123', 'vimeo', 'https://player.vimeo.com/video/123456789?h=abcdef0123'],
            'vimeo player' => ['https://player.vimeo.com/video/123456789?h=abcdef0123', 'vimeo', 'https://player.vimeo.com/video/123456789?h=abcdef0123'],
        ];
    }

    #[DataProvider('embeddable')]
    public function test_allow_listed_videos_get_an_embed_address_built_from_the_id(string $url, string $provider, string $embed): void
    {
        $result = VideoEmbed::resolve($url);

        $this->assertSame($provider, $result['provider']);
        $this->assertSame($embed, $result['embed_url']);
    }

    public static function recognisedButNotEmbedded(): array
    {
        return [
            'youtube bad id' => ['https://www.youtube.com/watch?v=short', 'youtube'],
            'youtube id with markup' => ['https://www.youtube.com/watch?v=dQw4w9WgXc"><script>', 'youtube'],
            'youtube id too long' => ['https://youtu.be/dQw4w9WgXcQQQ', 'youtube'],
            'youtube array id' => ['https://www.youtube.com/watch?v[]=dQw4w9WgXcQ', 'youtube'],
            'youtube channel' => ['https://www.youtube.com/@somechannel', 'youtube'],
            'youtube embed path trick' => ['https://www.youtube.com/embed/..%2F..%2Fevil', 'youtube'],
            'vimeo channel' => ['https://vimeo.com/channels/staffpicks', 'vimeo'],
            'vimeo bad hash' => ['https://vimeo.com/123456789/"onload=x', 'vimeo'],
            'hudl' => ['https://www.hudl.com/video/3/12345/abcdef', 'hudl'],
            'hudl subdomain' => ['https://fan.hudl.com/whatever', 'hudl'],
            'hudl embed' => ['https://www.hudl.com/embed/video/3/12345/abcdef', 'hudl'],
        ];
    }

    #[DataProvider('recognisedButNotEmbedded')]
    public function test_recognised_hosts_without_a_valid_id_are_shown_as_links(string $url, string $provider): void
    {
        $result = VideoEmbed::resolve($url);

        $this->assertSame($provider, $result['provider']);
        $this->assertNull($result['embed_url']);
    }

    public static function neverTrusted(): array
    {
        return [
            'other host' => ['https://example.com/video/123456789'],
            'lookalike suffix' => ['https://youtube.com.evil.example/watch?v=dQw4w9WgXcQ'],
            'lookalike prefix' => ['https://notyoutube.com/watch?v=dQw4w9WgXcQ'],
            'lookalike hudl' => ['https://evilhudl.com/video/1'],
            'lookalike subdomain' => ['https://youtu.be.evil.example/dQw4w9WgXcQ'],
            'youtube in query' => ['https://evil.example/?youtube.com/watch?v=dQw4w9WgXcQ'],
            'youtube in path' => ['https://evil.example/youtube.com/watch?v=dQw4w9WgXcQ'],
            'youtube in fragment' => ['https://evil.example/#https://youtu.be/dQw4w9WgXcQ'],
            'userinfo trick' => ['https://youtube.com@evil.example/watch?v=dQw4w9WgXcQ'],
            'userinfo trick 2' => ['https://www.youtube.com:pass@evil.example/embed/dQw4w9WgXcQ'],
            'http youtube' => ['http://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'http vimeo' => ['http://vimeo.com/123456789'],
            'protocol-relative' => ['//www.youtube.com/watch?v=dQw4w9WgXcQ'],
            'javascript' => ['javascript:alert(1)//youtube.com/watch?v=dQw4w9WgXcQ'],
            'data' => ['data:text/html,<script>alert(1)</script>'],
            'empty' => [''],
            'garbage' => ['not a link at all'],
            'malformed' => ['https:///'],
        ];
    }

    #[DataProvider('neverTrusted')]
    public function test_everything_else_is_a_plain_link_and_never_embedded(string $url): void
    {
        $result = VideoEmbed::resolve($url);

        $this->assertSame('link', $result['provider']);
        $this->assertNull($result['embed_url']);
    }

    public function test_every_embed_address_points_at_an_allowed_frame_source(): void
    {
        foreach (self::embeddable() as [$url]) {
            $embed = VideoEmbed::resolve($url)['embed_url'];

            $this->assertMatchesRegularExpression(
                '#\Ahttps://(www\.youtube-nocookie\.com/embed/[A-Za-z0-9_-]{11}|player\.vimeo\.com/video/\d+(\?h=[a-f0-9]+)?)\z#',
                $embed
            );
        }
    }
}
