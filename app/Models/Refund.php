<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    protected $guarded = [];
    protected $casts = ['items' => 'array', 'restock' => 'boolean', 'amount' => 'decimal:2'];
    public function order() { return $this->belongsTo(Order::class); }
    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }
}
