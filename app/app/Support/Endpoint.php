<?php

namespace App\Support;

class Endpoint
{
    /**
     * Expand URL template with variables
     *
     * @param string $template URL template with {placeholders}
     * @param array $vars Variables to substitute
     * @return string Expanded URL
     */
    public static function expand(string $template, array $vars): string
    {
        $url = $template;

        foreach ($vars as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }

        // Ensure {base} is replaced with the configured API base
        if (str_contains($url, '{base}')) {
            $url = str_replace('{base}', config('salla_api.base'), $url);
        }

        return $url;
    }
}
