<?php
/**
 * Custom Error Page
 *
 * Displays user-friendly error messages
 */

$code = isset($_GET['code']) ? (int)$_GET['code'] : 404;

$errors = [
    400 => [
        'title' => 'Bad Request',
        'message' => 'The server could not understand your request.',
        'icon' => 'alert-circle'
    ],
    401 => [
        'title' => 'Unauthorized',
        'message' => 'You need to log in to access this page.',
        'icon' => 'lock'
    ],
    403 => [
        'title' => 'Access Denied',
        'message' => 'You don\'t have permission to access this resource.',
        'icon' => 'shield-off'
    ],
    404 => [
        'title' => 'Page Not Found',
        'message' => 'The page you\'re looking for doesn\'t exist or has been moved.',
        'icon' => 'search'
    ],
    500 => [
        'title' => 'Server Error',
        'message' => 'Something went wrong on our end. Please try again later.',
        'icon' => 'alert-triangle'
    ],
    503 => [
        'title' => 'Service Unavailable',
        'message' => 'We\'re temporarily offline for maintenance. Please check back soon.',
        'icon' => 'tool'
    ]
];

$error = $errors[$code] ?? $errors[404];
http_response_code($code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $code ?> - <?= $error['title'] ?> | Junxtion</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #f8fafc;
        }
        .error-container {
            text-align: center;
            max-width: 500px;
        }
        .error-code {
            font-size: 120px;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(135deg, #FF6B35 0%, #f59e0b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 16px;
        }
        .error-title {
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        .error-message {
            font-size: 16px;
            color: #94a3b8;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .error-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 14px 28px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #FF6B35;
            color: white;
        }
        .btn-primary:hover {
            background: #e55a2b;
            transform: translateY(-2px);
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: white;
            border: 1px solid rgba(255,255,255,0.2);
        }
        .btn-secondary:hover {
            background: rgba(255,255,255,0.2);
        }
        .brand {
            margin-top: 48px;
            color: #64748b;
            font-size: 14px;
        }
        .brand a {
            color: #FF6B35;
            text-decoration: none;
        }
        @media (max-width: 480px) {
            .error-code { font-size: 80px; }
            .error-title { font-size: 22px; }
            .error-actions { flex-direction: column; }
            .btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-code"><?= $code ?></div>
        <h1 class="error-title"><?= htmlspecialchars($error['title']) ?></h1>
        <p class="error-message"><?= htmlspecialchars($error['message']) ?></p>

        <div class="error-actions">
            <a href="/" class="btn btn-primary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                </svg>
                Go Home
            </a>
            <a href="javascript:history.back()" class="btn btn-secondary">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Go Back
            </a>
        </div>

        <p class="brand">
            <a href="/">Junxtion</a> - Delicious food, delivered.
        </p>
    </div>
</body>
</html>
