<?php
/**
 * Customer Order Routes
 */

// Calculate order (preview)
route_post('/orders/calculate', function () {
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('order_type')
        ->in('order_type', ['delivery', 'pickup', 'dinein'])
        ->notEmptyArray('items');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    // Check if ordering is available
    $settings = new SettingsService();
    if (!$settings->isOrderingEnabled()) {
        Response::error($settings->getOrderingPausedMessage(), 400, 'ORDERING_DISABLED');
    }

    $service = new OrderService();
    $calculation = $service->calculateOrder($data);

    Response::success([
        'subtotal' => $calculation['subtotal'],
        'delivery_fee' => $calculation['delivery_fee'],
        'service_fee' => $calculation['service_fee'],
        'discount' => $calculation['discount'],
        'total' => $calculation['total'],
        'promo_code' => $calculation['promo_code'],
        'estimated_prep_minutes' => $calculation['prep_minutes'],
    ]);
});

// Create order
route_post('/orders', function () {
    $user = Auth::user(); // Optional auth
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('order_type')
        ->in('order_type', ['delivery', 'pickup', 'dinein'])
        ->notEmptyArray('items');

    // Require auth for all orders (enforced)
    if (!$user) {
        Response::unauthorized('Please log in to place an order');
    }

    // Validate delivery address for delivery orders
    if ($data['order_type'] === 'delivery') {
        if (empty($data['delivery_address'])) {
            Response::validationError(['delivery_address' => 'Delivery address is required']);
        }
    }

    // Validate table code for dine-in
    if ($data['order_type'] === 'dinein') {
        if (empty($data['table_code'])) {
            Response::validationError(['table_code' => 'Table code is required']);
        }
        $settings = new SettingsService();
        if (!$settings->isValidTableCode($data['table_code'])) {
            Response::validationError(['table_code' => 'Invalid table code']);
        }
    }

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    // Check if ordering is available
    $settings = new SettingsService();
    if (!$settings->isOrderingEnabled()) {
        Response::error($settings->getOrderingPausedMessage(), 400, 'ORDERING_DISABLED');
    }

    // Check minimum order
    $minOrder = $settings->getMinimumOrder();
    $orderService = new OrderService();
    $calculation = $orderService->calculateOrder($data);

    if ($calculation['subtotal'] < $minOrder) {
        $minOrderRands = number_format($minOrder / 100, 2);
        Response::error("Minimum order amount is R{$minOrderRands}", 400, 'MINIMUM_ORDER');
    }

    $order = $orderService->create($data, $user['id'] ?? null);

    Response::success($order, 201);
});

// Get user's orders
route_get('/orders', function () {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden('Use admin endpoints for staff');
    }

    $page = (int) (Request::query('page') ?? 1);
    $perPage = min(50, (int) (Request::query('per_page') ?? 20));

    $service = new OrderService();
    $orders = $service->getUserOrders($user['id'], $page, $perPage);

    Response::success($orders);
}, ['auth']);

// Get single order
route_get('/orders/{id}', function ($params) {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden('Use admin endpoints for staff');
    }

    $service = new OrderService();
    $order = $service->getOrder((int) $params['id'], $user['id']);

    if (!$order) {
        Response::notFound('Order not found');
    }

    Response::success($order);
}, ['auth']);

// Get order by order number (for tracking without full auth)
route_get('/orders/track/{orderNumber}', function ($params) {
    $db = $GLOBALS['db'];

    $order = $db->queryOne(
        "SELECT id, order_number, order_type, status, payment_status,
                total_cents, estimated_ready_at, created_at
         FROM orders
         WHERE order_number = ?",
        [$params['orderNumber']]
    );

    if (!$order) {
        Response::notFound('Order not found');
    }

    // Get status events for timeline
    $events = $db->query(
        "SELECT new_status, created_at
         FROM order_status_events
         WHERE order_id = ?
         ORDER BY created_at ASC",
        [$order['id']]
    );

    $order['status_timeline'] = $events;

    Response::success($order);
});

// Cancel order (customer)
route_post('/orders/{id}/cancel', function ($params) {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden('Use admin endpoints for staff');
    }

    $service = new OrderService();
    $order = $service->getOrder((int) $params['id'], $user['id']);

    if (!$order) {
        Response::notFound('Order not found');
    }

    // Only allow cancellation before preparation
    if (!in_array($order['status'], ['PENDING_PAYMENT', 'PLACED', 'ACCEPTED'])) {
        Response::error('Order cannot be cancelled at this stage', 400, 'CANCEL_NOT_ALLOWED');
    }

    $data = Request::getJson();
    $service->updateStatus((int) $params['id'], 'CANCELLED', null, $data['reason'] ?? 'Cancelled by customer');

    Response::success(['message' => 'Order cancelled']);
}, ['auth']);

// Create payment for order
route_post('/orders/{id}/pay', function ($params) {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden();
    }

    $orderService = new OrderService();
    $order = $orderService->getOrder((int) $params['id'], $user['id']);

    if (!$order) {
        Response::notFound('Order not found');
    }

    if ($order['status'] !== 'PENDING_PAYMENT') {
        Response::error('Order is not pending payment', 400);
    }

    $paymentService = new PaymentYocoService();
    $result = $paymentService->createCheckout((int) $params['id']);

    Response::success($result);
}, ['auth']);
