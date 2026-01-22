<?php
/**
 * Customer Authentication Service
 *
 * Handles OTP-based authentication for customers
 */

class AuthCustomerService
{
    private Database $db;
    private SmsService $sms;
    private AuditService $audit;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->sms = new SmsService();
        $this->audit = new AuditService();
    }

    /**
     * Request OTP for phone number
     */
    public function requestOtp(string $phone): array
    {
        $phone = Validator::normalizePhone($phone);

        // Rate limiting
        $rateLimitKey = RateLimit::phoneKey($phone, 'otp');
        if (!RateLimit::check($rateLimitKey, 5, 300)) { // 5 requests per 5 minutes
            throw new Exception('Too many OTP requests. Please wait before trying again.');
        }

        // Check IP rate limit too
        $ipKey = RateLimit::ipKey('otp');
        if (!RateLimit::check($ipKey, 20, 300)) {
            throw new Exception('Too many requests from this IP.');
        }

        // Generate OTP
        $otp = Crypto::generateOtp(6);
        $otpHash = Crypto::hashOtp($otp);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        // Invalidate previous OTPs for this phone
        $this->db->update('otp_codes', [
            'verified_at' => date('Y-m-d H:i:s'),
        ], 'phone = ? AND verified_at IS NULL', [$phone]);

        // Store new OTP
        $this->db->insert('otp_codes', [
            'phone' => $phone,
            'code_hash' => $otpHash,
            'purpose' => 'login',
            'expires_at' => $expiresAt,
            'max_attempts' => 5,
        ]);

        // Send OTP via SMS
        $result = $this->sms->sendOtp($phone, $otp);

        $this->audit->logActivity('auth.otp_requested', "OTP requested for {$phone}", [
            'phone' => $phone,
            'sms_provider' => $result['provider'] ?? 'unknown',
        ]);

        return [
            'message' => 'OTP sent successfully',
            'expires_in' => 300, // 5 minutes
            // Include OTP in response only in log mode (development)
            'debug_otp' => $this->sms->isLogMode() ? $otp : null,
        ];
    }

    /**
     * Verify OTP and authenticate
     */
    public function verifyOtp(string $phone, string $code): array
    {
        $phone = Validator::normalizePhone($phone);

        // Get latest OTP for this phone
        $otp = $this->db->queryOne(
            "SELECT * FROM otp_codes
             WHERE phone = ? AND purpose = 'login' AND verified_at IS NULL
             ORDER BY created_at DESC
             LIMIT 1",
            [$phone]
        );

        if (!$otp) {
            throw new Exception('No OTP found. Please request a new code.');
        }

        // Check expiry
        if (strtotime($otp['expires_at']) < time()) {
            throw new Exception('OTP has expired. Please request a new code.');
        }

        // Check attempts
        if ($otp['attempts'] >= $otp['max_attempts']) {
            throw new Exception('Too many failed attempts. Please request a new code.');
        }

        // Verify code (constant-time comparison)
        if (!Crypto::verifyOtp($code, $otp['code_hash'])) {
            // Increment attempts
            $this->db->exec("UPDATE otp_codes SET attempts = attempts + 1 WHERE id = {$otp['id']}");
            throw new Exception('Invalid OTP code.');
        }

        // Mark OTP as verified
        $this->db->update('otp_codes', [
            'verified_at' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$otp['id']]);

        // Find or create user
        $user = $this->db->queryOne(
            "SELECT * FROM users WHERE phone = ?",
            [$phone]
        );

        if (!$user) {
            // Create new user
            $userId = $this->db->insert('users', [
                'full_name' => '',
                'phone' => $phone,
                'status' => 'active',
            ]);
            $user = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);
            $isNewUser = true;
        } else {
            $isNewUser = false;

            // Check if user is suspended
            if ($user['status'] === 'suspended') {
                throw new Exception('Your account has been suspended. Please contact support.');
            }
        }

        // Generate tokens
        $accessToken = Auth::generateToken([
            'sub' => $user['id'],
            'type' => 'customer',
            'phone' => $phone,
        ]);

        $refreshToken = Auth::generateRefreshToken();
        $refreshTokenHash = hash('sha256', $refreshToken);

        // Create session
        $sessionId = Crypto::generateSessionId();
        $config = $GLOBALS['config']['security'] ?? [];
        $refreshExpiry = $config['refresh_expiry'] ?? 86400 * 30;

        $this->db->insert('sessions', [
            'id' => $sessionId,
            'user_type' => 'customer',
            'user_id' => $user['id'],
            'refresh_token_hash' => $refreshTokenHash,
            'device_id' => Request::get('device_id'),
            'ip' => Request::ip(),
            'user_agent' => substr(Request::userAgent(), 0, 500),
            'expires_at' => date('Y-m-d H:i:s', time() + $refreshExpiry),
        ]);

        $this->audit->logActivity('auth.login', "Customer logged in: {$phone}", [
            'user_id' => $user['id'],
        ], 'customer', $user['id']);

        return [
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $config['jwt_expiry'] ?? 3600,
            'user' => [
                'id' => (int) $user['id'],
                'full_name' => $user['full_name'],
                'phone' => $user['phone'],
                'email' => $user['email'],
                'is_new' => $isNewUser,
            ],
        ];
    }

    /**
     * Refresh access token
     */
    public function refreshToken(string $refreshToken): array
    {
        $refreshTokenHash = hash('sha256', $refreshToken);

        $session = $this->db->queryOne(
            "SELECT s.*, u.full_name, u.phone, u.email, u.status
             FROM sessions s
             INNER JOIN users u ON s.user_id = u.id
             WHERE s.refresh_token_hash = ?
               AND s.user_type = 'customer'
               AND s.revoked_at IS NULL
               AND s.expires_at > NOW()",
            [$refreshTokenHash]
        );

        if (!$session) {
            throw new Exception('Invalid or expired refresh token');
        }

        if ($session['status'] !== 'active') {
            throw new Exception('Account is not active');
        }

        // Generate new access token
        $accessToken = Auth::generateToken([
            'sub' => $session['user_id'],
            'type' => 'customer',
            'phone' => $session['phone'],
        ]);

        // Update session last used
        $this->db->update('sessions', [
            'last_used_at' => date('Y-m-d H:i:s'),
            'ip' => Request::ip(),
        ], 'id = ?', [$session['id']]);

        $config = $GLOBALS['config']['security'] ?? [];

        return [
            'token' => $accessToken,
            'expires_in' => $config['jwt_expiry'] ?? 3600,
        ];
    }

    /**
     * Logout - revoke session
     */
    public function logout(?string $refreshToken = null): void
    {
        $user = Auth::user();

        if ($refreshToken) {
            $refreshTokenHash = hash('sha256', $refreshToken);
            $this->db->update('sessions', [
                'revoked_at' => date('Y-m-d H:i:s'),
            ], 'refresh_token_hash = ? AND user_type = ?', [$refreshTokenHash, 'customer']);
        } elseif ($user) {
            // Revoke all sessions for this user
            $this->db->update('sessions', [
                'revoked_at' => date('Y-m-d H:i:s'),
            ], 'user_id = ? AND user_type = ?', [$user['id'], 'customer']);
        }

        if ($user) {
            $this->audit->logActivity('auth.logout', 'Customer logged out', null, 'customer', $user['id']);
        }

        Auth::clear();
    }

    /**
     * Update customer profile
     */
    public function updateProfile(int $userId, array $data): array
    {
        $allowedFields = ['full_name', 'email'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            throw new Exception('No valid fields to update');
        }

        // Validate email if provided
        if (isset($updateData['email']) && !empty($updateData['email'])) {
            if (!filter_var($updateData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email address');
            }
        }

        $before = $this->db->queryOne("SELECT * FROM users WHERE id = ?", [$userId]);

        $this->db->update('users', $updateData, 'id = ?', [$userId]);

        $this->audit->log('user.updated', 'user', $userId, $before, $updateData, null, $userId);

        return $this->db->queryOne(
            "SELECT id, full_name, phone, email FROM users WHERE id = ?",
            [$userId]
        );
    }

    /**
     * Get customer addresses
     */
    public function getAddresses(int $userId): array
    {
        return $this->db->query(
            "SELECT * FROM user_addresses WHERE user_id = ? ORDER BY is_default DESC, created_at DESC",
            [$userId]
        );
    }

    /**
     * Add customer address
     */
    public function addAddress(int $userId, array $data): int
    {
        // If this is set as default, unset other defaults
        if (!empty($data['is_default'])) {
            $this->db->update('user_addresses', ['is_default' => 0], 'user_id = ?', [$userId]);
        }

        return $this->db->insert('user_addresses', [
            'user_id' => $userId,
            'label' => $data['label'] ?? 'Home',
            'address_line1' => $data['address_line1'],
            'address_line2' => $data['address_line2'] ?? null,
            'suburb' => $data['suburb'] ?? null,
            'city' => $data['city'],
            'postal_code' => $data['postal_code'] ?? null,
            'instructions' => $data['instructions'] ?? null,
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'is_default' => $data['is_default'] ?? 0,
        ]);
    }

    /**
     * Update customer address
     */
    public function updateAddress(int $userId, int $addressId, array $data): bool
    {
        // Verify ownership
        $address = $this->db->queryOne(
            "SELECT id FROM user_addresses WHERE id = ? AND user_id = ?",
            [$addressId, $userId]
        );

        if (!$address) {
            throw new Exception('Address not found');
        }

        // If setting as default, unset others
        if (!empty($data['is_default'])) {
            $this->db->update('user_addresses', ['is_default' => 0], 'user_id = ?', [$userId]);
        }

        return $this->db->update('user_addresses', $data, 'id = ?', [$addressId]) > 0;
    }

    /**
     * Delete customer address
     */
    public function deleteAddress(int $userId, int $addressId): bool
    {
        return $this->db->delete('user_addresses', 'id = ? AND user_id = ?', [$addressId, $userId]) > 0;
    }
}
