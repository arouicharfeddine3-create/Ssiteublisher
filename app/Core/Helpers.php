<?php
namespace App\Core;

class Helpers
{
    public static function slugify(string $text): string
    {
        $text = preg_replace('~[^\pL\d]+~u', '-', $text);
        $transliterated = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = $transliterated === false ? $text : $transliterated;
        $text = preg_replace('~[^-\w]+~', '', $text);
        $text = trim($text, '-');
        $text = preg_replace('~-+~', '-', $text);
        return strtolower($text) ?: 'n-a';
    }

    public static function excerpt(string $content, int $limit = 200): string
    {
        $stripped = strip_tags($content);
        if (mb_strlen($stripped) <= $limit) {
            return $stripped;
        }
        return rtrim(mb_substr($stripped, 0, max(0, $limit))) . '...';
    }

    public static function randomString(int $length = 16): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Random string length must be at least 1 byte.');
        }

        return bin2hex(random_bytes($length));
    }

    public static function sanitizeFileName(string $name): string
    {
        $name = str_replace(' ', '_', basename($name));
        $name = preg_replace('/[^a-zA-Z0-9\-_.]/', '', $name) ?? '';
        return ltrim($name, '.') ?: 'file';
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}