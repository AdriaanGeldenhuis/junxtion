<?php
/**
 * Admin Notification Routes
 */

// Send broadcast notification
route_post('/admin/notifications/broadcast', function () {
    $user = Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('title', 'Title is required')
        ->required('body', 'Body is required')
        ->maxLength('title', 255);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new NotificationService();
    $notificationId = $service->sendBroadcast($data, $user['id']);

    Response::success(['notification_id' => $notificationId], 201);
}, ['staff']);

// Get notification history
route_get('/admin/notifications', function () {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $db = $GLOBALS['db'];

    $page = max(1, (int) (Request::query('page') ?? 1));
    $perPage = min(100, (int) (Request::query('per_page') ?? 20));
    $offset = ($page - 1) * $perPage;

    $total = (int) $db->queryValue("SELECT COUNT(*) FROM notifications");

    $notifications = $db->query(
        "SELECT n.*, su.full_name as created_by_name
         FROM notifications n
         LEFT JOIN staff_users su ON n.created_by_staff_id = su.id
         ORDER BY n.created_at DESC
         LIMIT ? OFFSET ?",
        [$perPage, $offset]
    );

    Response::success([
        'items' => $notifications,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
    ]);
}, ['staff']);

// Get single notification with delivery stats
route_get('/admin/notifications/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $db = $GLOBALS['db'];

    $notification = $db->queryOne(
        "SELECT n.*, su.full_name as created_by_name
         FROM notifications n
         LEFT JOIN staff_users su ON n.created_by_staff_id = su.id
         WHERE n.id = ?",
        [$params['id']]
    );

    if (!$notification) {
        Response::notFound('Notification not found');
    }

    // Get delivery stats
    $stats = $db->queryOne(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
            SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered,
            SUM(CASE WHEN status = 'clicked' THEN 1 ELSE 0 END) as clicked,
            SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed
         FROM notification_deliveries
         WHERE notification_id = ?",
        [$params['id']]
    );

    $notification['delivery_stats'] = $stats;

    Response::success($notification);
}, ['staff']);

// Cancel scheduled notification
route_post('/admin/notifications/{id}/cancel', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $db = $GLOBALS['db'];

    $notification = $db->queryOne(
        "SELECT id, send_status FROM notifications WHERE id = ?",
        [$params['id']]
    );

    if (!$notification) {
        Response::notFound('Notification not found');
    }

    if ($notification['send_status'] !== 'scheduled') {
        Response::error('Only scheduled notifications can be cancelled', 400);
    }

    $db->update('notifications', [
        'send_status' => 'cancelled',
    ], 'id = ?', [$params['id']]);

    Response::success(['message' => 'Notification cancelled']);
}, ['staff']);

// Get settings (admin)
route_get('/admin/settings', function () {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $service = new SettingsService();
    $settings = $service->getAllSettings();

    Response::success($settings);
}, ['staff']);

// Update settings
route_put('/admin/settings', function () {
    $user = Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    if (empty($data) || !is_array($data)) {
        Response::error('Settings data is required', 400);
    }

    $service = new SettingsService();
    $service->setMultiple($data, $user['id']);

    Response::success(['message' => 'Settings updated']);
}, ['staff']);

// Toggle ordering pause
route_post('/admin/settings/toggle-ordering', function () {
    $user = Auth::requireAnyRole(['manager', 'super_admin']);

    $service = new SettingsService();
    $currentPaused = $service->get('ordering_paused', false);

    $service->set('ordering_paused', !$currentPaused, $user['id']);

    Response::success([
        'ordering_paused' => !$currentPaused,
        'message' => !$currentPaused ? 'Ordering paused' : 'Ordering resumed',
    ]);
}, ['staff']);

// Get audit logs
route_get('/admin/audit', function () {
    Auth::requireRole('super_admin');

    $filters = [
        'staff_id' => Request::query('staff_id'),
        'action' => Request::query('action'),
        'entity_type' => Request::query('entity_type'),
        'entity_id' => Request::query('entity_id'),
        'from_date' => Request::query('from_date'),
        'to_date' => Request::query('to_date'),
    ];

    $page = max(1, (int) (Request::query('page') ?? 1));
    $perPage = min(100, (int) (Request::query('per_page') ?? 50));

    $service = new AuditService();
    $logs = $service->getAuditLogs(array_filter($filters), $page, $perPage);

    Response::success($logs);
}, ['staff']);

// Get promo codes
route_get('/admin/promo-codes', function () {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $db = $GLOBALS['db'];
    $codes = $db->query(
        "SELECT p.*, su.full_name as created_by_name
         FROM promo_codes p
         LEFT JOIN staff_users su ON p.created_by_staff_id = su.id
         ORDER BY p.created_at DESC"
    );

    Response::success($codes);
}, ['staff']);

// Create promo code
route_post('/admin/promo-codes', function () {
    $user = Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('code', 'Code is required')
        ->required('discount_type', 'Discount type is required')
        ->required('discount_value', 'Discount value is required')
        ->in('discount_type', ['percentage', 'fixed']);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $db = $GLOBALS['db'];

    // Check if code exists
    $existing = $db->queryOne(
        "SELECT id FROM promo_codes WHERE code = ?",
        [strtoupper($data['code'])]
    );

    if ($existing) {
        Response::error('Promo code already exists', 400);
    }

    $id = $db->insert('promo_codes', [
        'code' => strtoupper($data['code']),
        'description' => $data['description'] ?? null,
        'discount_type' => $data['discount_type'],
        'discount_value' => $data['discount_value'],
        'min_order_cents' => $data['min_order_cents'] ?? 0,
        'max_discount_cents' => $data['max_discount_cents'] ?? null,
        'usage_limit' => $data['usage_limit'] ?? null,
        'per_user_limit' => $data['per_user_limit'] ?? 1,
        'applies_to' => $data['applies_to'] ?? 'all',
        'first_order_only' => $data['first_order_only'] ?? 0,
        'start_at' => $data['start_at'] ?? null,
        'end_at' => $data['end_at'] ?? null,
        'active' => $data['active'] ?? 1,
        'created_by_staff_id' => $user['id'],
    ]);

    Response::success(['id' => $id], 201);
}, ['staff']);

// Update promo code
route_put('/admin/promo-codes/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $db = $GLOBALS['db'];
    $db->update('promo_codes', $data, 'id = ?', [$params['id']]);

    Response::success(['message' => 'Promo code updated']);
}, ['staff']);

// Delete promo code
route_delete('/admin/promo-codes/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $db = $GLOBALS['db'];
    $db->delete('promo_codes', 'id = ?', [$params['id']]);

    Response::success(['message' => 'Promo code deleted']);
}, ['staff']);
