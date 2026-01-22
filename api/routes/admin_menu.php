<?php
/**
 * Admin Menu Routes
 */

// Get all categories (admin)
route_get('/admin/menu/categories', function () {
    Auth::requireStaff();

    $service = new MenuService();
    $categories = $service->getAllCategories();

    Response::success($categories);
}, ['staff']);

// Create category
route_post('/admin/menu/categories', function () {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('name', 'Name is required')
        ->maxLength('name', 100);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new MenuService();
    $id = $service->createCategory($data);

    Response::success(['id' => $id], 201);
}, ['staff']);

// Update category
route_put('/admin/menu/categories/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $service = new MenuService();
    $service->updateCategory((int) $params['id'], $data);

    Response::success(['message' => 'Category updated']);
}, ['staff']);

// Delete category
route_delete('/admin/menu/categories/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $service = new MenuService();
    $service->deleteCategory((int) $params['id']);

    Response::success(['message' => 'Category deleted']);
}, ['staff']);

// Get all items (admin)
route_get('/admin/menu/items', function () {
    Auth::requireStaff();

    $categoryId = Request::query('category_id');

    $service = new MenuService();
    $items = $service->getAllItems($categoryId ? (int) $categoryId : null);

    Response::success($items);
}, ['staff']);

// Create item
route_post('/admin/menu/items', function () {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('category_id', 'Category is required')
        ->required('name', 'Name is required')
        ->required('price_cents', 'Price is required')
        ->positive('price_cents', 'Price must be positive')
        ->maxLength('name', 150);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new MenuService();
    $id = $service->createItem($data);

    Response::success(['id' => $id], 201);
}, ['staff']);

// Update item
route_put('/admin/menu/items/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $service = new MenuService();
    $service->updateItem((int) $params['id'], $data);

    Response::success(['message' => 'Item updated']);
}, ['staff']);

// Toggle item availability
route_post('/admin/menu/items/{id}/toggle', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin', 'cashier']);

    $service = new MenuService();
    $service->toggleItem((int) $params['id']);

    Response::success(['message' => 'Item availability toggled']);
}, ['staff']);

// Delete item
route_delete('/admin/menu/items/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $service = new MenuService();
    $service->deleteItem((int) $params['id']);

    Response::success(['message' => 'Item deleted']);
}, ['staff']);

// Upload item image
route_post('/admin/menu/items/{id}/image', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $file = Request::file('image');
    if (!$file) {
        Response::error('No image uploaded', 400);
    }

    $service = new MenuService();
    $imagePath = $service->uploadImage($file);

    // Update item with new image
    $service->updateItem((int) $params['id'], ['image_path' => $imagePath]);

    Response::success(['image_path' => $imagePath]);
}, ['staff']);

// Create modifier group
route_post('/admin/menu/items/{id}/modifier-groups', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('name', 'Name is required');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new MenuService();
    $id = $service->createModifierGroup((int) $params['id'], $data);

    Response::success(['id' => $id], 201);
}, ['staff']);

// Create modifier
route_post('/admin/menu/modifier-groups/{id}/modifiers', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('name', 'Name is required');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new MenuService();
    $id = $service->createModifier((int) $params['id'], $data);

    Response::success(['id' => $id], 201);
}, ['staff']);

// Clear menu cache
route_post('/admin/menu/clear-cache', function () {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $service = new MenuService();
    $service->clearCache();

    Response::success(['message' => 'Menu cache cleared']);
}, ['staff']);

// Get specials (admin)
route_get('/admin/specials', function () {
    Auth::requireStaff();

    $db = $GLOBALS['db'];
    $specials = $db->query("SELECT * FROM specials ORDER BY sort_order ASC, created_at DESC");

    Response::success($specials);
}, ['staff']);

// Create special
route_post('/admin/specials', function () {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('title', 'Title is required');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $db = $GLOBALS['db'];
    $id = $db->insert('specials', [
        'title' => $data['title'],
        'body' => $data['body'] ?? null,
        'image_path' => $data['image_path'] ?? null,
        'discount_type' => $data['discount_type'] ?? 'none',
        'discount_value' => $data['discount_value'] ?? 0,
        'promo_code' => $data['promo_code'] ?? null,
        'start_at' => $data['start_at'] ?? null,
        'end_at' => $data['end_at'] ?? null,
        'active' => $data['active'] ?? 1,
        'sort_order' => $data['sort_order'] ?? 0,
    ]);

    // Clear menu cache
    $menuService = new MenuService();
    $menuService->clearCache();

    Response::success(['id' => $id], 201);
}, ['staff']);

// Update special
route_put('/admin/specials/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);
    $data = Request::getJson();

    $db = $GLOBALS['db'];
    $db->update('specials', $data, 'id = ?', [$params['id']]);

    // Clear menu cache
    $menuService = new MenuService();
    $menuService->clearCache();

    Response::success(['message' => 'Special updated']);
}, ['staff']);

// Delete special
route_delete('/admin/specials/{id}', function ($params) {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $db = $GLOBALS['db'];
    $db->delete('specials', 'id = ?', [$params['id']]);

    // Clear menu cache
    $menuService = new MenuService();
    $menuService->clearCache();

    Response::success(['message' => 'Special deleted']);
}, ['staff']);
