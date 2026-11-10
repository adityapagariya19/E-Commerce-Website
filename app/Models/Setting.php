<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret'];
    protected $casts = ['is_secret' => 'boolean'];

    private static ?array $memo = null;

    public static function all_(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }
        try {
            return self::$memo = Cache::remember('settings.all', 3600, fn () => self::query()->get()
                ->mapWithKeys(fn ($s) => [$s->key => ['v' => $s->value, 's' => $s->is_secret]])->all());
        } catch (\Throwable $e) {
            return self::$memo = []; // DB not migrated yet
        }
    }

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $row = self::all_()[$key] ?? null;
        if (! $row || $row['v'] === null || $row['v'] === '') {
            return $default;
        }
        if ($row['s']) {
            try { return Crypt::decryptString($row['v']); } catch (\Throwable $e) { return $default; }
        }
        return $row['v'];
    }

    public static function put(string $key, mixed $value, bool $secret = false): void
    {
        $stored = $value === null || $value === '' ? null : ($secret ? Crypt::encryptString((string) $value) : (string) $value);
        self::updateOrCreate(['key' => $key], ['value' => $stored, 'is_secret' => $secret]);
        self::flush();
    }

    public static function flush(): void
    {
        self::$memo = null;
        Cache::forget('settings.all');
    }
}
