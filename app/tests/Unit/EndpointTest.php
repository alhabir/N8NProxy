<?php

use App\Support\Endpoint;

test('can expand simple placeholders', function () {
    $template = '{base}/orders/{id}';
    $vars = ['base' => 'https://api.example.com', 'id' => '123'];
    
    $result = Endpoint::expand($template, $vars);
    
    expect($result)->toBe('https://api.example.com/orders/123');
});

test('uses config base when not provided', function () {
    config(['salla_api.base' => 'https://api.salla.dev/admin/v2']);
    
    $template = '{base}/products';
    $result = Endpoint::expand($template, []);
    
    expect($result)->toBe('https://api.salla.dev/admin/v2/products');
});

test('can expand multiple placeholders', function () {
    $template = '{base}/stores/{store_id}/orders/{order_id}';
    $vars = [
        'base' => 'https://api.example.com',
        'store_id' => 'store123',
        'order_id' => 'order456',
    ];
    
    $result = Endpoint::expand($template, $vars);
    
    expect($result)->toBe('https://api.example.com/stores/store123/orders/order456');
});

test('handles templates without placeholders', function () {
    $template = 'https://api.example.com/static/path';
    $result = Endpoint::expand($template, []);
    
    expect($result)->toBe('https://api.example.com/static/path');
});
