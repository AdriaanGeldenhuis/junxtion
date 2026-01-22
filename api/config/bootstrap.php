<?php
/**
 * API Bootstrap
 *
 * Initializes the API environment
 */

// Prevent direct access
if (!defined('JUNXTION_API')) {
    http_response_code(403);
    die('Direct access not allowed');
}

// Error reporting based on environment
$config = $GLOBALS['config'] ?? [];
if (($config['app']['debug'] ?? false) === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Set timezone
date_default_timezone_set($config['app']['timezone'] ?? 'Africa/Johannesburg');

// Set default headers for JSON API
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// CORS headers (adjust for production)
$allowedOrigins = [
    $config['app']['base_url'] ?? 'https://junxtionapp.co.za',
    'https://junxtionapp.co.za',
    'http://localhost:3000', // Development
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array($origin, $allowedOrigins)) {
    header("Access-Control-Allow-Origin: {$origin}");
}
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// Load helper libraries
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/response.php';
require_once __DIR__ . '/../lib/request.php';
require_once __DIR__ . '/../lib/validator.php';
require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/rate_limit.php';
require_once __DIR__ . '/../lib/crypto.php';

// Load service classes
require_once __DIR__ . '/../services/AuditService.php';
require_once __DIR__ . '/../services/SmsService.php';
require_once __DIR__ . '/../services/AuthCustomerService.php';
require_once __DIR__ . '/../services/AuthStaffService.php';
require_once __DIR__ . '/../services/MenuService.php';
require_once __DIR__ . '/../services/OrderService.php';
require_once __DIR__ . '/../services/SettingsService.php';
require_once __DIR__ . '/../services/NotificationService.php';
require_once __DIR__ . '/../services/FcmService.php';
require_once __DIR__ . '/../services/PaymentYocoService.php';

// Initialize database connection
try {
    $db = Database::getInstance($config['db']);
} catch (PDOException $e) {
    Response::error('Database connection failed', 500, 'DB_ERROR');
}

// Make config and db available globally
$GLOBALS['db'] = $db;
