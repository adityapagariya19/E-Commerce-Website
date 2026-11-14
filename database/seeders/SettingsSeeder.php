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
            'store_phone' => '+91 9876543210',
            'store_email' => 'info@jboptician.com',
            'contact_recipient' => 'info@jboptician.com',
            'store_hours' => '10:00 AM – 9:30 PM (Mon – Sun)',
            'map_embed_url' => 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3752.253891226364!2d75.3397181!3d19.8714937!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3bdba3980b02342d%3A0xa893b3f8d625ee38!2sJB%20OPTICIAN!5e0!3m2!1sen!2sin!4v1710000000000!5m2!1sen!2sin',
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
