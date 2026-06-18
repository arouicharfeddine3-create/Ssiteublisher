<?php
namespace App\Security;

class InputSanitizer
{
    public static function clean(array|string $input): array|string
    {
        if (is_array($input)) {
            return array_map([self::class, 'clean'], $input);
        }
        $input = trim($input);
        $input = stripslashes($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $input;
    }

    public static function cleanForDb(string $input): string
    {
        // Only trim, do not escape because we use prepared statements
        return trim($input);
    }

    public static function allowedTags(string $html): string
    {
        // Strip unsafe tags and remove common dangerous attributes/protocols.
        $html = strip_tags($html, '<p><a><strong><em><ul><ol><li><h2><h3><h4><br><img><table><tr><td><th>');
        $html = preg_replace('/\s+on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html) ?? '';
        $html = preg_replace('/\s+(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', '', $html) ?? '';
        $html = preg_replace('/\s+(href|src)\s*=\s*javascript:[^\s>]*/i', '', $html) ?? '';
        return $html;
    }
}
