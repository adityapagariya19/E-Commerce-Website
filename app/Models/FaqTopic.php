<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FaqTopic extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function faqs() { return $this->hasMany(Faq::class)->orderBy('position')->orderBy('id'); }
}
