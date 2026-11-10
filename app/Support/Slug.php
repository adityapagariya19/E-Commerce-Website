<?php

namespace App\Support;

use Illuminate\Support\Str;

class Slug
{
    public static function unique(string $modelClass, string $source, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $i = 2;
        while ($modelClass::where($column, $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }
        return $slug;
    }

    /** Neutralise spreadsheet formula injection in CSV exports. */
    public static function csv(mixed $v): mixed
    {
        return is_string($v) && preg_match('/^[=+\-@\t\r]/', $v) ? "'".$v : $v;
    }
}
