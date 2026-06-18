<?php
namespace App\Security;

class RateLimit
{
    private string $cacheDir;

    public function __construct()
    {
        $this->cacheDir = BASE_PATH . '/storage/cache/ratelimit/';
        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }
    }

    public function check(string $identifier, int $maxRequests, int $windowSeconds): bool
    {
        $key = hash('sha256', $identifier);
        $file = $this->cacheDir . $key;
        $now = time();
        $data = [];
        if ($maxRequests < 1 || $windowSeconds < 1) {
            throw new \InvalidArgumentException('Rate limit max requests and window must be positive.');
        }

        if (file_exists($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            $data = is_array($decoded) ? $decoded : [];
            // Remove malformed and expired entries.
            $data = array_values(array_filter(
                $data,
                fn($t) => is_int($t) && ($now - $t) < $windowSeconds
            ));
        }
        if (count($data) >= $maxRequests) {
            return false;
        }
        $data[] = $now;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return true;
    }
}