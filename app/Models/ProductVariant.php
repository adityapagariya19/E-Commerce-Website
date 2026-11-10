<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $guarded = [];
    protected $casts = ['options' => 'array', 'price' => 'decimal:2', 'sale_price' => 'decimal:2', 'is_active' => 'boolean'];
    public function product() { return $this->belongsTo(Product::class); }
}
