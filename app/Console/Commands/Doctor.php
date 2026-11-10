<?php

namespace App\Console\Commands;

use App\Support\ImageProcessor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Doctor extends Command
{
    protected $signature = 'jbo:doctor {--fix : create the storage symlink if it is missing}';
    protected $description = 'Diagnose the usual causes of broken images, failed uploads and mis-configuration.';

    public function handle(): int
    {
        $bad = 0;
        $row = function (bool $ok, string $label, string $hint = '') use (&$bad) {
            $this->line(($ok ? ' <info>✔</info> ' : ' <error>✘</error> ').$label.(! $ok && $hint ? "\n     → {$hint}" : ''));
            $ok || $bad++;
        };

        $row(true, 'PHP '.PHP_VERSION);
        try { DB::connection()->getPdo(); $row(true, 'Database connection ('.config('database.default').')'); } catch (\Throwable $e) { $row(false, 'Database connection', $e->getMessage()); }
        $link = public_path('storage');
        $linked = is_link($link) || is_dir($link);
        if (! $linked && $this->option('fix')) { @symlink(storage_path('app/public'), $link); $linked = is_link($link); }
        $row($linked, 'public/storage link', 'Run: php artisan storage:link   (the app also serves /storage/* itself as a fallback, but the link is faster)');
        $row(is_writable(storage_path('app/public')), 'storage/app/public writable', 'chmod -R ug+rw storage bootstrap/cache');
        $row(is_writable(storage_path('logs')), 'storage/logs writable');
        $appUrl = rtrim((string) config('app.url'), '/');
        $row(! str_contains($appUrl, 'localhost') || app()->environment('local'), "APP_URL = {$appUrl}", 'Set APP_URL to the real public URL in production. (Image URLs follow the visitor\'s host, so this does not break images, but emails/links use it.)');
        $row(ImageProcessor::available(), 'PHP GD extension (image resizing)', 'Install/enable php-gd; without it uploads are stored unresized.');
        $row(function_exists('imagewebp'), 'GD WebP support', 'Without it only JPEG/PNG are generated.');
        $row(function_exists('exif_read_data'), 'EXIF extension (phone photo rotation)', 'Enable ext-exif so portrait phone photos are not sideways.');
        $row(! config('app.debug') || app()->environment('local'), 'APP_DEBUG is off outside local', 'Set APP_DEBUG=false in production.');
        $missing = 0;
        foreach (\App\Models\ProductImage::pluck('path') as $path) {
            if (str_starts_with($path, 'uploads/') && ! Storage::disk('public')->exists($path)) { $missing++; }
        }
        $row($missing === 0, "Product images referenced in DB but missing on disk: {$missing}", 'Re-upload them, or restore storage/app/public from a backup.');
        $this->newLine();
        $bad ? $this->warn("{$bad} problem(s) found.") : $this->info('All checks passed.');
        return $bad ? self::FAILURE : self::SUCCESS;
    }
}
