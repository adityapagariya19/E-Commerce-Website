<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $guarded = [];
    protected $casts = ['synced_omnisend' => 'boolean', 'subscribed_at' => 'datetime', 'unsubscribed_at' => 'datetime', 'consent_at' => 'datetime', 'synced_at' => 'datetime'];
}
