<?php

namespace App\Support;

class Endpoint
{
    /**
     * Expand template URL with provided variables
     *
     * @param string $template The URL template with placeholders like {base}, {id}
     * @param array $variables Variables to replace in the template
     * @return string The expanded URL
     */
    public static function expand(string $template, array $variables = []): string
    {
        $url = $template;
        
        // Replace all variables
        foreach ($variables as $key => $value) {
            $url = str_replace('{' . $key . '}', $value, $url);
        }
        
        // Replace {base} if not already replaced
        if (str_contains($url, '{base}')) {
            $url = str_replace('{base}', config('salla_api.base'), $url);
        }
        
        return $url;
    }
}