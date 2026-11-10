<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        $values = [
            'store_name' => 'JB Optician',
            'store_tagline' => 'Your vision is our mission',
            // Address read from the Google Maps card in the reference (Contact Us page).
            'store_address' => 'Shop 1, Matrutva Maternity & Nursing Hospital, Colony, Ulkanagari Rd, near Omkareshwar Mandir, Garkheda Area, Chhatrapati Sambhajinagar, Maharashtra 431005',
            // Phone/email/hours/map-embed are NOT visible in the reference: left empty on purpose. Fill in Admin > Settings.
            'store_phone' => null, 'store_email' => null, 'contact_recipient' => null, 'store_hours' => null, 'map_embed_url' => null,
            'social_instagram' => null, 'social_facebook' => null, 'social_youtube' => null,
            // Ticker copy transcribed from the reference header. CONFIRM with the owner before launch.
            'announcements' => "FREE SHIPPING IN INDIA. ORDERS ₹2000+\nWELCOME TO JB OPTICIAN STORE\nSIGN UP & ENJOY 10% OFF",
            'shipping_flat_rate' => '0', 'shipping_free_threshold' => '0', 'tax_rate' => '0',
            'newsletter_success_message' => "You're all signed up! You'll start to receive discounts by email and phone.",
        ];
        foreach ($values as $k => $v) {
            Setting::firstOrCreate(['key' => $k], ['value' => $v, 'is_secret' => false]);
        }
        Setting::flush();
    }
}
