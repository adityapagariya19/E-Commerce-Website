<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function parent(): BelongsTo { return $this->belongsTo(self::class, 'parent_id'); }
    public function children(): HasMany { return $this->hasMany(self::class, 'parent_id')->orderBy('position')->orderBy('name'); }
    public function products(): BelongsToMany { return $this->belongsToMany(Product::class); }
    public function scopeActive($q) { return $q->where('is_active', true); }

    /** IDs of this category and all descendants (for "Shop" filtering). */
    public function descendantIds(): array
    {
        $ids = [$this->id];
        foreach ($this->children as $child) {
            $ids = array_merge($ids, $child->descendantIds());
        }
        return $ids;
    }
}
