<?php

namespace App\Support;

class Endpoint
{
    public static function expand(string $template, array $vars = []): string
    {
        $result = $template;
        
        foreach ($vars as $key => $value) {
            $result = str_replace("{{$key}}", $value, $result);
        }
        
        return $result;
    }

    public static function orders(): array
    {
        return [
            'list' => '/orders',
            'get' => '/orders/{id}',
            'create' => '/orders',
            'update' => '/orders/{id}',
            'delete' => '/orders/{id}',
        ];
    }

    public static function products(): array
    {
        return [
            'list' => '/products',
            'get' => '/products/{id}',
            'create' => '/products',
            'update' => '/products/{id}',
            'delete' => '/products/{id}',
        ];
    }

    public static function customers(): array
    {
        return [
            'list' => '/customers',
            'get' => '/customers/{id}',
            'update' => '/customers/{id}',
            'delete' => '/customers/{id}',
        ];
    }

    public static function coupons(): array
    {
        return [
            'list' => '/marketing/coupons',
            'get' => '/marketing/coupons/{id}',
            'create' => '/marketing/coupons',
            'update' => '/marketing/coupons/{id}',
            'delete' => '/marketing/coupons/{id}',
        ];
    }

    public static function categories(): array
    {
        return [
            'list' => '/categories',
            'get' => '/categories/{id}',
            'create' => '/categories',
            'update' => '/categories/{id}',
            'delete' => '/categories/{id}',
        ];
    }

    public static function exports(): array
    {
        return [
            'create' => '/exports',
            'list' => '/exports',
            'status' => '/exports/{id}',
            'download' => '/exports/{id}/download',
        ];
    }
}