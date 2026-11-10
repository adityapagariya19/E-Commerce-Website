<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HeroSlide extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function scopeActive($q) { return $q->where('is_active', true)->orderBy('position')->orderBy('id'); }
}
