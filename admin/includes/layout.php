<?php
/**
 * Admin Layout Template
 *
 * Usage:
 * require_once 'includes/layout.php';
 * admin_header('Page Title', 'page-name');
 * // ... page content
 * admin_footer();
 */

session_start();

$configPath = __DIR__ . '/../../private/config.php';
$config = file_exists($configPath) ? require $configPath : [];

$baseUrl = $config['app']['base_url'] ?? 'https://junxtionapp.co.za';
$appName = $config['app']['name'] ?? 'Junxtion';

function admin_header($title, $activePage = '') {
    global $baseUrl, $appName;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?= htmlspecialchars($title) ?> - <?= htmlspecialchars($appName) ?> Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <aside class="admin-sidebar" id="sidebar">
            <div class="logo"><?= htmlspecialchars($appName) ?></div>
            <ul class="nav-menu">
                <li>
                    <a href="/admin/dashboard.php" class="<?= $activePage === 'dashboard' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="/admin/orders.php" class="<?= $activePage === 'orders' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Orders
                    </a>
                </li>
                <li>
                    <a href="/admin/kitchen.php" class="<?= $activePage === 'kitchen' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/><polyline points="9,22 9,12 15,12 15,22"/></svg>
                        Kitchen
                    </a>
                </li>
                <div class="nav-divider"></div>
                <li>
                    <a href="/admin/menu.php" class="<?= $activePage === 'menu' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                        Menu
                    </a>
                </li>
                <li>
                    <a href="/admin/specials.php" class="<?= $activePage === 'specials' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12,2 15,8.5 22,9.3 17,14 18.2,21 12,17.8 5.8,21 7,14 2,9.3 9,8.5"/></svg>
                        Specials
                    </a>
                </li>
                <li>
                    <a href="/admin/promo-codes.php" class="<?= $activePage === 'promo-codes' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        Promo Codes
                    </a>
                </li>
                <div class="nav-divider"></div>
                <li>
                    <a href="/admin/notifications.php" class="<?= $activePage === 'notifications' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8A6 6 0 006 8c0 7-3 9-3 9h18s-3-2-3-9M13.73 21a2 2 0 01-3.46 0"/></svg>
                        Notifications
                    </a>
                </li>
                <li>
                    <a href="/admin/staff.php" class="<?= $activePage === 'staff' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/></svg>
                        Staff
                    </a>
                </li>
                <li>
                    <a href="/admin/settings.php" class="<?= $activePage === 'settings' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33H9a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82V9a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                        Settings
                    </a>
                </li>
                <div class="nav-divider"></div>
                <li>
                    <a href="#" onclick="AdminAPI.logout(); return false;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                        Logout
                    </a>
                </li>
            </ul>
        </aside>

        <main class="admin-main">
            <header class="admin-header">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <button class="menu-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12h18M3 6h18M3 18h18"/></svg>
                    </button>
                    <h1><?= htmlspecialchars($title) ?></h1>
                </div>
                <div id="header-actions"></div>
            </header>
            <div class="admin-content">
<?php
}

function admin_footer() {
?>
            </div>
        </main>
    </div>

    <script src="/assets/js/admin.js"></script>
</body>
</html>
<?php
}
