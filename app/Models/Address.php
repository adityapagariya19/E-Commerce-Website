<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $guarded = [];
    public function user() { return $this->belongsTo(User::class); }
    public function toArrayForOrder(): array
    {
        return $this->only(['first_name', 'last_name', 'company', 'line1', 'line2', 'city', 'state', 'postcode', 'country', 'phone']);
    }
}
