<?php
/**
 * Junxtion App - Main Entry Point
 *
 * Redirects to customer webapp
 */

// Set timezone
date_default_timezone_set('Africa/Johannesburg');

// Load configuration
$configPath = __DIR__ . '/private/config.php';
if (!file_exists($configPath)) {
    http_response_code(500);
    die('Configuration file not found. Please set up config.php');
}

$config = require $configPath;

// Check if this is an API request
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

if (strpos($requestUri, '/api/') === 0) {
    // Route to API
    require __DIR__ . '/api/index.php';
    exit;
}

if (strpos($requestUri, '/admin/') === 0) {
    // Route to Admin
    require __DIR__ . '/admin/index.php';
    exit;
}

// Default: Route to customer app
require __DIR__ . '/app/index.php';
