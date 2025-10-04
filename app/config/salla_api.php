<?php

return [
    'base' => rtrim(env('SALLA_API_BASE', 'https://api.salla.dev/admin/v2'), '/'),

    'oauth' => [
        'token_url' => env('SALLA_OAUTH_TOKEN_URL', 'https://accounts.salla.sa/oauth2/token'),
        'client_id' => env('SALLA_CLIENT_ID'),
        'client_secret' => env('SALLA_CLIENT_SECRET'),
    ],

    'orders' => [
        'create' => '{base}/orders',
        'delete' => '{base}/orders/{id}',
        'get'    => '{base}/orders/{id}',
        'list'   => '{base}/orders',
        'update' => '{base}/orders/{id}',
    ],

    'products' => [
        'create' => '{base}/products',
        'delete' => '{base}/products/{id}',
        'get'    => '{base}/products/{id}',
        'list'   => '{base}/products',
        'update' => '{base}/products/{id}',
    ],

    'customers' => [
        'delete' => '{base}/customers/{id}',
        'get'    => '{base}/customers/{id}',
        'list'   => '{base}/customers',
        'update' => '{base}/customers/{id}',
    ],

    'coupons' => [
        'create' => '{base}/coupons',
        'delete' => '{base}/coupons/{id}',
        'get'    => '{base}/coupons/{id}',
        'list'   => '{base}/coupons',
        'update' => '{base}/coupons/{id}',
    ],

    'categories' => [
        'create' => '{base}/categories',
        'delete' => '{base}/categories/{id}',
        'get'    => '{base}/categories/{id}',
        'list'   => '{base}/categories',
        'update' => '{base}/categories/{id}',
    ],

    'exports' => [
        'create'   => '{base}/exports',
        'list'     => '{base}/exports',
        'status'   => '{base}/exports/{id}',
        'download' => '{base}/exports/{id}/download',
    ],
];
