<?php

namespace App\Services;

use App\Models\Profile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Profile photos are never stored as uploaded. The file is checked by its
 * content, decoded, turned upright, shrunk and written out again as a fresh
 * JPEG, which drops location data and anything else hidden in the original.
 * Files live on the private disk and are only served through the app.
 */
class PhotoStorage
{
    public const MAX_KILOBYTES = 6144;

    public const MAX_PIXELS = 40_000_000;

    public const OUTPUT_EDGE = 900;

    private const TYPES = [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP];

    public static function replace(Profile $profile, UploadedFile $file): void
    {
        $jpeg = self::reencode($file->getRealPath());

        $path = 'photos/'.Str::random(40).'.jpg';
        Storage::disk('local')->put($path, $jpeg);

        $old = $profile->photo_path;
        $profile->forceFill(['photo_path' => $path])->save();

        if ($old) {
            Storage::disk('local')->delete($old);
        }
    }

    public static function remove(Profile $profile): void
    {
        if ($profile->photo_path) {
            Storage::disk('local')->delete($profile->photo_path);
            $profile->forceFill(['photo_path' => null])->save();
        }
    }

    public static function reencode(string $sourcePath): string
    {
        $info = @getimagesize($sourcePath);

        if ($info === false || ! in_array($info[2], self::TYPES, true)) {
            self::fail('That file is not a JPEG, PNG or WebP image.');
        }

        [$width, $height] = $info;

        if ($width < 1 || $height < 1 || $width * $height > self::MAX_PIXELS) {
            self::fail('That image is too large. Please use one under 40 megapixels.');
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($sourcePath),
            IMAGETYPE_PNG => @imagecreatefrompng($sourcePath),
            IMAGETYPE_WEBP => @imagecreatefromwebp($sourcePath),
        };

        if ($image === false) {
            self::fail('That image could not be read. Please try another one.');
        }

        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = (@exif_read_data($sourcePath)['Orientation'] ?? 1);
            $image = match ($orientation) {
                3 => imagerotate($image, 180, 0),
                6 => imagerotate($image, -90, 0),
                8 => imagerotate($image, 90, 0),
                default => $image,
            };
            $width = imagesx($image);
            $height = imagesy($image);
        }

        $scale = min(1, self::OUTPUT_EDGE / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagejpeg($canvas, null, 84);
        $jpeg = (string) ob_get_clean();

        imagedestroy($image);
        imagedestroy($canvas);

        return $jpeg;
    }

    private static function fail(string $message): never
    {
        throw ValidationException::withMessages(['photo' => $message]);
    }
}
