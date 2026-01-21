<?php
/**
 * Notification Service
 *
 * Handles push notifications, inbox, and notification preferences
 */

class NotificationService
{
    private Database $db;
    private FcmService $fcm;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->fcm = new FcmService();
    }

    /**
     * Register device for push notifications
     */
    public function registerDevice(array $data, string $userType, int $userId): int
    {
        $deviceId = $data['device_id'];
        $fcmToken = $data['fcm_token'];

        // Check if device already exists
        $existing = $this->db->queryOne(
            "SELECT id FROM devices WHERE device_id = ?",
            [$deviceId]
        );

        if ($existing) {
            // Update existing
            $this->db->update('devices', [
                'user_type' => $userType,
                'user_id' => $userId,
                'fcm_token' => $fcmToken,
                'platform' => $data['platform'] ?? 'android',
                'app_version' => $data['app_version'] ?? null,
                'device_model' => $data['device_model'] ?? null,
                'os_version' => $data['os_version'] ?? null,
                'enabled' => 1,
                'last_seen_at' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$existing['id']]);

            return $existing['id'];
        }

        // Create new
        return $this->db->insert('devices', [
            'user_type' => $userType,
            'user_id' => $userId,
            'device_id' => $deviceId,
            'fcm_token' => $fcmToken,
            'platform' => $data['platform'] ?? 'android',
            'app_version' => $data['app_version'] ?? null,
            'device_model' => $data['device_model'] ?? null,
            'os_version' => $data['os_version'] ?? null,
            'enabled' => 1,
        ]);
    }

    /**
     * Unregister device
     */
    public function unregisterDevice(string $deviceId): bool
    {
        return $this->db->delete('devices', 'device_id = ?', [$deviceId]) > 0;
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(string $userType, int $userId, array $prefs): void
    {
        $existing = $this->db->queryOne(
            "SELECT id FROM notification_preferences WHERE user_type = ? AND user_id = ?",
            [$userType, $userId]
        );

        $data = [
            'orders_enabled' => $prefs['orders_enabled'] ?? 1,
            'specials_enabled' => $prefs['specials_enabled'] ?? 1,
            'announcements_enabled' => $prefs['announcements_enabled'] ?? 1,
            'marketing_enabled' => $prefs['marketing_enabled'] ?? 0,
            'quiet_start' => $prefs['quiet_start'] ?? null,
            'quiet_end' => $prefs['quiet_end'] ?? null,
        ];

        if ($existing) {
            $this->db->update('notification_preferences', $data, 'id = ?', [$existing['id']]);
        } else {
            $data['user_type'] = $userType;
            $data['user_id'] = $userId;
            $this->db->insert('notification_preferences', $data);
        }
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(string $userType, int $userId): array
    {
        $prefs = $this->db->queryOne(
            "SELECT * FROM notification_preferences WHERE user_type = ? AND user_id = ?",
            [$userType, $userId]
        );

        return $prefs ?: [
            'orders_enabled' => true,
            'specials_enabled' => true,
            'announcements_enabled' => true,
            'marketing_enabled' => false,
            'quiet_start' => null,
            'quiet_end' => null,
        ];
    }

    /**
     * Send order notification
     */
    public function sendOrderNotification(int $orderId, string $status): void
    {
        $order = $this->db->queryOne(
            "SELECT o.*, u.full_name as customer_name
             FROM orders o
             LEFT JOIN users u ON o.user_id = u.id
             WHERE o.id = ?",
            [$orderId]
        );

        if (!$order) {
            return;
        }

        // Notification content based on status
        $notifications = [
            'PLACED' => [
                'title' => 'Order Received!',
                'body' => "Your order #{$order['order_number']} has been received. We'll start preparing it soon.",
            ],
            'ACCEPTED' => [
                'title' => 'Order Accepted',
                'body' => "Your order #{$order['order_number']} has been accepted!",
            ],
            'IN_PREP' => [
                'title' => 'Preparing Your Order',
                'body' => "Your order #{$order['order_number']} is being prepared.",
            ],
            'READY' => [
                'title' => 'Order Ready!',
                'body' => $order['order_type'] === 'pickup'
                    ? "Your order #{$order['order_number']} is ready for pickup!"
                    : "Your order #{$order['order_number']} is ready!",
            ],
            'OUT_FOR_DELIVERY' => [
                'title' => 'On Its Way!',
                'body' => "Your order #{$order['order_number']} is out for delivery.",
            ],
            'COMPLETED' => [
                'title' => 'Order Complete',
                'body' => "Thank you! Your order #{$order['order_number']} is complete.",
            ],
            'CANCELLED' => [
                'title' => 'Order Cancelled',
                'body' => "Your order #{$order['order_number']} has been cancelled.",
            ],
        ];

        $notification = $notifications[$status] ?? null;
        if (!$notification) {
            return;
        }

        // Send to customer
        if ($order['user_id']) {
            $this->sendToUser('customer', $order['user_id'], $notification, [
                'type' => 'order_update',
                'order_id' => (string) $orderId,
                'order_number' => $order['order_number'],
                'status' => $status,
                'deeplink' => "/app/track.php?order={$order['order_number']}",
            ], 'orders');
        }

        // Notify staff of new orders
        if ($status === 'PLACED') {
            $this->notifyStaffNewOrder($order);
        }
    }

    /**
     * Notify staff of new order
     */
    private function notifyStaffNewOrder(array $order): void
    {
        $notification = [
            'title' => 'New Order!',
            'body' => "Order #{$order['order_number']} - R" . number_format($order['total_cents'] / 100, 2),
        ];

        $data = [
            'type' => 'new_order',
            'order_id' => (string) $order['id'],
            'order_number' => $order['order_number'],
            'order_type' => $order['order_type'],
            'deeplink' => "/admin/order_view.php?id={$order['id']}",
        ];

        // Get all staff devices
        $devices = $this->db->query(
            "SELECT d.fcm_token
             FROM devices d
             INNER JOIN staff_users su ON d.user_id = su.id AND d.user_type = 'staff'
             WHERE d.enabled = 1 AND su.active = 1"
        );

        foreach ($devices as $device) {
            $this->fcm->sendToDevice($device['fcm_token'], $notification, $data);
        }
    }

    /**
     * Send notification to specific user
     */
    public function sendToUser(string $userType, int $userId, array $notification, array $data = [], ?string $category = null): int
    {
        // Check preferences if category specified
        if ($category) {
            $prefs = $this->getPreferences($userType, $userId);
            $prefKey = $category . '_enabled';
            if (isset($prefs[$prefKey]) && !$prefs[$prefKey]) {
                return 0; // User opted out
            }

            // Check quiet hours
            if ($prefs['quiet_start'] && $prefs['quiet_end']) {
                $now = date('H:i');
                if ($now >= $prefs['quiet_start'] || $now <= $prefs['quiet_end']) {
                    // In quiet hours - skip push but still add to inbox
                    $this->addToInbox($userType, $userId, $notification, $data);
                    return 0;
                }
            }
        }

        // Get user's devices
        $devices = $this->db->query(
            "SELECT id, fcm_token FROM devices WHERE user_type = ? AND user_id = ? AND enabled = 1",
            [$userType, $userId]
        );

        $sent = 0;
        foreach ($devices as $device) {
            $result = $this->fcm->sendToDevice($device['fcm_token'], $notification, $data);

            if ($result['success']) {
                $sent++;
            } elseif (!empty($result['should_remove_token'])) {
                // Token invalid - disable device
                $this->db->update('devices', ['enabled' => 0], 'id = ?', [$device['id']]);
            }
        }

        // Add to inbox
        $this->addToInbox($userType, $userId, $notification, $data);

        return $sent;
    }

    /**
     * Add notification to user's inbox
     */
    private function addToInbox(string $userType, int $userId, array $notification, array $data = [], ?int $notificationId = null): int
    {
        return $this->db->insert('inbox_messages', [
            'user_type' => $userType,
            'user_id' => $userId,
            'notification_id' => $notificationId,
            'title' => $notification['title'],
            'body' => $notification['body'],
            'image_path' => $notification['image'] ?? null,
            'deeplink' => $data['deeplink'] ?? null,
        ]);
    }

    /**
     * Get user's inbox
     */
    public function getInbox(string $userType, int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->queryValue(
            "SELECT COUNT(*) FROM inbox_messages WHERE user_type = ? AND user_id = ? AND archived_at IS NULL",
            [$userType, $userId]
        );

        $messages = $this->db->query(
            "SELECT id, title, body, image_path, deeplink, read_at, created_at
             FROM inbox_messages
             WHERE user_type = ? AND user_id = ? AND archived_at IS NULL
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$userType, $userId, $perPage, $offset]
        );

        $unreadCount = (int) $this->db->queryValue(
            "SELECT COUNT(*) FROM inbox_messages WHERE user_type = ? AND user_id = ? AND read_at IS NULL AND archived_at IS NULL",
            [$userType, $userId]
        );

        return [
            'items' => $messages,
            'unread_count' => $unreadCount,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Mark message as read
     */
    public function markAsRead(int $messageId, string $userType, int $userId): bool
    {
        return $this->db->update('inbox_messages', [
            'read_at' => date('Y-m-d H:i:s'),
        ], 'id = ? AND user_type = ? AND user_id = ?', [$messageId, $userType, $userId]) > 0;
    }

    /**
     * Mark all as read
     */
    public function markAllAsRead(string $userType, int $userId): int
    {
        return $this->db->update('inbox_messages', [
            'read_at' => date('Y-m-d H:i:s'),
        ], 'user_type = ? AND user_id = ? AND read_at IS NULL', [$userType, $userId]);
    }

    /**
     * Send broadcast notification (admin)
     */
    public function sendBroadcast(array $data, int $staffId): int
    {
        // Create notification record
        $notificationId = $this->db->insert('notifications', [
            'audience_type' => $data['audience_type'] ?? 'all_customers',
            'audience_json' => isset($data['audience']) ? json_encode($data['audience']) : null,
            'title' => $data['title'],
            'body' => $data['body'],
            'image_path' => $data['image_path'] ?? null,
            'deeplink' => $data['deeplink'] ?? null,
            'data_json' => isset($data['data']) ? json_encode($data['data']) : null,
            'priority' => $data['priority'] ?? 'normal',
            'created_by_staff_id' => $staffId,
            'schedule_at' => $data['schedule_at'] ?? null,
            'send_status' => $data['schedule_at'] ? 'scheduled' : 'sending',
        ]);

        // If not scheduled, send now
        if (empty($data['schedule_at'])) {
            $this->processBroadcast($notificationId);
        }

        return $notificationId;
    }

    /**
     * Process broadcast sending
     */
    public function processBroadcast(int $notificationId): array
    {
        $notification = $this->db->queryOne(
            "SELECT * FROM notifications WHERE id = ?",
            [$notificationId]
        );

        if (!$notification || $notification['send_status'] === 'sent') {
            return ['error' => 'Notification not found or already sent'];
        }

        // Get target devices
        $devices = $this->getTargetDevices($notification);

        $sentCount = 0;
        $failedCount = 0;

        foreach ($devices as $device) {
            $result = $this->fcm->sendToDevice(
                $device['fcm_token'],
                [
                    'title' => $notification['title'],
                    'body' => $notification['body'],
                    'image' => $notification['image_path'],
                ],
                [
                    'notification_id' => (string) $notificationId,
                    'deeplink' => $notification['deeplink'],
                ]
            );

            // Record delivery
            $this->db->insert('notification_deliveries', [
                'notification_id' => $notificationId,
                'device_id' => $device['id'],
                'user_type' => $device['user_type'],
                'user_id' => $device['user_id'],
                'status' => $result['success'] ? 'sent' : 'failed',
                'fcm_message_id' => $result['message_id'] ?? null,
                'error_message' => $result['error'] ?? null,
                'sent_at' => date('Y-m-d H:i:s'),
            ]);

            // Add to inbox
            $this->addToInbox(
                $device['user_type'],
                $device['user_id'],
                ['title' => $notification['title'], 'body' => $notification['body'], 'image' => $notification['image_path']],
                ['deeplink' => $notification['deeplink']],
                $notificationId
            );

            if ($result['success']) {
                $sentCount++;
            } else {
                $failedCount++;
            }
        }

        // Update notification status
        $this->db->update('notifications', [
            'send_status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s'),
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
        ], 'id = ?', [$notificationId]);

        return [
            'sent' => $sentCount,
            'failed' => $failedCount,
        ];
    }

    /**
     * Get target devices for notification
     */
    private function getTargetDevices(array $notification): array
    {
        switch ($notification['audience_type']) {
            case 'all_customers':
                return $this->db->query(
                    "SELECT d.id, d.fcm_token, d.user_type, d.user_id
                     FROM devices d
                     WHERE d.user_type = 'customer' AND d.enabled = 1"
                );

            case 'all_staff':
                return $this->db->query(
                    "SELECT d.id, d.fcm_token, d.user_type, d.user_id
                     FROM devices d
                     WHERE d.user_type = 'staff' AND d.enabled = 1"
                );

            case 'specific_users':
                $audience = json_decode($notification['audience_json'], true);
                $userIds = $audience['user_ids'] ?? [];
                $userType = $audience['user_type'] ?? 'customer';

                if (empty($userIds)) {
                    return [];
                }

                $placeholders = implode(',', array_fill(0, count($userIds), '?'));
                return $this->db->query(
                    "SELECT d.id, d.fcm_token, d.user_type, d.user_id
                     FROM devices d
                     WHERE d.user_type = ? AND d.user_id IN ({$placeholders}) AND d.enabled = 1",
                    array_merge([$userType], $userIds)
                );

            default:
                return [];
        }
    }
}
