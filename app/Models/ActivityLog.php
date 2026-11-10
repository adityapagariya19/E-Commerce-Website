<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['meta' => 'array', 'created_at' => 'datetime'];
    public function user() { return $this->belongsTo(User::class); }

    public static function record(string $action, ?Model $subject = null, array $meta = []): void
    {
        static::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip' => request()->ip(),
        ]);
    }
}
