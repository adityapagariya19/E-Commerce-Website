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
            'store_phone' => '+91 9762759028',
            'store_email' => 'info@jboptician.com',
            'contact_recipient' => 'info@jboptician.com',
            'store_hours' => '10:00 AM – 9:30 PM (Mon – Sun)',
            'map_embed_url' => 'https://maps.google.com/maps?q=Shop+1,+Matrutva+Maternity+%26+Nursing+Hospital,+Colony,+Ulkanagari+Rd,+near+Omkareshwar+Mandir,+Garkheda+Area,+Chhatrapati+Sambhajinagar,+Maharashtra+431005&t=&z=16&ie=UTF8&iwloc=&output=embed',
            'social_instagram' => 'https://instagram.com/',
            'social_facebook' => 'https://facebook.com/',
            'social_youtube' => 'https://youtube.com/',
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
