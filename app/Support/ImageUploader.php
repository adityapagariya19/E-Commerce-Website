<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Validated image uploads only (jpg/png/webp, size + minimum width), then optimised by ImageProcessor. */
class ImageUploader
{
    private const PROFILE_BY_DIR = ['hero' => 'hero', 'products' => 'product', 'blocks' => 'tile', 'categories' => 'tile', 'brands' => 'logo'];

    public static function rules(bool $required = false): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'image', 'mimes:'.implode(',', config('jbo.upload.mimes')),
            'max:'.config('jbo.upload.max_kb'), 'dimensions:min_width='.config('jbo.upload.min_width')];
    }

    /** Stores + optimises. Returns the path (relative to the public disk) of the fallback image. */
    public static function store(UploadedFile $file, string $dir): string
    {
        $rel = 'uploads/'.$dir;
        if (! ImageProcessor::available()) {
            return $file->store($rel, 'public'); // GD missing: keep the validated original
        }
        $abs = Storage::disk('public')->path($rel);
        $name = (string) Str::uuid();
        $filename = ImageProcessor::process($file->getRealPath(), $abs, $name, self::PROFILE_BY_DIR[$dir] ?? 'product');
        return $rel.'/'.$filename;
    }

    /** Deletes the image and its WebP/thumbnail twins. Only ever touches files under uploads/. */
    public static function delete(?string $path): void
    {
        if (! $path || ! str_starts_with($path, 'uploads/') || str_contains($path, '..')) {
            return;
        }
        Storage::disk('public')->delete(ImageProcessor::allPaths($path));
    }

    public static function replace(?UploadedFile $file, ?string $old, string $dir): ?string
    {
        if (! $file) {
            return $old;
        }
        $new = self::store($file, $dir); // store first: if processing fails the old image is untouched
        self::delete($old);
        return $new;
    }
}
