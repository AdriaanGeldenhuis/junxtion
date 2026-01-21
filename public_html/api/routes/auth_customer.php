<?php
/**
 * Customer Authentication Routes
 */

// Request OTP
route_post('/auth/otp/request', function () {
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('phone', 'Phone number is required')
        ->phone('phone', 'Invalid phone number format');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new AuthCustomerService();
    $result = $service->requestOtp($data['phone']);

    Response::success($result);
}, ['rate_limit_strict']);

// Verify OTP
route_post('/auth/otp/verify', function () {
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('phone', 'Phone number is required')
        ->required('code', 'OTP code is required')
        ->minLength('code', 6, 'OTP must be 6 digits')
        ->maxLength('code', 6, 'OTP must be 6 digits');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new AuthCustomerService();
    $result = $service->verifyOtp($data['phone'], $data['code']);

    Response::success($result);
}, ['rate_limit_strict']);

// Refresh token
route_post('/auth/refresh', function () {
    $data = Request::getJson();

    if (empty($data['refresh_token'])) {
        Response::error('Refresh token is required', 400);
    }

    $service = new AuthCustomerService();
    $result = $service->refreshToken($data['refresh_token']);

    Response::success($result);
});

// Logout
route_post('/auth/logout', function () {
    $data = Request::getJson();
    $refreshToken = $data['refresh_token'] ?? null;

    $service = new AuthCustomerService();
    $service->logout($refreshToken);

    Response::success(['message' => 'Logged out successfully']);
});

// Get current user profile
route_get('/auth/me', function () {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::success([
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'] ?? null,
            'role' => $user['role'],
            'type' => 'staff',
        ]);
    }

    Response::success([
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'phone' => $user['phone'],
        'email' => $user['email'],
        'type' => 'customer',
    ]);
}, ['auth']);

// Update profile
route_put('/auth/profile', function () {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden('Use admin endpoints for staff profile');
    }

    $data = Request::getJson();

    $validator = Validator::make($data)
        ->maxLength('full_name', 100)
        ->email('email');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new AuthCustomerService();
    $result = $service->updateProfile($user['id'], $data);

    Response::success($result);
}, ['auth']);

// Get addresses
route_get('/auth/addresses', function () {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden();
    }

    $service = new AuthCustomerService();
    $addresses = $service->getAddresses($user['id']);

    Response::success($addresses);
}, ['auth']);

// Add address
route_post('/auth/addresses', function () {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden();
    }

    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('address_line1', 'Address is required')
        ->required('city', 'City is required')
        ->maxLength('label', 50)
        ->maxLength('address_line1', 255)
        ->maxLength('city', 100);

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $service = new AuthCustomerService();
    $addressId = $service->addAddress($user['id'], $data);

    Response::success(['id' => $addressId], 201);
}, ['auth']);

// Update address
route_put('/auth/addresses/{id}', function ($params) {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden();
    }

    $data = Request::getJson();

    $service = new AuthCustomerService();
    $service->updateAddress($user['id'], (int) $params['id'], $data);

    Response::success(['message' => 'Address updated']);
}, ['auth']);

// Delete address
route_delete('/auth/addresses/{id}', function ($params) {
    $user = Auth::requireAuth();

    if (Auth::isStaff()) {
        Response::forbidden();
    }

    $service = new AuthCustomerService();
    $service->deleteAddress($user['id'], (int) $params['id']);

    Response::success(['message' => 'Address deleted']);
}, ['auth']);
