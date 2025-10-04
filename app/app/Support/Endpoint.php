<?php

namespace App\Support;

class Endpoint
{
    public static function expand(string $template, array $variables): string
    {
        $url = $template;

        foreach ($variables as $key => $value) {
            $url = str_replace('{'.(string) $key.'}', (string) $value, $url);
        }

        if (str_contains($url, '{base}')) {
            $url = str_replace('{base}', (string) config('salla_api.base'), $url);
        }

        return $url;
    }
}
