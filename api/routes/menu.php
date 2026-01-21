<?php
/**
 * Public Menu Routes
 */

// Get full menu (public, cached)
route_get('/menu', function () {
    $service = new MenuService();
    $menu = $service->getFullMenu();

    Response::success($menu);
});

// Get single item
route_get('/menu/items/{id}', function ($params) {
    $service = new MenuService();
    $item = $service->getItem((int) $params['id']);

    if (!$item) {
        Response::notFound('Item not found');
    }

    Response::success($item);
});

// Get settings (public)
route_get('/settings', function () {
    $service = new SettingsService();
    $settings = $service->getPublicSettings();

    // Add computed values
    $settings['is_open'] = $service->isWithinBusinessHours();
    $settings['ordering_available'] = $service->isOrderingEnabled();

    if (!$settings['ordering_available']) {
        $settings['ordering_message'] = $service->getOrderingPausedMessage();
    }

    Response::success($settings);
});

// Validate promo code
route_post('/promo/validate', function () {
    $data = Request::getJson();

    if (empty($data['code'])) {
        Response::error('Promo code is required', 400);
    }

    $db = $GLOBALS['db'];
    $code = strtoupper(trim($data['code']));

    $promo = $db->queryOne(
        "SELECT id, code, description, discount_type, discount_value,
                min_order_cents, max_discount_cents, applies_to
         FROM promo_codes
         WHERE code = ? AND active = 1
           AND (start_at IS NULL OR start_at <= NOW())
           AND (end_at IS NULL OR end_at >= NOW())
           AND (usage_limit IS NULL OR usage_count < usage_limit)",
        [$code]
    );

    if (!$promo) {
        Response::error('Invalid or expired promo code', 404, 'INVALID_PROMO');
    }

    Response::success([
        'code' => $promo['code'],
        'description' => $promo['description'],
        'discount_type' => $promo['discount_type'],
        'discount_value' => (int) $promo['discount_value'],
        'min_order_cents' => (int) $promo['min_order_cents'],
        'max_discount_cents' => $promo['max_discount_cents'] ? (int) $promo['max_discount_cents'] : null,
        'applies_to' => $promo['applies_to'],
    ]);
});
