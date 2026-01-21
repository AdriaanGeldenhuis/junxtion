<?php
/**
 * Request Handler
 *
 * Parse and sanitize incoming request data
 */

class Request
{
    private static ?array $jsonBody = null;

    /**
     * Get JSON body from request
     */
    public static function getJson(): array
    {
        if (self::$jsonBody === null) {
            $rawBody = file_get_contents('php://input');
            self::$jsonBody = json_decode($rawBody, true) ?? [];
        }
        return self::$jsonBody;
    }

    /**
     * Get specific field from JSON body
     */
    public static function get(string $key, $default = null)
    {
        $data = self::getJson();
        return $data[$key] ?? $default;
    }

    /**
     * Get query parameter
     */
    public static function query(string $key, $default = null)
    {
        return $_GET[$key] ?? $default;
    }

    /**
     * Get multiple fields from JSON body
     */
    public static function only(array $keys): array
    {
        $data = self::getJson();
        $result = [];
        foreach ($keys as $key) {
            if (isset($data[$key])) {
                $result[$key] = $data[$key];
            }
        }
        return $result;
    }

    /**
     * Get all JSON data except specified keys
     */
    public static function except(array $keys): array
    {
        $data = self::getJson();
        return array_diff_key($data, array_flip($keys));
    }

    /**
     * Check if request has specific field
     */
    public static function has(string $key): bool
    {
        $data = self::getJson();
        return isset($data[$key]);
    }

    /**
     * Get request method
     */
    public static function method(): string
    {
        return $_SERVER['REQUEST_METHOD'];
    }

    /**
     * Check if request is specific method
     */
    public static function isMethod(string $method): bool
    {
        return strtoupper($_SERVER['REQUEST_METHOD']) === strtoupper($method);
    }

    /**
     * Get bearer token from Authorization header
     */
    public static function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (preg_match('/Bearer\s+(.+)/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get client IP address
     */
    public static function ip(): string
    {
        // Check for proxy headers (be careful with these in production)
        $headers = [
            'HTTP_CF_CONNECTING_IP',  // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'REMOTE_ADDR'
        ];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }

    /**
     * Get User Agent
     */
    public static function userAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
    }

    /**
     * Get request header
     */
    public static function header(string $name): ?string
    {
        $serverKey = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$serverKey] ?? null;
    }

    /**
     * Get uploaded file
     */
    public static function file(string $name): ?array
    {
        return $_FILES[$name] ?? null;
    }

    /**
     * Get raw body
     */
    public static function rawBody(): string
    {
        return file_get_contents('php://input');
    }

    /**
     * Get the full URL
     */
    public static function fullUrl(): string
    {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    /**
     * Get the path
     */
    public static function path(): string
    {
        return parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    }
}
