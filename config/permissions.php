<?php

// Role definitions. In admin UI, the primary role is presented as 'Admin'.
return [
    'roles' => [
        'super_admin' => ['label' => 'Admin', 'permissions' => ['*']],
        'store_manager' => ['label' => 'Admin', 'permissions' => [
            'dashboard', 'products', 'catalog', 'media', 'orders', 'customers', 'coupons',
            'content', 'reports', 'newsletter', 'messages', 'reviews',
        ]],
        'content_manager' => ['label' => 'Admin', 'permissions' => [
            'dashboard', 'products', 'catalog', 'media', 'content', 'reviews',
        ]],
        'order_support' => ['label' => 'Admin', 'permissions' => [
            'dashboard', 'orders', 'messages',
        ]],
    ],
];
