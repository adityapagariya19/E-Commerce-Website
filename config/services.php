<?php

return [
    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID'),
        'key_secret' => env('RAZORPAY_KEY_SECRET'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        'base_url' => 'https://api.razorpay.com/v1',
    ],
    'omnisend' => [
        'api_key' => env('OMNISEND_API_KEY'),
        'base_url' => 'https://api.omnisend.com/v3',
    ],
    'chatbase' => ['bot_id' => env('CHATBASE_BOT_ID')],
    'ga4' => ['measurement_id' => env('GA4_MEASUREMENT_ID')],
];
