<?php

namespace App\Support;

class Endpoint
{
    /**
     * Expand URL template with variables
     * 
     * @param string $template URL template with {placeholder} syntax
     * @param array $vars Variables to substitute
     * @return string Expanded URL
     */
    public static function expand(string $template, array $vars = []): string
    {
        $url = $template;

        // Replace all provided variables
        foreach ($vars as $key => $value) {
            $url = str_replace('{' . $key . '}', (string) $value, $url);
        }

        // Replace {base} with the configured base URL if not already provided
        if (str_contains($url, '{base}') && !isset($vars['base'])) {
            $url = str_replace('{base}', config('salla_api.base'), $url);
        }

        return $url;
    }
}