<?php

return [
    'client_id' => env('SALLA_CLIENT_ID'),
    'client_secret' => env('SALLA_CLIENT_SECRET'),

    'webhook' => [
        'mode' => env('SALLA_WEBHOOK_MODE', 'token'),
        'token' => env('SALLA_WEBHOOK_TOKEN'),
        'token_header' => 'X-Webhook-Token',
        'token_query_key' => 'token',
    ],

    'headers' => [
        'event' => 'X-Salla-Event',
        'event_id' => 'X-Salla-Event-Id',
        'merchant' => 'X-Salla-Merchant-Id',
    ],

    'events' => [
        'app_prefix' => 'app.',
    ],

    'forwarding' => [
        'forwarded_by' => 'N8NProxy',
        'merchant_header' => 'X-N8NProxy-Merchant-ID',
    ],
];
