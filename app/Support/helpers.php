<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

if (! function_exists('setting')) {
    /** Read a store setting (DB overrides config defaults). Secrets are decrypted transparently. */
    function setting(string $key, mixed $default = null): mixed
    {
        return Setting::getValue($key, $default);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount): string
    {
        $amount = (float) $amount;
        $formatted = number_format($amount, floor($amount) == $amount ? 2 : 2, '.', ',');
        return config('jbo.currency_symbol', '₹').$formatted;
    }
}

if (! function_exists('to_paise')) {
    function to_paise(float|int|string|null $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}

if (! function_exists('from_paise')) {
    function from_paise(int $paise): float
    {
        return round($paise / 100, 2);
    }
}

if (! function_exists('media_url')) {
    /**
     * Public URL for a stored image. Bundled placeholders live in /public/images; uploads live on the public disk.
     * Uses asset() (the host the visitor actually used) rather than APP_URL, so a wrong APP_URL/port can't break images.
     */
    function media_url(?string $path, ?string $variant = null): string
    {
        if (! $path) {
            return asset('images/placeholders/generic.svg');
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        if (str_starts_with($path, 'images/')) {
            return asset($path);
        }
        if ($variant) {
            $v = \App\Support\ImageProcessor::variantPath($path, $variant);
            $path = Storage::disk('public')->exists($v) ? $v : $path;
        }
        return asset('storage/'.ltrim($path, '/'));
    }
}

if (! function_exists('media_webp')) {
    /** URL of the WebP twin of an uploaded image, or null when there is none. */
    function media_webp(?string $path, ?string $variant = null): ?string
    {
        if (! $path || ! str_starts_with($path, 'uploads/')) {
            return null;
        }
        $w = \App\Support\ImageProcessor::webpPath($path, $variant);
        return Storage::disk('public')->exists($w) ? asset('storage/'.$w) : null;
    }
}

if (! function_exists('picture')) {
    /** <picture> with a WebP source (when available) and the optimised fallback <img>. All attributes are escaped. */
    function picture(?string $path, string $alt = '', ?string $variant = null, array $attrs = []): \Illuminate\Support\HtmlString
    {
        $img = '<img src="'.e(media_url($path, $variant)).'" alt="'.e($alt).'"';
        foreach ($attrs as $k => $v) {
            $img .= ' '.e($k).'="'.e($v).'"';
        }
        $img .= '>';
        $webp = media_webp($path, $variant);
        return new \Illuminate\Support\HtmlString($webp ? '<picture><source type="image/webp" srcset="'.e($webp).'">'.$img.'</picture>' : $img);
    }
}

if (! function_exists('order_status_label')) {
    function order_status_label(string $status): string
    {
        return config('jbo.order_statuses.'.$status, ucfirst(str_replace('_', ' ', $status)));
    }
}
