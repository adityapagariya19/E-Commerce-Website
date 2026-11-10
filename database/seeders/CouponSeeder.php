<?php

namespace Database\Seeders;

use App\Models\Coupon;
use Illuminate\Database\Seeder;

class CouponSeeder extends Seeder
{
    public function run(): void
    {
        // DEMO coupon matching the ticker "SIGN UP & ENJOY 10% OFF". Terms are an assumption — confirm with the owner.
        Coupon::firstOrCreate(['code' => 'WELCOME10'], ['description' => 'DEMO: 10% welcome offer', 'type' => 'percent', 'value' => 10, 'min_subtotal' => 500, 'max_discount' => 500, 'per_user_limit' => 1, 'is_active' => true]);
        Coupon::firstOrCreate(['code' => 'FLAT100'], ['description' => 'DEMO: flat ₹100 off', 'type' => 'fixed', 'value' => 100, 'min_subtotal' => 1000, 'is_active' => true]);
    }
}
