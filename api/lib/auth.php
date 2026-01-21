<?php
/**
 * Authentication Handler
 *
 * JWT token generation and validation
 */

class Auth
{
    private static ?array $currentUser = null;
    private static ?string $currentUserType = null;

    /**
     * Generate JWT token
     */
    public static function generateToken(array $payload, int $expiry = null): string
    {
        $config = $GLOBALS['config']['security'] ?? [];
        $secret = $config['jwt_secret'] ?? 'default_secret_change_me';
        $expiry = $expiry ?? ($config['jwt_expiry'] ?? 3600);

        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $payload['iat'] = time();
        $payload['exp'] = time() + $expiry;

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Generate refresh token
     */
    public static function generateRefreshToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Validate JWT token
     */
    public static function validateToken(string $token): ?array
    {
        $config = $GLOBALS['config']['security'] ?? [];
        $secret = $config['jwt_secret'] ?? 'default_secret_change_me';

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        // Verify signature
        $expectedSignature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $secret, true);
        $expectedSignatureEncoded = self::base64UrlEncode($expectedSignature);

        if (!Crypto::constantTimeCompare($signatureEncoded, $expectedSignatureEncoded)) {
            return null;
        }

        // Decode payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            return null;
        }

        // Check expiration
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return null;
        }

        return $payload;
    }

    /**
     * Get authenticated user from request
     */
    public static function user(): ?array
    {
        if (self::$currentUser !== null) {
            return self::$currentUser;
        }

        $token = Request::bearerToken();
        if (!$token) {
            return null;
        }

        $payload = self::validateToken($token);
        if (!$payload) {
            return null;
        }

        self::$currentUserType = $payload['type'] ?? 'customer';

        // Load full user data from database
        $db = $GLOBALS['db'];

        if (self::$currentUserType === 'staff') {
            self::$currentUser = $db->queryOne(
                'SELECT id, full_name, email, phone, role_id, active FROM staff_users WHERE id = ? AND active = 1',
                [$payload['sub']]
            );
            if (self::$currentUser) {
                // Get role name
                $role = $db->queryOne('SELECT name FROM roles WHERE id = ?', [self::$currentUser['role_id']]);
                self::$currentUser['role'] = $role['name'] ?? 'unknown';
            }
        } else {
            self::$currentUser = $db->queryOne(
                'SELECT id, full_name, phone, email, status FROM users WHERE id = ? AND status = "active"',
                [$payload['sub']]
            );
        }

        if (self::$currentUser) {
            self::$currentUser['token_payload'] = $payload;
        }

        return self::$currentUser;
    }

    /**
     * Check if user is authenticated
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Get user type (customer or staff)
     */
    public static function userType(): ?string
    {
        self::user(); // Ensure user is loaded
        return self::$currentUserType;
    }

    /**
     * Check if current user is staff
     */
    public static function isStaff(): bool
    {
        return self::userType() === 'staff';
    }

    /**
     * Check if current user has specific role
     */
    public static function hasRole(string $role): bool
    {
        $user = self::user();
        if (!$user || !self::isStaff()) {
            return false;
        }
        return ($user['role'] ?? '') === $role;
    }

    /**
     * Check if current user has any of the specified roles
     */
    public static function hasAnyRole(array $roles): bool
    {
        $user = self::user();
        if (!$user || !self::isStaff()) {
            return false;
        }
        return in_array($user['role'] ?? '', $roles, true);
    }

    /**
     * Require authentication - dies if not authenticated
     */
    public static function requireAuth(): array
    {
        $user = self::user();
        if (!$user) {
            Response::unauthorized('Authentication required');
        }
        return $user;
    }

    /**
     * Require staff authentication
     */
    public static function requireStaff(): array
    {
        $user = self::requireAuth();
        if (!self::isStaff()) {
            Response::forbidden('Staff access required');
        }
        return $user;
    }

    /**
     * Require specific role
     */
    public static function requireRole(string $role): array
    {
        $user = self::requireStaff();
        if (!self::hasRole($role)) {
            Response::forbidden("Role '{$role}' required");
        }
        return $user;
    }

    /**
     * Require any of specified roles
     */
    public static function requireAnyRole(array $roles): array
    {
        $user = self::requireStaff();
        if (!self::hasAnyRole($roles)) {
            Response::forbidden('Insufficient permissions');
        }
        return $user;
    }

    /**
     * Hash password
     */
    public static function hashPassword(string $password): string
    {
        $config = $GLOBALS['config']['security'] ?? [];
        $pepper = $config['password_pepper'] ?? '';
        return password_hash($password . $pepper, PASSWORD_ARGON2ID);
    }

    /**
     * Verify password
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        $config = $GLOBALS['config']['security'] ?? [];
        $pepper = $config['password_pepper'] ?? '';
        return password_verify($password . $pepper, $hash);
    }

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Clear cached user (for logout, etc.)
     */
    public static function clear(): void
    {
        self::$currentUser = null;
        self::$currentUserType = null;
    }
}
