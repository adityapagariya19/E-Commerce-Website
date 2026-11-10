<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

/**
 * Safety net for the #1 cause of "broken image after upload": the public/storage symlink is missing.
 * When the symlink exists the web server serves /storage/* itself and this route is never reached.
 */
class MediaController extends Controller
{
    public function show(string $path)
    {
        $ok = preg_match('#^uploads/[A-Za-z0-9/_.\-]+$#', $path) && ! str_contains($path, '..')
            && preg_match('/\.(jpe?g|png|webp)$/i', $path);
        abort_unless($ok && Storage::disk('public')->exists($path), 404);
        return Storage::disk('public')->response($path, null, ['Cache-Control' => 'public, max-age=604800']);
    }
}
