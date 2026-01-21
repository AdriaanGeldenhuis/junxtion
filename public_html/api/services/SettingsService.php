<?php
/**
 * Settings Service
 *
 * Handles application settings management
 */

class SettingsService
{
    private Database $db;
    private AuditService $audit;
    private static array $cache = [];

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->audit = new AuditService();
    }

    /**
     * Get single setting
     */
    public function get(string $key, $default = null)
    {
        // Check cache first
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        $row = $this->db->queryOne(
            "SELECT value_json FROM settings WHERE `key` = ?",
            [$key]
        );

        if (!$row) {
            return $default;
        }

        $value = json_decode($row['value_json'], true);
        self::$cache[$key] = $value;

        return $value;
    }

    /**
     * Get multiple settings
     */
    public function getMultiple(array $keys): array
    {
        $result = [];

        // Get from cache first
        $uncached = [];
        foreach ($keys as $key) {
            if (isset(self::$cache[$key])) {
                $result[$key] = self::$cache[$key];
            } else {
                $uncached[] = $key;
            }
        }

        if (!empty($uncached)) {
            $placeholders = implode(',', array_fill(0, count($uncached), '?'));
            $rows = $this->db->query(
                "SELECT `key`, value_json FROM settings WHERE `key` IN ({$placeholders})",
                $uncached
            );

            foreach ($rows as $row) {
                $value = json_decode($row['value_json'], true);
                self::$cache[$row['key']] = $value;
                $result[$row['key']] = $value;
            }
        }

        return $result;
    }

    /**
     * Get all settings in a category
     */
    public function getByCategory(string $category, bool $publicOnly = false): array
    {
        $sql = "SELECT `key`, value_json, description FROM settings WHERE category = ?";
        $params = [$category];

        if ($publicOnly) {
            $sql .= " AND is_public = 1";
        }

        $rows = $this->db->query($sql, $params);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = [
                'value' => json_decode($row['value_json'], true),
                'description' => $row['description'],
            ];
        }

        return $result;
    }

    /**
     * Get all public settings (for frontend)
     */
    public function getPublicSettings(): array
    {
        $rows = $this->db->query(
            "SELECT `key`, value_json FROM settings WHERE is_public = 1"
        );

        $result = [];
        foreach ($rows as $row) {
            $result[$row['key']] = json_decode($row['value_json'], true);
        }

        return $result;
    }

    /**
     * Set single setting
     */
    public function set(string $key, $value, ?int $staffId = null): void
    {
        $before = $this->get($key);

        $existing = $this->db->queryOne(
            "SELECT id FROM settings WHERE `key` = ?",
            [$key]
        );

        $valueJson = json_encode($value);

        if ($existing) {
            $this->db->update('settings', [
                'value_json' => $valueJson,
                'updated_by_staff_id' => $staffId,
            ], 'id = ?', [$existing['id']]);
        } else {
            $this->db->insert('settings', [
                'key' => $key,
                'value_json' => $valueJson,
                'category' => 'custom',
                'is_public' => 0,
                'updated_by_staff_id' => $staffId,
            ]);
        }

        // Clear cache
        self::$cache[$key] = $value;

        // Audit log
        $this->audit->log('settings.updated', 'setting', null, [
            'key' => $key,
            'value' => $before,
        ], [
            'key' => $key,
            'value' => $value,
        ], $staffId);
    }

    /**
     * Set multiple settings
     */
    public function setMultiple(array $settings, ?int $staffId = null): void
    {
        foreach ($settings as $key => $value) {
            $this->set($key, $value, $staffId);
        }
    }

    /**
     * Check if ordering is enabled
     */
    public function isOrderingEnabled(): bool
    {
        $enabled = $this->get('ordering_enabled', true);
        $paused = $this->get('ordering_paused', false);

        return $enabled && !$paused;
    }

    /**
     * Get ordering paused message
     */
    public function getOrderingPausedMessage(): string
    {
        return $this->get('ordering_paused_message', 'We are temporarily not accepting orders. Please try again later.');
    }

    /**
     * Check if within business hours
     */
    public function isWithinBusinessHours(): bool
    {
        $hours = $this->get('business_hours', []);
        $dayOfWeek = strtolower(date('l'));

        if (!isset($hours[$dayOfWeek])) {
            return true; // Default to open if not configured
        }

        $dayHours = $hours[$dayOfWeek];

        if ($dayHours['closed'] ?? false) {
            return false;
        }

        $now = date('H:i');
        $open = $dayHours['open'] ?? '00:00';
        $close = $dayHours['close'] ?? '23:59';

        return $now >= $open && $now <= $close;
    }

    /**
     * Get delivery fee
     */
    public function getDeliveryFee(): int
    {
        return (int) $this->get('delivery_fee_cents', 2500);
    }

    /**
     * Get minimum order amount
     */
    public function getMinimumOrder(): int
    {
        return (int) $this->get('min_order_cents', 5000);
    }

    /**
     * Get all settings (admin)
     */
    public function getAllSettings(): array
    {
        return $this->db->query(
            "SELECT s.*, su.full_name as updated_by_name
             FROM settings s
             LEFT JOIN staff_users su ON s.updated_by_staff_id = su.id
             ORDER BY s.category, s.key"
        );
    }

    /**
     * Clear settings cache
     */
    public function clearCache(): void
    {
        self::$cache = [];
    }

    /**
     * Get table codes for dine-in
     */
    public function getTableCodes(): array
    {
        return $this->db->query(
            "SELECT code, table_name, section, capacity
             FROM table_codes
             WHERE active = 1
             ORDER BY section, table_name"
        );
    }

    /**
     * Validate table code
     */
    public function isValidTableCode(string $code): bool
    {
        $count = $this->db->queryValue(
            "SELECT COUNT(*) FROM table_codes WHERE code = ? AND active = 1",
            [$code]
        );
        return $count > 0;
    }
}
