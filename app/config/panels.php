<?php

$adminUrl = env('ADMIN_APP_URL', 'https://app.n8ndesigner.com');
$merchantUrl = env('MERCHANT_APP_URL', 'https://merchant.n8ndesigner.com');

return [
    'admin_url' => $adminUrl,
    'merchant_url' => $merchantUrl,
    'admin_domain' => parse_url($adminUrl, PHP_URL_HOST) ?: 'app.n8ndesigner.com',
    'merchant_domain' => parse_url($merchantUrl, PHP_URL_HOST) ?: 'merchant.n8ndesigner.com',
];
