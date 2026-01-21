<?php
/**
 * Admin Order Routes
 */

// Get live orders (for kitchen/admin display)
route_get('/admin/orders/live', function () {
    Auth::requireStaff();

    $since = Request::query('since');
    $status = Request::query('status');

    $service = new OrderService();
    $orders = $service->getLiveOrders($since, $status);

    Response::success([
        'orders' => $orders,
        'timestamp' => date('c'),
    ]);
}, ['staff']);

// Get all orders (paginated)
route_get('/admin/orders', function () {
    Auth::requireStaff();

    $db = $GLOBALS['db'];

    $page = max(1, (int) (Request::query('page') ?? 1));
    $perPage = min(100, (int) (Request::query('per_page') ?? 50));
    $status = Request::query('status');
    $orderType = Request::query('order_type');
    $dateFrom = Request::query('date_from');
    $dateTo = Request::query('date_to');

    $where = ['1=1'];
    $params = [];

    if ($status) {
        $where[] = 'status = ?';
        $params[] = $status;
    }

    if ($orderType) {
        $where[] = 'order_type = ?';
        $params[] = $orderType;
    }

    if ($dateFrom) {
        $where[] = 'created_at >= ?';
        $params[] = $dateFrom;
    }

    if ($dateTo) {
        $where[] = 'created_at <= ?';
        $params[] = $dateTo . ' 23:59:59';
    }

    $whereClause = implode(' AND ', $where);
    $offset = ($page - 1) * $perPage;

    $total = (int) $db->queryValue(
        "SELECT COUNT(*) FROM orders WHERE {$whereClause}",
        $params
    );

    $orders = $db->query(
        "SELECT o.*,
                (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count
         FROM orders o
         WHERE {$whereClause}
         ORDER BY o.created_at DESC
         LIMIT ? OFFSET ?",
        array_merge($params, [$perPage, $offset])
    );

    Response::success([
        'items' => $orders,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => ceil($total / $perPage),
    ]);
}, ['staff']);

// Get single order (admin)
route_get('/admin/orders/{id}', function ($params) {
    Auth::requireStaff();

    $service = new OrderService();
    $order = $service->getOrder((int) $params['id']);

    if (!$order) {
        Response::notFound('Order not found');
    }

    Response::success($order);
}, ['staff']);

// Update order status
route_post('/admin/orders/{id}/status', function ($params) {
    $user = Auth::requireStaff();
    $data = Request::getJson();

    if (empty($data['status'])) {
        Response::error('Status is required', 400);
    }

    $validStatuses = ['PLACED', 'ACCEPTED', 'IN_PREP', 'READY', 'OUT_FOR_DELIVERY', 'COMPLETED', 'CANCELLED'];
    if (!in_array($data['status'], $validStatuses)) {
        Response::error('Invalid status', 400);
    }

    $service = new OrderService();
    $service->updateStatus(
        (int) $params['id'],
        $data['status'],
        $user['id'],
        $data['notes'] ?? null
    );

    // Send notification to customer
    $notificationService = new NotificationService();
    $notificationService->sendOrderNotification((int) $params['id'], $data['status']);

    Response::success(['message' => 'Order status updated']);
}, ['staff']);

// Assign delivery driver
route_post('/admin/orders/{id}/assign-driver', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    if (empty($data['driver_id'])) {
        Response::error('Driver ID is required', 400);
    }

    $db = $GLOBALS['db'];

    // Verify driver exists and has delivery role
    $driver = $db->queryOne(
        "SELECT su.id FROM staff_users su
         INNER JOIN roles r ON su.role_id = r.id
         WHERE su.id = ? AND r.name = 'delivery' AND su.active = 1",
        [$data['driver_id']]
    );

    if (!$driver) {
        Response::error('Invalid driver', 400);
    }

    // Create or update assignment
    $existing = $db->queryOne(
        "SELECT id FROM delivery_assignments WHERE order_id = ?",
        [$params['id']]
    );

    if ($existing) {
        $db->update('delivery_assignments', [
            'driver_id' => $data['driver_id'],
            'status' => 'assigned',
        ], 'id = ?', [$existing['id']]);
    } else {
        $db->insert('delivery_assignments', [
            'order_id' => (int) $params['id'],
            'driver_id' => $data['driver_id'],
            'status' => 'assigned',
        ]);
    }

    // Notify driver
    $notificationService = new NotificationService();
    $notificationService->sendToUser('staff', $data['driver_id'], [
        'title' => 'New Delivery Assigned',
        'body' => "You have a new delivery order #{$params['id']}",
    ], [
        'type' => 'delivery_assigned',
        'order_id' => $params['id'],
    ]);

    Response::success(['message' => 'Driver assigned']);
}, ['staff']);

// Get order stats (dashboard)
route_get('/admin/orders/stats', function () {
    Auth::requireStaff();

    $db = $GLOBALS['db'];

    // Today's stats
    $today = date('Y-m-d');

    $todayOrders = $db->queryOne(
        "SELECT
            COUNT(*) as total,
            SUM(CASE WHEN status = 'COMPLETED' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status NOT IN ('COMPLETED', 'CANCELLED') THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN payment_status = 'paid' THEN total_cents ELSE 0 END) as revenue
         FROM orders
         WHERE DATE(created_at) = ?",
        [$today]
    );

    // Pending orders count
    $pending = $db->queryValue(
        "SELECT COUNT(*) FROM orders WHERE status IN ('PLACED', 'ACCEPTED', 'IN_PREP')"
    );

    // Ready for pickup/delivery
    $ready = $db->queryValue(
        "SELECT COUNT(*) FROM orders WHERE status IN ('READY', 'OUT_FOR_DELIVERY')"
    );

    Response::success([
        'today' => [
            'total_orders' => (int) $todayOrders['total'],
            'completed_orders' => (int) $todayOrders['completed'],
            'active_orders' => (int) $todayOrders['active'],
            'revenue_cents' => (int) $todayOrders['revenue'],
        ],
        'pending_orders' => (int) $pending,
        'ready_orders' => (int) $ready,
    ]);
}, ['staff']);

// Initiate refund
route_post('/admin/orders/{id}/refund', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $data = Request::getJson();

    $service = new PaymentYocoService();
    $result = $service->initiateRefund(
        (int) $params['id'],
        $data['amount_cents'] ?? null,
        $data['reason'] ?? null
    );

    Response::success($result);
}, ['staff']);
