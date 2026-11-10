<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $guarded = [];
    protected $casts = ['unit_price' => 'decimal:2', 'line_total' => 'decimal:2'];
    public function order() { return $this->belongsTo(Order::class); }
    public function product() { return $this->belongsTo(Product::class); }
    public function variant() { return $this->belongsTo(ProductVariant::class, 'variant_id'); }
    public function refundableQty(): int { return max(0, $this->qty - $this->refunded_qty); }
}
