<?php
/**
 * Junxtion Customer App - Entry Point
 *
 * Mobile-first PWA customer webapp
 */

$configPath = __DIR__ . '/../private/config.php';
$config = file_exists($configPath) ? require $configPath : [];

$baseUrl = $config['app']['base_url'] ?? 'https://junxtionapp.co.za';
$appName = $config['app']['name'] ?? 'Junxtion';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="description" content="Order delicious food from Junxtion - Fast delivery & pickup">

    <title><?= htmlspecialchars($appName) ?></title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/pwa/manifest.json">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="/assets/img/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/assets/img/favicon-16x16.png">
    <link rel="apple-touch-icon" href="/assets/img/apple-touch-icon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/app.css">

    <style>
        /* Critical CSS - Inline for faster load */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f8f9fa;
            color: #212529;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }
        .loading-screen {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: #fff;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #FF6B35;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        .loading-text {
            margin-top: 16px;
            color: #666;
            font-size: 14px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Screen -->
    <div class="loading-screen" id="loading-screen">
        <div class="loading-spinner"></div>
        <p class="loading-text">Loading...</p>
    </div>

    <!-- App Container -->
    <div id="app">
        <!-- Content will be loaded here -->
    </div>

    <!-- Bottom Navigation (mobile) -->
    <nav id="bottom-nav" class="bottom-nav" style="display:none;">
        <a href="/app/home.php" class="nav-item active" data-page="home">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
            </svg>
            <span>Home</span>
        </a>
        <a href="/app/menu.php" class="nav-item" data-page="menu">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
            <span>Menu</span>
        </a>
        <a href="/app/cart.php" class="nav-item" data-page="cart">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span>Cart</span>
            <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
        </a>
        <a href="/app/orders.php" class="nav-item" data-page="orders">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                <polyline points="14 2 14 8 20 8"></polyline>
            </svg>
            <span>Orders</span>
        </a>
        <a href="/app/profile.php" class="nav-item" data-page="profile">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <span>Profile</span>
        </a>
    </nav>

    <!-- Scripts -->
    <script>
        // App configuration
        window.APP_CONFIG = {
            baseUrl: '<?= $baseUrl ?>',
            apiUrl: '<?= $baseUrl ?>/api',
            name: '<?= htmlspecialchars($appName) ?>'
        };
    </script>
    <script src="/assets/js/app.js"></script>

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/pwa/service-worker.js')
                    .then(function(registration) {
                        console.log('SW registered:', registration.scope);
                    })
                    .catch(function(error) {
                        console.log('SW registration failed:', error);
                    });
            });
        }

        // Hide loading screen when app is ready
        window.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                document.getElementById('loading-screen').style.display = 'none';
                document.getElementById('bottom-nav').style.display = 'flex';
            }, 500);
        });
    </script>
</body>
</html>
