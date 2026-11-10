<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\RazorpayService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /** key => validation rule. */
    private const PLAIN = [
        'store_name' => 'required|max:100', 'store_tagline' => 'nullable|max:150', 'store_email' => 'nullable|email|max:150', 'contact_recipient' => 'nullable|email|max:150',
        'store_phone' => 'nullable|max:30', 'store_address' => 'nullable|max:400', 'store_hours' => 'nullable|max:200',
        'map_embed_url' => ['nullable', 'max:1000', 'regex:~^https://www\.google\.com/maps/embed\?~'],
        'social_instagram' => 'nullable|url|max:200', 'social_facebook' => 'nullable|url|max:200', 'social_youtube' => 'nullable|url|max:200',
        'announcements' => 'nullable|max:1000',
        'shipping_flat_rate' => 'nullable|numeric|min:0', 'shipping_free_threshold' => 'nullable|numeric|min:0', 'tax_rate' => 'nullable|numeric|min:0|max:100',
        'razorpay_key_id' => ['nullable', 'max:100', 'regex:/^rzp_(test|live)_[A-Za-z0-9]+$/'],
        'tryon_allowed_hosts' => ['nullable', 'max:300', 'regex:/^[a-z0-9.,\-\s]+$/i'],
        'chatbase_bot_id' => 'nullable|max:100', 'ga4_measurement_id' => ['nullable', 'regex:/^G-[A-Z0-9]+$/'],
        'newsletter_success_message' => 'nullable|max:300', 'help_phone' => 'nullable|max:30', 'help_email' => 'nullable|email|max:150',
    ];
    private const SECRET = ['razorpay_key_secret', 'razorpay_webhook_secret', 'omnisend_api_key'];

    public function edit(RazorpayService $razorpay)
    {
        return view('admin.settings', ['razorpayMode' => $razorpay->mode(), 'webhookUrl' => route('webhooks.razorpay'),
            'hasSecret' => collect(self::SECRET)->mapWithKeys(fn ($k) => [$k => (bool) setting($k)])]);
    }

    public function update(Request $request)
    {
        $rules = self::PLAIN;
        foreach (self::SECRET as $k) { $rules[$k] = 'nullable|string|max:200'; }
        $d = $request->validate($rules);
        foreach (self::PLAIN as $k => $_) {
            Setting::put($k, $d[$k] ?? null);
        }
        foreach (self::SECRET as $k) {
            if ($request->boolean("clear_{$k}")) { Setting::put($k, null, true); }
            elseif (filled($d[$k] ?? null)) { Setting::put($k, $d[$k], true); } // blank = keep current; stored encrypted
        }
        ActivityLog::record('settings.updated'); // never log values
        return back()->with('success', 'Settings saved.');
    }
}
