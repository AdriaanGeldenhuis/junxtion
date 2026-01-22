<?php
/**
 * Cryptographic Utilities
 *
 * Secure hashing, comparison, and random generation
 */

class Crypto
{
    /**
     * Constant-time string comparison
     * Prevents timing attacks
     */
    public static function constantTimeCompare(string $known, string $user): bool
    {
        return hash_equals($known, $user);
    }

    /**
     * Generate secure random bytes
     */
    public static function randomBytes(int $length = 32): string
    {
        return random_bytes($length);
    }

    /**
     * Generate secure random hex string
     */
    public static function randomHex(int $length = 32): string
    {
        return bin2hex(random_bytes($length / 2));
    }

    /**
     * Generate secure random integer
     */
    public static function randomInt(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    /**
     * Generate OTP code
     */
    public static function generateOtp(int $length = 6): string
    {
        $max = pow(10, $length) - 1;
        $otp = random_int(0, $max);
        return str_pad((string) $otp, $length, '0', STR_PAD_LEFT);
    }

    /**
     * Hash OTP code for storage
     */
    public static function hashOtp(string $otp): string
    {
        return hash('sha256', $otp);
    }

    /**
     * Verify OTP code against hash
     */
    public static function verifyOtp(string $otp, string $hash): bool
    {
        return self::constantTimeCompare(self::hashOtp($otp), $hash);
    }

    /**
     * Generate unique device ID
     */
    public static function generateDeviceId(): string
    {
        return 'dev_' . self::randomHex(24);
    }

    /**
     * Generate unique session ID
     */
    public static function generateSessionId(): string
    {
        return 'sess_' . self::randomHex(32);
    }

    /**
     * Generate idempotency key (for payment retries, etc.)
     */
    public static function generateIdempotencyKey(): string
    {
        return 'idem_' . self::randomHex(16);
    }

    /**
     * HMAC SHA256
     */
    public static function hmacSha256(string $data, string $key): string
    {
        return hash_hmac('sha256', $data, $key, true);
    }

    /**
     * HMAC SHA256 base64 encoded
     */
    public static function hmacSha256Base64(string $data, string $key): string
    {
        return base64_encode(self::hmacSha256($data, $key));
    }

    /**
     * Verify Yoco webhook signature
     */
    public static function verifyYocoWebhook(
        string $webhookId,
        string $timestamp,
        string $body,
        string $signature,
        string $secret
    ): bool {
        // Check timestamp is within 3 minutes
        $timestampInt = (int) $timestamp;
        $now = time();
        if (abs($now - $timestampInt) > 180) { // 3 minutes = 180 seconds
            return false;
        }

        // Remove 'whsec_' prefix from secret
        $secret = str_replace('whsec_', '', $secret);
        $secretDecoded = base64_decode($secret);

        // Build signed content
        $signedContent = "{$webhookId}.{$timestamp}.{$body}";

        // Calculate expected signature
        $expectedSignature = self::hmacSha256Base64($signedContent, $secretDecoded);

        // Yoco sends multiple signatures separated by space, check each
        $signatures = explode(' ', $signature);
        foreach ($signatures as $sig) {
            // Remove version prefix (v1, etc.)
            if (strpos($sig, ',') !== false) {
                $sig = explode(',', $sig)[1];
            }
            if (self::constantTimeCompare($expectedSignature, $sig)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate safe filename from user input
     */
    public static function safeFilename(string $filename): string
    {
        // Get extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        $ext = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $ext));

        // Generate unique name
        $uniqueName = self::randomHex(16);

        return $uniqueName . ($ext ? ".{$ext}" : '');
    }

    /**
     * Encrypt data (AES-256-GCM)
     */
    public static function encrypt(string $data, string $key): string
    {
        $key = hash('sha256', $key, true);
        $iv = random_bytes(12);
        $tag = '';

        $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Decrypt data (AES-256-GCM)
     */
    public static function decrypt(string $data, string $key): ?string
    {
        $key = hash('sha256', $key, true);
        $data = base64_decode($data);

        if (strlen($data) < 28) { // 12 (iv) + 16 (tag) minimum
            return null;
        }

        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $encrypted = substr($data, 28);

        $decrypted = openssl_decrypt($encrypted, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $decrypted === false ? null : $decrypted;
    }
}
