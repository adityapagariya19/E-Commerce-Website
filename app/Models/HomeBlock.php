<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeBlock extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean', 'is_placeholder' => 'boolean'];
    public const SECTIONS = ['collage' => 'Category collage', 'promo' => 'Promotional banner', 'trust' => 'Trust / service row', 'social' => 'Instagram / social tiles'];
    public function scopeSection($q, string $section) { return $q->where('section', $section)->where('is_active', true)->orderBy('position')->orderBy('id'); }
}
