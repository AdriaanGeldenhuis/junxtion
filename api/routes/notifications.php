<?php
/**
 * Notification Routes (Customer & Staff)
 */

// Register device for push notifications
route_post('/notifications/register-device', function () {
    $user = Auth::requireAuth();
    $data = Request::getJson();

    $validator = Validator::make($data)
        ->required('device_id', 'Device ID is required')
        ->required('fcm_token', 'FCM token is required');

    if ($validator->fails()) {
        Response::validationError($validator->errors());
    }

    $userType = Auth::isStaff() ? 'staff' : 'customer';

    $service = new NotificationService();
    $deviceId = $service->registerDevice($data, $userType, $user['id']);

    Response::success(['device_id' => $deviceId]);
}, ['auth']);

// Unregister device
route_post('/notifications/unregister-device', function () {
    $data = Request::getJson();

    if (empty($data['device_id'])) {
        Response::error('Device ID is required', 400);
    }

    $service = new NotificationService();
    $service->unregisterDevice($data['device_id']);

    Response::success(['message' => 'Device unregistered']);
});

// Get notification preferences
route_get('/notifications/preferences', function () {
    $user = Auth::requireAuth();
    $userType = Auth::isStaff() ? 'staff' : 'customer';

    $service = new NotificationService();
    $prefs = $service->getPreferences($userType, $user['id']);

    Response::success($prefs);
}, ['auth']);

// Update notification preferences
route_put('/notifications/preferences', function () {
    $user = Auth::requireAuth();
    $data = Request::getJson();
    $userType = Auth::isStaff() ? 'staff' : 'customer';

    $service = new NotificationService();
    $service->updatePreferences($userType, $user['id'], $data);

    Response::success(['message' => 'Preferences updated']);
}, ['auth']);

// Get inbox
route_get('/notifications/inbox', function () {
    $user = Auth::requireAuth();
    $userType = Auth::isStaff() ? 'staff' : 'customer';

    $page = max(1, (int) (Request::query('page') ?? 1));
    $perPage = min(50, (int) (Request::query('per_page') ?? 20));

    $service = new NotificationService();
    $inbox = $service->getInbox($userType, $user['id'], $page, $perPage);

    Response::success($inbox);
}, ['auth']);

// Mark message as read
route_post('/notifications/inbox/{id}/read', function ($params) {
    $user = Auth::requireAuth();
    $userType = Auth::isStaff() ? 'staff' : 'customer';

    $service = new NotificationService();
    $service->markAsRead((int) $params['id'], $userType, $user['id']);

    Response::success(['message' => 'Marked as read']);
}, ['auth']);

// Mark all as read
route_post('/notifications/inbox/read-all', function () {
    $user = Auth::requireAuth();
    $userType = Auth::isStaff() ? 'staff' : 'customer';

    $service = new NotificationService();
    $count = $service->markAllAsRead($userType, $user['id']);

    Response::success(['marked_count' => $count]);
}, ['auth']);
