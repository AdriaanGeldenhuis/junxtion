<?php
/**
 * Staff Authentication Service
 *
 * Handles password-based authentication for staff
 */

class AuthStaffService
{
    private Database $db;
    private AuditService $audit;

    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 30;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->audit = new AuditService();
    }

    /**
     * Staff login
     */
    public function login(string $email, string $password): array
    {
        $email = strtolower(trim($email));

        // Rate limit by IP
        $ipKey = RateLimit::ipKey('staff_login');
        if (!RateLimit::check($ipKey, 10, 300)) {
            throw new Exception('Too many login attempts. Please try again later.');
        }

        // Get staff user
        $staff = $this->db->queryOne(
            "SELECT su.*, r.name as role_name, r.permissions
             FROM staff_users su
             INNER JOIN roles r ON su.role_id = r.id
             WHERE su.email = ?",
            [$email]
        );

        if (!$staff) {
            $this->audit->logActivity('auth.failed_login', "Staff login failed: unknown email {$email}");
            throw new Exception('Invalid email or password');
        }

        // Check if locked out
        if ($staff['locked_until'] && strtotime($staff['locked_until']) > time()) {
            $minutesLeft = ceil((strtotime($staff['locked_until']) - time()) / 60);
            throw new Exception("Account locked. Try again in {$minutesLeft} minutes.");
        }

        // Check if active
        if (!$staff['active']) {
            throw new Exception('Account is disabled. Please contact an administrator.');
        }

        // Verify password
        if (!Auth::verifyPassword($password, $staff['password_hash'])) {
            // Increment login attempts
            $attempts = $staff['login_attempts'] + 1;
            $updateData = ['login_attempts' => $attempts];

            // Lock account if too many attempts
            if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
                $updateData['locked_until'] = date('Y-m-d H:i:s', strtotime('+' . self::LOCKOUT_MINUTES . ' minutes'));
                $this->audit->log('auth.account_locked', 'staff', $staff['id'], null, [
                    'attempts' => $attempts,
                    'locked_until' => $updateData['locked_until'],
                ]);
            }

            $this->db->update('staff_users', $updateData, 'id = ?', [$staff['id']]);

            $this->audit->logActivity('auth.failed_login', "Staff login failed: wrong password for {$email}", null, 'staff', $staff['id']);

            throw new Exception('Invalid email or password');
        }

        // Reset login attempts on successful login
        $this->db->update('staff_users', [
            'login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => Request::ip(),
        ], 'id = ?', [$staff['id']]);

        // Generate tokens
        $accessToken = Auth::generateToken([
            'sub' => $staff['id'],
            'type' => 'staff',
            'email' => $email,
            'role' => $staff['role_name'],
        ]);

        $refreshToken = Auth::generateRefreshToken();
        $refreshTokenHash = hash('sha256', $refreshToken);

        // Create session
        $sessionId = Crypto::generateSessionId();
        $config = $GLOBALS['config']['security'] ?? [];
        $refreshExpiry = $config['refresh_expiry'] ?? 86400 * 30;

        $this->db->insert('sessions', [
            'id' => $sessionId,
            'user_type' => 'staff',
            'user_id' => $staff['id'],
            'refresh_token_hash' => $refreshTokenHash,
            'device_id' => Request::get('device_id'),
            'ip' => Request::ip(),
            'user_agent' => substr(Request::userAgent(), 0, 500),
            'expires_at' => date('Y-m-d H:i:s', time() + $refreshExpiry),
        ]);

        $this->audit->log('auth.login', 'staff', $staff['id'], null, [
            'ip' => Request::ip(),
        ], $staff['id']);

        return [
            'token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => $config['jwt_expiry'] ?? 3600,
            'user' => [
                'id' => (int) $staff['id'],
                'full_name' => $staff['full_name'],
                'email' => $staff['email'],
                'role' => $staff['role_name'],
                'permissions' => json_decode($staff['permissions'], true) ?? [],
            ],
        ];
    }

    /**
     * Refresh staff token
     */
    public function refreshToken(string $refreshToken): array
    {
        $refreshTokenHash = hash('sha256', $refreshToken);

        $session = $this->db->queryOne(
            "SELECT s.*, su.full_name, su.email, su.active, r.name as role_name, r.permissions
             FROM sessions s
             INNER JOIN staff_users su ON s.user_id = su.id
             INNER JOIN roles r ON su.role_id = r.id
             WHERE s.refresh_token_hash = ?
               AND s.user_type = 'staff'
               AND s.revoked_at IS NULL
               AND s.expires_at > NOW()",
            [$refreshTokenHash]
        );

        if (!$session) {
            throw new Exception('Invalid or expired refresh token');
        }

        if (!$session['active']) {
            throw new Exception('Account is disabled');
        }

        // Generate new access token
        $accessToken = Auth::generateToken([
            'sub' => $session['user_id'],
            'type' => 'staff',
            'email' => $session['email'],
            'role' => $session['role_name'],
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
     * Logout staff
     */
    public function logout(?string $refreshToken = null): void
    {
        $user = Auth::user();

        if ($refreshToken) {
            $refreshTokenHash = hash('sha256', $refreshToken);
            $this->db->update('sessions', [
                'revoked_at' => date('Y-m-d H:i:s'),
            ], 'refresh_token_hash = ? AND user_type = ?', [$refreshTokenHash, 'staff']);
        } elseif ($user) {
            // Revoke current session only
            $token = Request::bearerToken();
            if ($token) {
                $payload = Auth::validateToken($token);
                if ($payload) {
                    // Can't revoke JWT, but we can revoke refresh token
                }
            }
        }

        if ($user) {
            $this->audit->log('auth.logout', 'staff', $user['id'], null, null, $user['id']);
        }

        Auth::clear();
    }

    /**
     * Change password
     */
    public function changePassword(int $staffId, string $currentPassword, string $newPassword): void
    {
        $staff = $this->db->queryOne(
            "SELECT id, password_hash FROM staff_users WHERE id = ?",
            [$staffId]
        );

        if (!$staff) {
            throw new Exception('Staff not found');
        }

        // Verify current password
        if (!Auth::verifyPassword($currentPassword, $staff['password_hash'])) {
            throw new Exception('Current password is incorrect');
        }

        // Validate new password
        if (strlen($newPassword) < 8) {
            throw new Exception('Password must be at least 8 characters');
        }

        // Update password
        $this->db->update('staff_users', [
            'password_hash' => Auth::hashPassword($newPassword),
        ], 'id = ?', [$staffId]);

        // Revoke all sessions (force re-login)
        $this->db->update('sessions', [
            'revoked_at' => date('Y-m-d H:i:s'),
        ], 'user_id = ? AND user_type = ?', [$staffId, 'staff']);

        $this->audit->log('auth.password_changed', 'staff', $staffId, null, null, $staffId);
    }

    /**
     * Create staff user (admin only)
     */
    public function createStaff(array $data): int
    {
        // Validate email uniqueness
        $existing = $this->db->queryOne(
            "SELECT id FROM staff_users WHERE email = ?",
            [strtolower($data['email'])]
        );

        if ($existing) {
            throw new Exception('Email already in use');
        }

        // Validate role exists
        $role = $this->db->queryOne(
            "SELECT id FROM roles WHERE id = ?",
            [$data['role_id']]
        );

        if (!$role) {
            throw new Exception('Invalid role');
        }

        $staffId = $this->db->insert('staff_users', [
            'full_name' => $data['full_name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'] ?? null,
            'password_hash' => Auth::hashPassword($data['password']),
            'role_id' => $data['role_id'],
            'active' => $data['active'] ?? 1,
        ]);

        $this->audit->log('staff.created', 'staff', $staffId, null, [
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ]);

        return $staffId;
    }

    /**
     * Update staff user
     */
    public function updateStaff(int $staffId, array $data): bool
    {
        $before = $this->db->queryOne("SELECT * FROM staff_users WHERE id = ?", [$staffId]);

        if (!$before) {
            throw new Exception('Staff not found');
        }

        $allowedFields = ['full_name', 'phone', 'role_id', 'active'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return false;
        }

        $updated = $this->db->update('staff_users', $updateData, 'id = ?', [$staffId]);

        if ($updated) {
            $this->audit->log('staff.updated', 'staff', $staffId, $before, $updateData);
        }

        return $updated > 0;
    }

    /**
     * Reset staff password (admin only)
     */
    public function resetPassword(int $staffId, string $newPassword): void
    {
        $this->db->update('staff_users', [
            'password_hash' => Auth::hashPassword($newPassword),
            'login_attempts' => 0,
            'locked_until' => null,
        ], 'id = ?', [$staffId]);

        // Revoke all sessions
        $this->db->update('sessions', [
            'revoked_at' => date('Y-m-d H:i:s'),
        ], 'user_id = ? AND user_type = ?', [$staffId, 'staff']);

        $this->audit->log('auth.password_reset', 'staff', $staffId);
    }

    /**
     * Get all staff users
     */
    public function getAllStaff(): array
    {
        return $this->db->query(
            "SELECT su.id, su.full_name, su.email, su.phone, su.active,
                    su.last_login_at, su.created_at,
                    r.name as role_name
             FROM staff_users su
             INNER JOIN roles r ON su.role_id = r.id
             ORDER BY su.full_name"
        );
    }

    /**
     * Get all roles
     */
    public function getRoles(): array
    {
        return $this->db->query("SELECT * FROM roles ORDER BY id");
    }
}
