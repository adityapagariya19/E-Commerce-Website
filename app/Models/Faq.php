<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Faq extends Model
{
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
    public function topic() { return $this->belongsTo(FaqTopic::class, 'faq_topic_id'); }
}
