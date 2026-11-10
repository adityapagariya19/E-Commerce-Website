<?php

return [
    'currency' => 'INR',
    'currency_symbol' => '₹',

    // Popular This Week rules (documented in docs/popular-this-week.md)
    'popular' => [
        'window_days' => 7,
        'limit' => 4,
        'cache_minutes' => 10,
        'counted_statuses' => ['processing', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'completed'],
        'include_demo' => (bool) env('POPULAR_INCLUDE_DEMO', false),
    ],

    'new_arrivals_limit' => 8,
    'low_stock_threshold' => 5,
    'pending_payment_expiry_minutes' => 60,

    // Business workflow: pending_payment → processing → confirmed → packed → shipped → out_for_delivery → delivered.
    // 'completed' is kept as a legacy/short-cut "done" status for shops that don't use the full courier steps.
    'order_statuses' => [
        'pending_payment' => 'Payment pending',
        'processing' => 'Processing',
        'confirmed' => 'Confirmed',
        'packed' => 'Packed',
        'shipped' => 'Shipped',
        'out_for_delivery' => 'Out for delivery',
        'delivered' => 'Delivered',
        'completed' => 'Completed',
        'on_hold' => 'On hold',
        'cancelled' => 'Cancelled',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ],

    // Manual (admin) transitions. Payment-driven changes go through PaymentService.
    'status_transitions' => [
        'pending_payment' => ['cancelled', 'on_hold'],
        'on_hold' => ['processing', 'cancelled'],
        'processing' => ['confirmed', 'packed', 'shipped', 'delivered', 'completed', 'on_hold', 'cancelled'],
        'confirmed' => ['packed', 'shipped', 'on_hold', 'cancelled'],
        'packed' => ['shipped', 'on_hold', 'cancelled'],
        'shipped' => ['out_for_delivery', 'delivered', 'completed', 'processing'],
        'out_for_delivery' => ['delivered'],
        'delivered' => [],
        'completed' => [],
        'cancelled' => [],
        'failed' => [],
        'refunded' => [],
    ],

    // Steps shown in the customer's progress tracker
    'tracker_steps' => ['processing', 'confirmed', 'packed', 'shipped', 'out_for_delivery', 'delivered'],

    'upload' => ['max_kb' => 4096, 'mimes' => ['jpg', 'jpeg', 'png', 'webp'], 'min_width' => 100],
];
