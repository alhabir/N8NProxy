<?php

return [
    'base' => env('SALLA_API_BASE', 'https://api.salla.dev/admin/v2'),
    
    'oauth' => [
        'token_url' => env('SALLA_OAUTH_TOKEN_URL', 'https://accounts.salla.sa/oauth2/token'),
        'client_id' => env('SALLA_CLIENT_ID'),
        'client_secret' => env('SALLA_CLIENT_SECRET'),
    ],

    'endpoints' => [
        'orders' => [
            'list' => '/orders',
            'get' => '/orders/{id}',
            'create' => '/orders',
            'update' => '/orders/{id}',
            'delete' => '/orders/{id}',
        ],
        'products' => [
            'list' => '/products',
            'get' => '/products/{id}',
            'create' => '/products',
            'update' => '/products/{id}',
            'delete' => '/products/{id}',
        ],
        'customers' => [
            'list' => '/customers',
            'get' => '/customers/{id}',
            'update' => '/customers/{id}',
            'delete' => '/customers/{id}',
        ],
        'coupons' => [
            'list' => '/marketing/coupons',
            'get' => '/marketing/coupons/{id}',
            'create' => '/marketing/coupons',
            'update' => '/marketing/coupons/{id}',
            'delete' => '/marketing/coupons/{id}',
        ],
        'categories' => [
            'list' => '/categories',
            'get' => '/categories/{id}',
            'create' => '/categories',
            'update' => '/categories/{id}',
            'delete' => '/categories/{id}',
        ],
        'exports' => [
            'create' => '/exports',
            'list' => '/exports',
            'status' => '/exports/{id}',
            'download' => '/exports/{id}/download',
        ],
    ],
];