<?php
/**
 * Staff Authentication Routes
 */

// Staff login
route_post('/admin/auth/login', function () {
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('email', 'Email is required')
        ->required('password', 'Password is required')
        ->email('email', 'Invalid email format');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new AuthStaffService();
    $result = $service->login($data['email'], $data['password']);

    Response::success($result);
}, ['rate_limit_strict']);

// Staff refresh token
route_post('/admin/auth/refresh', function () {
    $data = Request::getJson();

    if (empty($data['refresh_token'])) {
        Response::error('Refresh token is required', 400);
    }

    $service = new AuthStaffService();
    $result = $service->refreshToken($data['refresh_token']);

    Response::success($result);
});

// Staff logout
route_post('/admin/auth/logout', function () {
    $data = Request::getJson();
    $refreshToken = $data['refresh_token'] ?? null;

    $service = new AuthStaffService();
    $service->logout($refreshToken);

    Response::success(['message' => 'Logged out successfully']);
});

// Change password
route_post('/admin/auth/change-password', function () {
    $user = Auth::requireStaff();
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('current_password', 'Current password is required')
        ->required('new_password', 'New password is required')
        ->minLength('new_password', 8, 'Password must be at least 8 characters');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new AuthStaffService();
    $service->changePassword($user['id'], $data['current_password'], $data['new_password']);

    Response::success(['message' => 'Password changed successfully']);
}, ['staff']);

// Get all staff (manager+)
route_get('/admin/staff', function () {
    Auth::requireAnyRole(['manager', 'super_admin']);

    $service = new AuthStaffService();
    $staff = $service->getAllStaff();

    Response::success($staff);
}, ['staff']);

// Create staff (super_admin only)
route_post('/admin/staff', function () {
    Auth::requireRole('super_admin');
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('full_name', 'Name is required')
        ->required('email', 'Email is required')
        ->required('password', 'Password is required')
        ->required('role_id', 'Role is required')
        ->email('email')
        ->minLength('password', 8);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new AuthStaffService();
    $staffId = $service->createStaff($data);

    Response::success(['id' => $staffId], 201);
}, ['staff']);

// Update staff (super_admin only)
route_put('/admin/staff/{id}', function ($params) {
    Auth::requireRole('super_admin');
    $data = Request::getJson();

    $service = new AuthStaffService();
    $service->updateStaff((int) $params['id'], $data);

    Response::success(['message' => 'Staff updated']);
}, ['staff']);

// Reset staff password (super_admin only)
route_post('/admin/staff/{id}/reset-password', function ($params) {
    Auth::requireRole('super_admin');
    $data = Request::getJson();

    if (empty($data['new_password'])) {
        Response::error('New password is required', 400);
    }

    if (strlen($data['new_password']) < 8) {
        Response::error('Password must be at least 8 characters', 400);
    }

    $service = new AuthStaffService();
    $service->resetPassword((int) $params['id'], $data['new_password']);

    Response::success(['message' => 'Password reset successfully']);
}, ['staff']);

// Get roles
route_get('/admin/roles', function () {
    Auth::requireStaff();

    $service = new AuthStaffService();
    $roles = $service->getRoles();

    Response::success($roles);
}, ['staff']);
