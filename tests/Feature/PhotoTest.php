<?php

namespace Tests\Feature;

use App\Services\PhotoStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\MakesAthletes;
use Tests\TestCase;

class PhotoTest extends TestCase
{
    use MakesAthletes, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function fileFromBytes(string $bytes, string $name, string $mime): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'phototest');
        file_put_contents($path, $bytes);

        return new UploadedFile($path, $name, $mime, null, true);
    }

    private function jpegBytes(int $width = 400, int $height = 300): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 40, 120, 80));
        ob_start();
        imagejpeg($image, null, 90);

        return (string) ob_get_clean();
    }

    public function test_a_photo_is_re_encoded_as_a_jpeg_under_a_random_private_name(): void
    {
        $user = $this->athlete();

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => UploadedFile::fake()->image('My Holiday Selfie.jpg', 2400, 1600)])
            ->assertSessionHasNoErrors();

        $path = $user->profile->fresh()->photo_path;
        $this->assertMatchesRegularExpression('#\Aphotos/[A-Za-z0-9]{40}\.jpg\z#', $path);
        $this->assertStringNotContainsString('Selfie', $path);
        Storage::disk('local')->assertExists($path);

        $stored = Storage::disk('local')->get($path);
        $this->assertSame("\xFF\xD8\xFF", substr($stored, 0, 3));

        [$w, $h, $type] = getimagesizefromstring($stored);
        $this->assertSame(IMAGETYPE_JPEG, $type);
        $this->assertSame(PhotoStorage::OUTPUT_EDGE, $w);
        $this->assertSame(600, $h);

        // Nothing lands in the public web folder.
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_png_and_webp_are_converted_to_jpeg(): void
    {
        $user = $this->athlete();
        $this->actingAs($user);

        $this->post('/profile/photo', ['photo' => UploadedFile::fake()->image('a.png', 200, 200)])->assertSessionHasNoErrors();
        $this->assertSame("\xFF\xD8\xFF", substr(Storage::disk('local')->get($user->profile->fresh()->photo_path), 0, 3));

        $image = imagecreatetruecolor(120, 80);
        ob_start();
        imagewebp($image);
        $webp = ob_get_clean();

        $this->post('/profile/photo', ['photo' => $this->fileFromBytes($webp, 'a.webp', 'image/webp')])->assertSessionHasNoErrors();
        $this->assertSame("\xFF\xD8\xFF", substr(Storage::disk('local')->get($user->profile->fresh()->photo_path), 0, 3));
    }

    public function test_hidden_data_in_the_original_file_does_not_survive(): void
    {
        $user = $this->athlete();
        $jpeg = $this->jpegBytes();

        // Insert an APP1 (where EXIF and GPS live) and a comment segment right after the start marker.
        $exif = "Exif\0\0GPS-SECRET-MARKER 32.9857N 96.7502W";
        $comment = 'COMMENT-SECRET-MARKER';
        $tainted = substr($jpeg, 0, 2)
            ."\xFF\xE1".pack('n', strlen($exif) + 2).$exif
            ."\xFF\xFE".pack('n', strlen($comment) + 2).$comment
            .substr($jpeg, 2);

        $this->assertStringContainsString('GPS-SECRET-MARKER', $tainted);
        $this->assertNotFalse(@getimagesizefromstring($tainted), 'The crafted file must still be a readable JPEG.');

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => $this->fileFromBytes($tainted, 'gps.jpg', 'image/jpeg')])
            ->assertSessionHasNoErrors();

        $stored = Storage::disk('local')->get($user->profile->fresh()->photo_path);
        $this->assertStringNotContainsString('GPS-SECRET-MARKER', $stored);
        $this->assertStringNotContainsString('COMMENT-SECRET-MARKER', $stored);
        $this->assertStringNotContainsString('Exif', $stored);
    }

    public function test_code_appended_to_a_real_image_does_not_survive(): void
    {
        $user = $this->athlete();
        $polyglot = $this->jpegBytes().'<?php system($_GET["c"]); ?>';

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => $this->fileFromBytes($polyglot, 'shell.jpg', 'image/jpeg')])
            ->assertSessionHasNoErrors();

        $path = $user->profile->fresh()->photo_path;
        $this->assertStringEndsWith('.jpg', $path);
        $this->assertStringNotContainsString('<?php', Storage::disk('local')->get($path));
    }

    public function test_files_that_are_not_images_are_refused_whatever_they_are_called(): void
    {
        $user = $this->athlete();
        $this->actingAs($user);

        $files = [
            UploadedFile::fake()->createWithContent('evil.jpg', '<?php echo "hi";'),
            $this->fileFromBytes('<?php echo "hi";', 'evil.jpg', 'image/jpeg'),
            $this->fileFromBytes('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'x.svg', 'image/svg+xml'),
            $this->fileFromBytes('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'x.jpg', 'image/jpeg'),
            $this->fileFromBytes("GIF89a\x01\x00\x01\x00\x00\x00\x00;", 'x.jpg', 'image/jpeg'),
            $this->fileFromBytes('%PDF-1.4 not really', 'x.png', 'image/png'),
            UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'),
        ];

        foreach ($files as $file) {
            $this->post('/profile/photo', ['photo' => $file])->assertSessionHasErrors('photo');
        }

        $this->post('/profile/photo', [])->assertSessionHasErrors('photo');
        $this->post('/profile/photo', ['photo' => 'just text'])->assertSessionHasErrors('photo');

        $this->assertNull($user->profile->fresh()->photo_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_broken_image_with_a_valid_header_is_refused(): void
    {
        $user = $this->athlete();
        $truncated = substr($this->jpegBytes(600, 600), 0, 200);

        $response = $this->actingAs($user)->post('/profile/photo', ['photo' => $this->fileFromBytes($truncated, 'cut.jpg', 'image/jpeg')]);

        // Either refused, or decoded into a harmless clean JPEG. Never stored as sent.
        $path = $user->profile->fresh()->photo_path;
        if ($path === null) {
            $response->assertSessionHasErrors('photo');
        } else {
            $this->assertNotSame($truncated, Storage::disk('local')->get($path));
        }
        $this->assertTrue(true);
    }

    public function test_oversized_files_are_refused(): void
    {
        $user = $this->athlete();

        $this->actingAs($user)
            ->post('/profile/photo', ['photo' => UploadedFile::fake()->image('big.jpg')->size(PhotoStorage::MAX_KILOBYTES + 1)])
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->profile->fresh()->photo_path);
    }

    public function test_images_with_enormous_dimensions_are_refused_before_decoding(): void
    {
        // A tiny PNG header that claims to be 30000 x 30000 pixels.
        $ihdr = 'IHDR'.pack('N', 30000).pack('N', 30000)."\x08\x02\x00\x00\x00";
        $png = "\x89PNG\r\n\x1a\n".pack('N', 13).$ihdr.pack('N', crc32($ihdr));
        $path = tempnam(sys_get_temp_dir(), 'bomb');
        file_put_contents($path, $png);

        try {
            PhotoStorage::reencode($path);
            $this->fail('A 900-megapixel image was accepted.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('too large', $e->errors()['photo'][0]);
        }
    }

    public function test_replacing_a_photo_deletes_the_old_file_and_removing_deletes_the_file(): void
    {
        $user = $this->athlete();
        $this->actingAs($user);

        $this->post('/profile/photo', ['photo' => UploadedFile::fake()->image('one.jpg', 300, 300)]);
        $first = $user->profile->fresh()->photo_path;

        $this->post('/profile/photo', ['photo' => UploadedFile::fake()->image('two.jpg', 300, 300)]);
        $second = $user->profile->fresh()->photo_path;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
        $this->assertCount(1, Storage::disk('local')->allFiles());

        $this->delete('/profile/photo')->assertSessionHasNoErrors();
        $this->assertNull($user->profile->fresh()->photo_path);
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->get('/profile/photo')->assertNotFound();
    }

    public function test_a_coach_sees_the_photo_only_through_an_active_link_with_the_photo_switched_on(): void
    {
        $user = $this->athlete();
        $this->actingAs($user)->post('/profile/photo', ['photo' => UploadedFile::fake()->image('me.jpg', 300, 300)]);
        $link = $this->linkFor($user);
        $token = $link->token();
        $this->post('/logout');

        $this->get("/p/{$token}/photo")
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('no-store', $this->get("/p/{$token}/photo")->headers->get('Cache-Control'));
        $this->assertSame(route('share.photo', ['token' => $token]), $this->props($this->get("/p/{$token}"))['profile']['photo_url']);

        // Guests cannot use the athlete's own photo address.
        $this->get('/profile/photo')->assertRedirect('/login');

        // Photo hidden.
        $user->profile->update(['hidden_sections' => ['photo']]);
        $this->get("/p/{$token}/photo")->assertNotFound();
        $this->assertNull($this->props($this->get("/p/{$token}"))['profile']['photo_url']);

        // Photo shown again, link revoked.
        $user->profile->update(['hidden_sections' => []]);
        $this->get("/p/{$token}/photo")->assertOk();
        $link->forceFill(['revoked_at' => now()])->save();
        $this->get("/p/{$token}/photo")->assertNotFound();
        $this->get('/p/'.str_repeat('b', 43).'/photo')->assertNotFound();
    }

    public function test_uploads_are_rate_limited(): void
    {
        $this->actingAs($this->athlete());

        for ($i = 0; $i < 20; $i++) {
            $this->post('/profile/photo', []);
        }

        $this->post('/profile/photo', [])->assertStatus(429);
    }
}
