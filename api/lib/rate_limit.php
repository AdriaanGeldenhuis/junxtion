<?php
/**
 * Rate Limiter
 *
 * Simple file-based rate limiting for Xneelo hosting
 */

class RateLimit
{
    private static string $cacheDir;

    /**
     * Initialize cache directory
     */
    private static function init(): void
    {
        $config = $GLOBALS['config'] ?? [];
        self::$cacheDir = ($config['paths']['private'] ?? __DIR__ . '/../private') . '/cache/rate_limit/';

        if (!is_dir(self::$cacheDir)) {
            mkdir(self::$cacheDir, 0755, true);
        }
    }

    /**
     * Check rate limit
     *
     * @return bool True if allowed, false if rate limited
     */
    public static function check(string $key, int $maxRequests = 60, int $windowSeconds = 60): bool
    {
        self::init();

        $key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $file = self::$cacheDir . $key . '.json';

        $now = time();
        $data = ['requests' => [], 'count' => 0];

        // Load existing data
        if (file_exists($file)) {
            $content = file_get_contents($file);
            $data = json_decode($content, true) ?? $data;
        }

        // Remove expired requests
        $windowStart = $now - $windowSeconds;
        $data['requests'] = array_filter($data['requests'], function ($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        // Check if over limit
        if (count($data['requests']) >= $maxRequests) {
            return false;
        }

        // Add current request
        $data['requests'][] = $now;
        $data['count'] = count($data['requests']);

        // Save data
        file_put_contents($file, json_encode($data), LOCK_EX);

        return true;
    }

    /**
     * Check rate limit and respond with error if exceeded
     */
    public static function enforce(string $key, int $maxRequests = 60, int $windowSeconds = 60): void
    {
        if (!self::check($key, $maxRequests, $windowSeconds)) {
            Response::rateLimited($windowSeconds);
        }
    }

    /**
     * Get remaining requests for a key
     */
    public static function remaining(string $key, int $maxRequests = 60, int $windowSeconds = 60): int
    {
        self::init();

        $key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $file = self::$cacheDir . $key . '.json';

        if (!file_exists($file)) {
            return $maxRequests;
        }

        $now = time();
        $content = file_get_contents($file);
        $data = json_decode($content, true) ?? ['requests' => []];

        $windowStart = $now - $windowSeconds;
        $validRequests = array_filter($data['requests'], function ($timestamp) use ($windowStart) {
            return $timestamp > $windowStart;
        });

        return max(0, $maxRequests - count($validRequests));
    }

    /**
     * Reset rate limit for a key
     */
    public static function reset(string $key): void
    {
        self::init();

        $key = preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
        $file = self::$cacheDir . $key . '.json';

        if (file_exists($file)) {
            unlink($file);
        }
    }

    /**
     * Get rate limit key for current IP
     */
    public static function ipKey(string $prefix = 'ip'): string
    {
        return $prefix . '_' . str_replace([':', '.'], '_', Request::ip());
    }

    /**
     * Get rate limit key for phone number
     */
    public static function phoneKey(string $phone, string $prefix = 'phone'): string
    {
        return $prefix . '_' . preg_replace('/[^0-9]/', '', $phone);
    }

    /**
     * Cleanup old rate limit files (call from cron)
     */
    public static function cleanup(int $olderThanSeconds = 3600): int
    {
        self::init();

        $deleted = 0;
        $now = time();

        $files = glob(self::$cacheDir . '*.json');
        foreach ($files as $file) {
            if (filemtime($file) < ($now - $olderThanSeconds)) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
