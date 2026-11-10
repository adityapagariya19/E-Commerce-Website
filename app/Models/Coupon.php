<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $guarded = [];
    protected $casts = [
        'value' => 'decimal:2', 'min_subtotal' => 'decimal:2', 'max_discount' => 'decimal:2',
        'starts_at' => 'datetime', 'ends_at' => 'datetime', 'is_active' => 'boolean',
        'product_ids' => 'array', 'category_ids' => 'array',
    ];

    public function usages() { return $this->hasMany(CouponUsage::class); }

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = strtoupper(trim($value));
    }

    /** Returns a customer-facing error string, or null when the coupon may be used. */
    public function check(int $subtotalPaise, ?int $userId, ?string $email): ?string
    {
        if (! $this->is_active) return 'This coupon is not active.';
        if ($this->starts_at && $this->starts_at->isFuture()) return 'This coupon is not valid yet.';
        if ($this->ends_at && $this->ends_at->isPast()) return 'This coupon has expired.';
        if ($this->min_subtotal !== null && $subtotalPaise < to_paise($this->min_subtotal)) {
            return 'Minimum order of '.money($this->min_subtotal).' required for this coupon.';
        }
        if ($this->usage_limit !== null && $this->usages()->count() >= $this->usage_limit) return 'This coupon has reached its usage limit.';
        if ($this->per_user_limit !== null && ($userId || $email)) {
            $used = $this->usages()->where(fn ($q) => $userId ? $q->where('user_id', $userId) : $q->where('email', $email))->count();
            if ($used >= $this->per_user_limit) return 'You have already used this coupon the maximum number of times.';
        }
        return null;
    }

    public function appliesTo(Product $product): bool
    {
        $hasP = ! empty($this->product_ids);
        $hasC = ! empty($this->category_ids);
        if (! $hasP && ! $hasC) return true;
        if ($hasP && in_array($product->id, $this->product_ids, true)) return true;
        if ($hasC && $product->categories->pluck('id')->intersect($this->category_ids)->isNotEmpty()) return true;
        return false;
    }
}
