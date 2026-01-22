<?php
/**
 * Junxtion API Entry Point
 *
 * All API requests route through here
 */

define('JUNXTION_API', true);

// Load configuration
$configPath = __DIR__ . '/../private/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'CONFIG_ERROR',
            'message' => 'Server configuration error'
        ]
    ]);
    exit;
}

$GLOBALS['config'] = require $configPath;

// Bootstrap the API
require_once __DIR__ . '/config/bootstrap.php';

// Get request path (remove /api prefix)
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH);
$path = preg_replace('#^/api#', '', $path);
$path = $path ?: '/';

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Load routes
require_once __DIR__ . '/routes/routes.php';

// Route the request (use the global router with registered routes)
try {
    $GLOBALS['router']->dispatch($method, $path);
} catch (PDOException $e) {
    // Database error
    $debug = $GLOBALS['config']['app']['debug'] ?? false;
    Response::error(
        $debug ? 'Database error: ' . $e->getMessage() : 'Database error',
        500,
        'DB_ERROR'
    );
} catch (Exception $e) {
    // General error
    $debug = $GLOBALS['config']['app']['debug'] ?? false;
    Response::error(
        $debug ? $e->getMessage() : 'Internal server error',
        500,
        'SERVER_ERROR'
    );
}
