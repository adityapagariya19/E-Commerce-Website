<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];
    protected $casts = [
        'billing' => 'array', 'shipping' => 'array',
        'subtotal' => 'decimal:2', 'discount_total' => 'decimal:2', 'shipping_total' => 'decimal:2',
        'tax_total' => 'decimal:2', 'total' => 'decimal:2', 'refunded_total' => 'decimal:2',
        'stock_reserved' => 'boolean', 'is_demo' => 'boolean',
        'placed_at' => 'datetime', 'paid_at' => 'datetime', 'cancelled_at' => 'datetime',
    ];

    public function user() { return $this->belongsTo(User::class); }
    public function items() { return $this->hasMany(OrderItem::class); }
    public function histories() { return $this->hasMany(OrderStatusHistory::class)->latest('id'); }
    public function payments() { return $this->hasMany(Payment::class); }
    public function refunds() { return $this->hasMany(Refund::class); }
    public function couponUsages() { return $this->hasMany(CouponUsage::class); }

    public function getRouteKeyName(): string { return 'id'; }
    public function paymentMethodLabel(): string
    {
        return ['cod' => 'Cash on delivery', 'razorpay' => 'Credit Card/Debit Card/NetBanking (Razorpay)'][$this->payment_method] ?? $this->payment_method;
    }
    public function statusLabel(): string { return order_status_label($this->status); }
    public function isPayable(): bool { return $this->payment_method === 'razorpay' && $this->status === 'pending_payment'; }
    public function netTotal(): float { return (float) $this->total - (float) $this->refunded_total; }

    /** Customers may cancel unpaid online orders, and COD orders that have not been packed/shipped yet. */
    public function customerCanCancel(): bool
    {
        return $this->status === 'pending_payment'
            || ($this->payment_method === 'cod' && in_array($this->status, ['processing', 'confirmed'], true));
    }

    public function hasTracking(): bool
    {
        return (bool) ($this->courier_name || $this->tracking_number || $this->tracking_url);
    }

    public static function nextNumber(): int
    {
        return max((int) static::max('order_number'), 5767) + 1; // reference shows order #5768
    }
}
