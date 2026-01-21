<?php
/**
 * Order Service
 *
 * Handles order creation, status management, and calculations
 */

class OrderService
{
    private Database $db;
    private AuditService $audit;

    // Valid status transitions
    private const STATUS_TRANSITIONS = [
        'PENDING_PAYMENT' => ['PLACED', 'CANCELLED'],
        'PLACED' => ['ACCEPTED', 'CANCELLED'],
        'ACCEPTED' => ['IN_PREP', 'CANCELLED'],
        'IN_PREP' => ['READY', 'CANCELLED'], // Cancel only by manager
        'READY' => ['OUT_FOR_DELIVERY', 'COMPLETED', 'CANCELLED'],
        'OUT_FOR_DELIVERY' => ['COMPLETED', 'CANCELLED'],
        'COMPLETED' => [],
        'CANCELLED' => [],
    ];

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->audit = new AuditService();
    }

    /**
     * Create new order
     */
    public function create(array $data, ?int $userId = null): array
    {
        // Validate order type
        $validTypes = ['delivery', 'pickup', 'dinein'];
        if (!in_array($data['order_type'], $validTypes)) {
            throw new Exception('Invalid order type');
        }

        // Validate items
        if (empty($data['items']) || !is_array($data['items'])) {
            throw new Exception('Order must contain items');
        }

        // Start transaction
        $this->db->beginTransaction();

        try {
            // Calculate order totals
            $calculation = $this->calculateOrder($data);

            // Generate order number
            $orderNumber = $this->generateOrderNumber();

            // Get customer snapshot
            $customerSnapshot = $this->getCustomerSnapshot($userId, $data);

            // Create order
            $orderId = $this->db->insert('orders', [
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'order_type' => $data['order_type'],
                'table_code' => $data['table_code'] ?? null,
                'status' => 'PENDING_PAYMENT',
                'payment_status' => 'pending',
                'subtotal_cents' => $calculation['subtotal'],
                'discount_cents' => $calculation['discount'],
                'delivery_fee_cents' => $calculation['delivery_fee'],
                'service_fee_cents' => $calculation['service_fee'],
                'tip_cents' => $data['tip_cents'] ?? 0,
                'total_cents' => $calculation['total'] + ($data['tip_cents'] ?? 0),
                'customer_phone_snapshot' => $customerSnapshot['phone'],
                'customer_name_snapshot' => $customerSnapshot['name'],
                'customer_email_snapshot' => $customerSnapshot['email'],
                'delivery_address_snapshot' => $customerSnapshot['address'] ? json_encode($customerSnapshot['address']) : null,
                'special_instructions' => $data['special_instructions'] ?? null,
                'promo_code_used' => $calculation['promo_code'] ?? null,
                'estimated_ready_at' => $this->calculateEstimatedReady($calculation['prep_minutes']),
            ]);

            // Create order items
            foreach ($calculation['items'] as $item) {
                $orderItemId = $this->db->insert('order_items', [
                    'order_id' => $orderId,
                    'item_id' => $item['item_id'],
                    'name_snapshot' => $item['name'],
                    'description_snapshot' => $item['description'] ?? null,
                    'price_cents_snapshot' => $item['price'],
                    'qty' => $item['quantity'],
                    'subtotal_cents' => $item['subtotal'],
                    'notes' => $item['notes'] ?? null,
                ]);

                // Create order item modifiers
                if (!empty($item['modifiers'])) {
                    foreach ($item['modifiers'] as $modifier) {
                        $this->db->insert('order_item_modifiers', [
                            'order_item_id' => $orderItemId,
                            'modifier_id' => $modifier['modifier_id'],
                            'name_snapshot' => $modifier['name'],
                            'price_cents_snapshot' => $modifier['price'],
                        ]);
                    }
                }
            }

            // Record status event
            $this->recordStatusEvent($orderId, null, 'PENDING_PAYMENT', null, true);

            // Record promo code usage
            if (!empty($calculation['promo_code_id']) && $userId) {
                $this->recordPromoUsage(
                    $calculation['promo_code_id'],
                    $userId,
                    $orderId,
                    $calculation['discount']
                );
            }

            $this->db->commit();

            $this->audit->log('order.created', 'order', $orderId, null, [
                'order_number' => $orderNumber,
                'total' => $calculation['total'],
            ]);

            return [
                'id' => $orderId,
                'order_number' => $orderNumber,
                'total_cents' => $calculation['total'] + ($data['tip_cents'] ?? 0),
                'status' => 'PENDING_PAYMENT',
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Calculate order totals
     */
    public function calculateOrder(array $data): array
    {
        $subtotal = 0;
        $totalPrepMinutes = 0;
        $itemsWithPrices = [];

        foreach ($data['items'] as $item) {
            // Get item from database
            $dbItem = $this->db->queryOne(
                "SELECT id, name, description, price_cents, prep_minutes, active
                 FROM items WHERE id = ? AND active = 1",
                [$item['item_id']]
            );

            if (!$dbItem) {
                throw new Exception("Item {$item['item_id']} not found or unavailable");
            }

            $quantity = max(1, (int) ($item['quantity'] ?? 1));
            $itemPrice = (int) $dbItem['price_cents'];
            $modifierTotal = 0;
            $modifierDetails = [];

            // Process modifiers
            if (!empty($item['modifiers'])) {
                foreach ($item['modifiers'] as $modifierId) {
                    $modifier = $this->db->queryOne(
                        "SELECT m.id, m.name, m.price_cents_delta, mg.item_id
                         FROM modifiers m
                         INNER JOIN modifier_groups mg ON m.group_id = mg.id
                         WHERE m.id = ? AND m.active = 1 AND mg.item_id = ?",
                        [$modifierId, $item['item_id']]
                    );

                    if ($modifier) {
                        $modifierTotal += (int) $modifier['price_cents_delta'];
                        $modifierDetails[] = [
                            'modifier_id' => (int) $modifier['id'],
                            'name' => $modifier['name'],
                            'price' => (int) $modifier['price_cents_delta'],
                        ];
                    }
                }
            }

            $itemSubtotal = ($itemPrice + $modifierTotal) * $quantity;
            $subtotal += $itemSubtotal;
            $totalPrepMinutes = max($totalPrepMinutes, (int) $dbItem['prep_minutes']);

            $itemsWithPrices[] = [
                'item_id' => (int) $dbItem['id'],
                'name' => $dbItem['name'],
                'description' => $dbItem['description'],
                'price' => $itemPrice,
                'quantity' => $quantity,
                'modifiers' => $modifierDetails,
                'subtotal' => $itemSubtotal,
                'notes' => $item['notes'] ?? null,
            ];
        }

        // Calculate delivery fee
        $deliveryFee = 0;
        if ($data['order_type'] === 'delivery') {
            $deliveryFee = (int) ($GLOBALS['config']['business']['delivery_fee_cents'] ?? 2500);

            // Check free delivery threshold
            $freeThreshold = $this->getSetting('free_delivery_threshold_cents', 15000);
            if ($subtotal >= $freeThreshold) {
                $deliveryFee = 0;
            }
        }

        // Calculate service fee
        $serviceFeePercent = (int) $this->getSetting('service_fee_percent', 0);
        $serviceFee = $serviceFeePercent > 0 ? (int) round($subtotal * $serviceFeePercent / 100) : 0;

        // Calculate discount from promo code
        $discount = 0;
        $promoCodeId = null;
        $promoCode = null;

        if (!empty($data['promo_code'])) {
            $promo = $this->validatePromoCode($data['promo_code'], $subtotal, $data['order_type'], $data['user_id'] ?? null);
            if ($promo) {
                $discount = $promo['discount'];
                $promoCodeId = $promo['id'];
                $promoCode = $promo['code'];
            }
        }

        $total = $subtotal + $deliveryFee + $serviceFee - $discount;

        return [
            'items' => $itemsWithPrices,
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $serviceFee,
            'discount' => $discount,
            'total' => max(0, $total),
            'prep_minutes' => $totalPrepMinutes,
            'promo_code' => $promoCode,
            'promo_code_id' => $promoCodeId,
        ];
    }

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $newStatus, ?int $staffId = null, ?string $notes = null): bool
    {
        $order = $this->getOrder($orderId);
        if (!$order) {
            throw new Exception('Order not found');
        }

        $currentStatus = $order['status'];

        // Validate transition
        if (!$this->isValidTransition($currentStatus, $newStatus)) {
            throw new Exception("Cannot transition from {$currentStatus} to {$newStatus}");
        }

        // Special rules for cancellation
        if ($newStatus === 'CANCELLED') {
            if (in_array($currentStatus, ['IN_PREP', 'READY', 'OUT_FOR_DELIVERY'])) {
                // Only manager can cancel at these stages
                if (!Auth::hasAnyRole(['manager', 'super_admin'])) {
                    throw new Exception('Only managers can cancel orders in preparation or later stages');
                }
            }
        }

        // Update order
        $updateData = [
            'status' => $newStatus,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        if ($newStatus === 'READY') {
            $updateData['actual_ready_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'COMPLETED') {
            $updateData['delivered_at'] = date('Y-m-d H:i:s');
        } elseif ($newStatus === 'CANCELLED') {
            $updateData['cancelled_at'] = date('Y-m-d H:i:s');
            $updateData['cancel_reason'] = $notes;
            $updateData['cancelled_by'] = $staffId ? 'staff' : 'customer';
        }

        $this->db->update('orders', $updateData, 'id = ?', [$orderId]);

        // Record status event
        $this->recordStatusEvent($orderId, $currentStatus, $newStatus, $staffId, false, $notes);

        // Log audit
        $this->audit->log('order.status_changed', 'order', $orderId, [
            'status' => $currentStatus,
        ], [
            'status' => $newStatus,
        ], $staffId);

        return true;
    }

    /**
     * Get single order
     */
    public function getOrder(int $orderId, ?int $userId = null): ?array
    {
        $sql = "SELECT o.*,
                       GROUP_CONCAT(DISTINCT ose.new_status ORDER BY ose.created_at) as status_history
                FROM orders o
                LEFT JOIN order_status_events ose ON o.id = ose.order_id
                WHERE o.id = ?";
        $params = [$orderId];

        if ($userId !== null) {
            $sql .= " AND o.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " GROUP BY o.id";

        $order = $this->db->queryOne($sql, $params);

        if (!$order) {
            return null;
        }

        // Get order items
        $order['items'] = $this->db->query(
            "SELECT oi.*,
                    GROUP_CONCAT(CONCAT(oim.name_snapshot, ':', oim.price_cents_snapshot) SEPARATOR '|') as modifiers
             FROM order_items oi
             LEFT JOIN order_item_modifiers oim ON oi.id = oim.order_item_id
             WHERE oi.order_id = ?
             GROUP BY oi.id",
            [$orderId]
        );

        // Parse modifiers
        foreach ($order['items'] as &$item) {
            $item['modifiers'] = [];
            if ($item['modifiers']) {
                $mods = explode('|', $item['modifiers']);
                foreach ($mods as $mod) {
                    [$name, $price] = explode(':', $mod);
                    $item['modifiers'][] = ['name' => $name, 'price' => (int) $price];
                }
            }
        }

        // Get status events
        $order['status_events'] = $this->db->query(
            "SELECT ose.*, su.full_name as staff_name
             FROM order_status_events ose
             LEFT JOIN staff_users su ON ose.by_staff_id = su.id
             WHERE ose.order_id = ?
             ORDER BY ose.created_at ASC",
            [$orderId]
        );

        // Decode JSON fields
        $order['delivery_address_snapshot'] = $order['delivery_address_snapshot']
            ? json_decode($order['delivery_address_snapshot'], true)
            : null;

        return $order;
    }

    /**
     * Get orders for user
     */
    public function getUserOrders(int $userId, int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $total = (int) $this->db->queryValue(
            "SELECT COUNT(*) FROM orders WHERE user_id = ?",
            [$userId]
        );

        $orders = $this->db->query(
            "SELECT id, order_number, order_type, status, payment_status,
                    total_cents, created_at
             FROM orders
             WHERE user_id = ?
             ORDER BY created_at DESC
             LIMIT ? OFFSET ?",
            [$userId, $perPage, $offset]
        );

        return [
            'items' => $orders,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /**
     * Get live orders for admin
     */
    public function getLiveOrders(?string $since = null, ?string $status = null): array
    {
        $where = ["status NOT IN ('COMPLETED', 'CANCELLED')"];
        $params = [];

        if ($since) {
            $where[] = "updated_at > ?";
            $params[] = $since;
        }

        if ($status) {
            $where[] = "status = ?";
            $params[] = $status;
        }

        $whereClause = implode(' AND ', $where);

        return $this->db->query(
            "SELECT o.*,
                    (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
             FROM orders o
             WHERE {$whereClause}
             ORDER BY
                CASE o.status
                    WHEN 'PLACED' THEN 1
                    WHEN 'ACCEPTED' THEN 2
                    WHEN 'IN_PREP' THEN 3
                    WHEN 'READY' THEN 4
                    WHEN 'OUT_FOR_DELIVERY' THEN 5
                    ELSE 6
                END,
                o.created_at ASC",
            $params
        );
    }

    /**
     * Mark order as paid (called by webhook)
     */
    public function markAsPaid(int $orderId): bool
    {
        $order = $this->getOrder($orderId);
        if (!$order || $order['status'] !== 'PENDING_PAYMENT') {
            return false;
        }

        $this->db->update('orders', [
            'payment_status' => 'paid',
        ], 'id = ?', [$orderId]);

        // Transition to PLACED
        $this->updateStatus($orderId, 'PLACED', null, 'Payment confirmed');

        return true;
    }

    // ========================================
    // Private helper methods
    // ========================================

    private function generateOrderNumber(): string
    {
        $prefix = 'JNX';
        $date = date('ymd');
        $random = str_pad(mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        return "{$prefix}{$date}{$random}";
    }

    private function getCustomerSnapshot(?int $userId, array $data): array
    {
        $snapshot = [
            'phone' => $data['phone'] ?? null,
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['delivery_address'] ?? null,
        ];

        if ($userId) {
            $user = $this->db->queryOne(
                "SELECT full_name, phone, email FROM users WHERE id = ?",
                [$userId]
            );
            if ($user) {
                $snapshot['phone'] = $snapshot['phone'] ?? $user['phone'];
                $snapshot['name'] = $snapshot['name'] ?? $user['full_name'];
                $snapshot['email'] = $snapshot['email'] ?? $user['email'];
            }
        }

        return $snapshot;
    }

    private function calculateEstimatedReady(int $prepMinutes): string
    {
        $baseMinutes = (int) $this->getSetting('estimated_prep_minutes', 20);
        $totalMinutes = max($prepMinutes, $baseMinutes);
        return date('Y-m-d H:i:s', strtotime("+{$totalMinutes} minutes"));
    }

    private function recordStatusEvent(int $orderId, ?string $prev, string $new, ?int $staffId, bool $bySystem, ?string $notes = null): void
    {
        $this->db->insert('order_status_events', [
            'order_id' => $orderId,
            'previous_status' => $prev,
            'new_status' => $new,
            'by_staff_id' => $staffId,
            'by_system' => $bySystem ? 1 : 0,
            'notes' => $notes,
        ]);
    }

    private function isValidTransition(string $current, string $new): bool
    {
        $allowed = self::STATUS_TRANSITIONS[$current] ?? [];
        return in_array($new, $allowed);
    }

    private function validatePromoCode(string $code, int $subtotal, string $orderType, ?int $userId): ?array
    {
        $promo = $this->db->queryOne(
            "SELECT * FROM promo_codes
             WHERE code = ? AND active = 1
               AND (start_at IS NULL OR start_at <= NOW())
               AND (end_at IS NULL OR end_at >= NOW())",
            [strtoupper($code)]
        );

        if (!$promo) {
            return null;
        }

        // Check minimum order
        if ($subtotal < $promo['min_order_cents']) {
            return null;
        }

        // Check order type
        if ($promo['applies_to'] !== 'all' && $promo['applies_to'] !== $orderType) {
            return null;
        }

        // Check usage limit
        if ($promo['usage_limit'] && $promo['usage_count'] >= $promo['usage_limit']) {
            return null;
        }

        // Check per-user limit
        if ($userId && $promo['per_user_limit']) {
            $userUsage = $this->db->queryValue(
                "SELECT COUNT(*) FROM promo_code_usage WHERE promo_code_id = ? AND user_id = ?",
                [$promo['id'], $userId]
            );
            if ($userUsage >= $promo['per_user_limit']) {
                return null;
            }
        }

        // Check first order only
        if ($promo['first_order_only'] && $userId) {
            $orderCount = $this->db->queryValue(
                "SELECT COUNT(*) FROM orders WHERE user_id = ? AND status != 'CANCELLED'",
                [$userId]
            );
            if ($orderCount > 0) {
                return null;
            }
        }

        // Calculate discount
        $discount = 0;
        if ($promo['discount_type'] === 'percentage') {
            $discount = (int) round($subtotal * $promo['discount_value'] / 100);
        } else {
            $discount = (int) $promo['discount_value'];
        }

        // Apply max discount cap
        if ($promo['max_discount_cents'] && $discount > $promo['max_discount_cents']) {
            $discount = (int) $promo['max_discount_cents'];
        }

        return [
            'id' => (int) $promo['id'],
            'code' => $promo['code'],
            'discount' => $discount,
        ];
    }

    private function recordPromoUsage(int $promoId, int $userId, int $orderId, int $discount): void
    {
        $this->db->insert('promo_code_usage', [
            'promo_code_id' => $promoId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_cents' => $discount,
        ]);

        // Increment usage count
        $this->db->exec("UPDATE promo_codes SET usage_count = usage_count + 1 WHERE id = {$promoId}");
    }

    private function getSetting(string $key, $default = null)
    {
        $value = $this->db->queryValue(
            "SELECT value_json FROM settings WHERE `key` = ?",
            [$key]
        );
        return $value ? json_decode($value, true) : $default;
    }
}
