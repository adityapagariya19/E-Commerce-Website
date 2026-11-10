<?php

namespace App\Support;

use RuntimeException;

/**
 * Upload processing with PHP's GD extension (no extra Composer dependency).
 *  - fixes EXIF orientation (phone photos), never upscales, keeps aspect ratio
 *  - writes an optimised fallback (JPEG, or PNG when the source may have transparency)
 *  - writes a WebP twin when GD supports it, plus a smaller "-thumb" pair for grids
 * File naming:  abc.jpg  abc.webp  abc-thumb.jpg  abc-thumb.webp
 */
class ImageProcessor
{
    public const PROFILES = [
        'hero' => ['max' => 1920, 'thumb' => 800, 'quality' => 82],
        'product' => ['max' => 1200, 'thumb' => 480, 'quality' => 82],
        'tile' => ['max' => 1200, 'thumb' => 480, 'quality' => 82],
        'logo' => ['max' => 600, 'thumb' => 300, 'quality' => 85],
    ];
    private const MAX_PIXELS = 50_000_000;

    public static function available(): bool
    {
        return function_exists('imagecreatefromstring') && function_exists('imagejpeg');
    }

    public static function variantPath(string $path, string $variant): string
    {
        return preg_replace('/(\.[A-Za-z0-9]+)$/', '-'.$variant.'$1', $path);
    }

    public static function webpPath(string $path, ?string $variant = null): string
    {
        $base = $variant ? self::variantPath($path, $variant) : $path;
        return preg_replace('/\.[A-Za-z0-9]+$/', '.webp', $base);
    }

    /** All files that belong to one stored image (used when deleting/replacing). */
    public static function allPaths(string $path): array
    {
        return array_unique([$path, self::variantPath($path, 'thumb'), self::webpPath($path), self::webpPath($path, 'thumb')]);
    }

    /**
     * @return string file name (without directory) of the fallback image that was written into $absDir
     */
    public static function process(string $sourceFile, string $absDir, string $baseName, string $profile = 'product'): string
    {
        $cfg = self::PROFILES[$profile] ?? self::PROFILES['product'];
        $info = @getimagesize($sourceFile);
        if (! $info || $info[0] < 1 || $info[1] < 1) {
            throw new RuntimeException('Not a valid image.');
        }
        if ($info[0] * $info[1] > self::MAX_PIXELS) {
            throw new RuntimeException('Image is too large (over 50 megapixels).');
        }
        $img = @imagecreatefromstring((string) file_get_contents($sourceFile));
        if (! $img) {
            throw new RuntimeException('The image could not be read.');
        }
        $mime = $info['mime'] ?? '';
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $img = self::orient($img, (int) (@exif_read_data($sourceFile)['Orientation'] ?? 1));
        }
        if (function_exists('imagepalettetotruecolor')) {
            @imagepalettetotruecolor($img);
        }
        $alpha = in_array($mime, ['image/png', 'image/webp', 'image/gif'], true);
        $ext = $alpha ? 'png' : 'jpg';
        if (! is_dir($absDir) && ! @mkdir($absDir, 0775, true) && ! is_dir($absDir)) {
            throw new RuntimeException('Cannot create the upload directory.');
        }

        $main = self::fit($img, $cfg['max']);
        self::write($main, "{$absDir}/{$baseName}.{$ext}", $ext, $cfg['quality'], $alpha);
        self::write($main, "{$absDir}/{$baseName}.webp", 'webp', $cfg['quality'], $alpha);
        $thumb = self::fit($main, $cfg['thumb']);
        self::write($thumb, "{$absDir}/{$baseName}-thumb.{$ext}", $ext, $cfg['quality'], $alpha);
        self::write($thumb, "{$absDir}/{$baseName}-thumb.webp", 'webp', $cfg['quality'], $alpha);

        return "{$baseName}.{$ext}";
    }

    private static function orient($img, int $o)
    {
        $angle = [3 => 180, 6 => -90, 8 => 90][$o] ?? 0;
        if ($angle) {
            $rot = imagerotate($img, $angle, 0);
            if ($rot) { return $rot; }
        }
        return $img;
    }

    /** Scale down to $maxW wide (never up), preserving aspect ratio and alpha. */
    private static function fit($img, int $maxW)
    {
        $w = imagesx($img);
        if ($w <= $maxW) {
            return $img;
        }
        $scaled = imagescale($img, $maxW, -1, IMG_BICUBIC);
        if ($scaled) {
            imagealphablending($scaled, false);
            imagesavealpha($scaled, true);
            return $scaled;
        }
        return $img;
    }

    private static function write($img, string $file, string $type, int $q, bool $alpha): void
    {
        if ($type === 'webp') {
            if (! function_exists('imagewebp')) { return; }
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagewebp($img, $file, $q);
        } elseif ($type === 'png') {
            imagealphablending($img, false);
            imagesavealpha($img, true);
            imagepng($img, $file, 7);
        } else {
            // JPEG has no alpha: flatten onto white so transparent areas don't turn black
            $w = imagesx($img); $h = imagesy($img);
            $flat = imagecreatetruecolor($w, $h);
            imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
            imagecopy($flat, $img, 0, 0, 0, 0, $w, $h);
            imageinterlace($flat, true);
            imagejpeg($flat, $file, $q);
        }
        @chmod($file, 0664);
    }
}
