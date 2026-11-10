<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $guarded = [];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'manage_stock' => 'boolean',
        'is_featured' => 'boolean',
        'is_new_arrival' => 'boolean',
        'tryon_enabled' => 'boolean',
        'specs' => 'array',
        'tags' => 'array',
        'published_at' => 'datetime',
    ];

    public function brand(): BelongsTo { return $this->belongsTo(Brand::class); }
    public function categories(): BelongsToMany { return $this->belongsToMany(Category::class); }
    public function images(): HasMany { return $this->hasMany(ProductImage::class)->orderBy('position')->orderBy('id'); }
    public function variants(): HasMany { return $this->hasMany(ProductVariant::class); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }
    public function approvedReviews(): HasMany { return $this->hasMany(Review::class)->where('status', 'approved'); }

    public function scopePublished($q)
    {
        return $q->where('status', 'published')
            ->where(fn ($w) => $w->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function isVariable(): bool { return $this->type === 'variable'; }

    /** @return array{regular: ?float, sale: ?float, current: ?float} */
    public function pricing(?ProductVariant $variant = null): array
    {
        if ($variant) {
            $regular = $variant->price !== null ? (float) $variant->price : ($this->price !== null ? (float) $this->price : null);
            $saleRaw = $variant->price !== null ? $variant->sale_price : ($variant->sale_price ?? $this->sale_price);
        } else {
            $regular = $this->price !== null ? (float) $this->price : null;
            $saleRaw = $this->sale_price;
        }
        $sale = ($regular !== null && $saleRaw !== null && (float) $saleRaw > 0 && (float) $saleRaw < $regular) ? (float) $saleRaw : null;
        return ['regular' => $regular, 'sale' => $sale, 'current' => $sale ?? $regular];
    }

    /** Lowest purchasable price (for variable products' listing cards). */
    public function listingPricing(): array
    {
        if ($this->isVariable()) {
            $best = null;
            foreach ($this->variants->where('is_active', true) as $v) {
                $p = $this->pricing($v);
                if ($p['current'] !== null && ($best === null || $p['current'] < $best['current'])) {
                    $best = $p;
                }
            }
            return $best ?? ['regular' => null, 'sale' => null, 'current' => null];
        }
        return $this->pricing();
    }

    public function getDiscountPercentAttribute(): ?int
    {
        $p = $this->listingPricing();
        return $p['sale'] && $p['regular'] ? (int) round((1 - $p['sale'] / $p['regular']) * 100) : null;
    }

    public function isPurchasable(): bool
    {
        if ($this->status !== 'published') return false;
        return $this->isVariable()
            ? $this->variants->where('is_active', true)->contains(fn ($v) => $this->pricing($v)['current'] !== null)
            : $this->price !== null;
    }

    /** Units that can be sold now. Unmanaged stock counts as unlimited. */
    public function availableStock(?ProductVariant $variant = null): int
    {
        if (! $this->manage_stock) return PHP_INT_MAX;
        return max(0, (int) ($this->isVariable() ? ($variant?->stock_qty ?? 0) : $this->stock_qty));
    }

    public function inStock(): bool
    {
        if (! $this->manage_stock) return true;
        return $this->isVariable() ? $this->variants->where('is_active', true)->sum('stock_qty') > 0 : $this->stock_qty > 0;
    }

    public function getImageUrlAttribute(): string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();
        return media_url($img?->path);
    }

    public function getThumbUrlAttribute(): string
    {
        $img = $this->relationLoaded('images') ? $this->images->first() : $this->images()->first();
        return media_url($img?->path, 'thumb');
    }

    public function getRatingAverageAttribute(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');
        return $avg ? round($avg, 1) : null;
    }
}
