<?php

// Role => permission list. '*' = everything. Stored on users.role; no extra tables needed.
return [
    'roles' => [
        'super_admin' => ['label' => 'Super administrator', 'permissions' => ['*']],
        'store_manager' => ['label' => 'Store manager', 'permissions' => [
            'dashboard', 'products', 'catalog', 'media', 'orders', 'customers', 'coupons',
            'content', 'reports', 'newsletter', 'messages', 'reviews',
        ]],
        'content_manager' => ['label' => 'Product / content manager', 'permissions' => [
            'dashboard', 'products', 'catalog', 'media', 'content', 'reviews',
        ]],
        'order_support' => ['label' => 'Order support', 'permissions' => [
            'dashboard', 'orders', 'messages',
        ]],
    ],
    // Sensitive: only super_admin ('*'): settings, admin_users, activity_log, customers.pii via 'customers' (store_manager).
];
