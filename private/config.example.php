<?php
/**
 * Junxtion App Configuration - EXAMPLE
 *
 * Copy this file to config.php and fill in your actual values
 * NEVER commit config.php with real credentials
 */

return [
    // ===========================================
    // DATABASE CONFIGURATION
    // ===========================================
    'db' => [
        'host'     => 'localhost',
        'name'     => 'your_database_name',
        'user'     => 'your_database_user',
        'pass'     => 'your_database_password',
        'charset'  => 'utf8mb4',
        'options'  => [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ],
    ],

    // ===========================================
    // APPLICATION SETTINGS
    // ===========================================
    'app' => [
        'name'         => 'Junxtion',
        'base_url'     => 'https://yourdomain.co.za',
        'api_url'      => 'https://yourdomain.co.za/api',
        'timezone'     => 'Africa/Johannesburg',
        'debug'        => false,
        'environment'  => 'production',
    ],

    // ===========================================
    // YOCO PAYMENT GATEWAY
    // ===========================================
    'yoco' => [
        'secret_key'     => 'sk_test_xxx',
        'public_key'     => 'pk_test_xxx',
        'webhook_secret' => 'whsec_xxx',
        'api_url'        => 'https://payments.yoco.com/api',
        'test_mode'      => true,
    ],

    // ===========================================
    // FCM (Firebase Cloud Messaging)
    // ===========================================
    'fcm' => [
        'project_id'           => 'your-firebase-project',
        'service_account_path' => __DIR__ . '/firebase_sa.json',
        'api_url'              => 'https://fcm.googleapis.com/v1',
    ],

    // ===========================================
    // SMS PROVIDER
    // ===========================================
    'sms' => [
        'provider'   => 'log',
        'api_key'    => '',
        'api_secret' => '',
        'sender_id'  => 'Junxtion',
    ],

    // ===========================================
    // SECURITY
    // ===========================================
    'security' => [
        'jwt_secret'         => 'generate_a_secure_random_string_here',
        'jwt_expiry'         => 3600,
        'refresh_expiry'     => 86400 * 30,
        'otp_expiry'         => 300,
        'otp_max_attempts'   => 5,
        'password_pepper'    => 'change_this_pepper',
        'rate_limit_window'  => 60,
        'rate_limit_max'     => 60,
    ],

    // ===========================================
    // BUSINESS SETTINGS
    // ===========================================
    'business' => [
        'delivery_fee_cents'    => 2500,
        'min_order_cents'       => 5000,
        'service_fee_percent'   => 0,
        'ordering_enabled'      => true,
        'delivery_enabled'      => true,
        'pickup_enabled'        => true,
        'dinein_enabled'        => true,
        'hours' => [
            'monday'    => ['09:00', '21:00'],
            'tuesday'   => ['09:00', '21:00'],
            'wednesday' => ['09:00', '21:00'],
            'thursday'  => ['09:00', '21:00'],
            'friday'    => ['09:00', '22:00'],
            'saturday'  => ['09:00', '22:00'],
            'sunday'    => ['10:00', '20:00'],
        ],
    ],

    // ===========================================
    // FILE UPLOADS
    // ===========================================
    'uploads' => [
        'menu_path'       => __DIR__ . '/../public_html/uploads/menu/',
        'events_path'     => __DIR__ . '/../public_html/uploads/events/',
        'max_size'        => 5 * 1024 * 1024,
        'allowed_types'   => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_exts'    => ['jpg', 'jpeg', 'png', 'webp'],
    ],

    // ===========================================
    // PATHS
    // ===========================================
    'paths' => [
        'private'    => __DIR__,
        'public'     => __DIR__ . '/../public_html',
        'logs'       => __DIR__ . '/logs',
        'cache'      => __DIR__ . '/cache',
        'backups'    => __DIR__ . '/backups',
    ],

    // ===========================================
    // CACHE
    // ===========================================
    'cache' => [
        'menu_ttl'      => 60,
        'enabled'       => true,
        'driver'        => 'file',
    ],
];
