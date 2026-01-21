<?php
/**
 * Junxtion Admin Panel - Entry Point
 *
 * Staff management interface
 */

session_start();

$configPath = __DIR__ . '/../../private/config.php';
$config = file_exists($configPath) ? require $configPath : [];

$baseUrl = $config['app']['base_url'] ?? 'https://junxtionapp.co.za';
$appName = $config['app']['name'] ?? 'Junxtion';

// Check if logged in (will redirect to login if not)
$isLoggedIn = isset($_SESSION['staff_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">

    <title>Admin - <?= htmlspecialchars($appName) ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Admin Styles -->
    <link rel="stylesheet" href="/assets/css/admin.css">

    <style>
        /* Critical CSS */
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f1f5f9;
            color: #1e293b;
            min-height: 100vh;
        }
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: 250px;
            background: #1e293b;
            color: #fff;
            padding: 20px 0;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            overflow-y: auto;
            z-index: 1000;
        }
        .admin-main {
            flex: 1;
            margin-left: 250px;
            padding: 20px;
        }
        .admin-header {
            background: #fff;
            padding: 16px 24px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #FF6B35;
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .nav-menu {
            list-style: none;
            margin-top: 20px;
        }
        .nav-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            color: #94a3b8;
            text-decoration: none;
            transition: all 0.2s;
        }
        .nav-menu a:hover, .nav-menu a.active {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .login-form {
            max-width: 400px;
            margin: 100px auto;
            padding: 40px;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }
        .login-form h1 {
            text-align: center;
            margin-bottom: 30px;
            color: #1e293b;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #475569;
        }
        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.2s;
        }
        .form-group input:focus {
            outline: none;
            border-color: #FF6B35;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #FF6B35;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background 0.2s;
        }
        .btn:hover {
            background: #e55a2b;
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fef2f2;
            color: #dc2626;
            border: 1px solid #fecaca;
        }
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
            }
            .admin-sidebar.open {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
<?php if (!$isLoggedIn): ?>
    <!-- Login Form -->
    <div class="login-form">
        <h1><?= htmlspecialchars($appName) ?> Admin</h1>

        <div id="login-error" class="alert alert-error" style="display:none;"></div>

        <form id="login-form" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required autofocus>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Sign In</button>
        </form>
    </div>

    <script>
        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('login-error');

            try {
                const response = await fetch('<?= $baseUrl ?>/api/admin/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success) {
                    localStorage.setItem('admin_token', data.data.token);
                    window.location.href = '/admin/dashboard.php';
                } else {
                    errorDiv.textContent = data.error.message;
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Connection error. Please try again.';
                errorDiv.style.display = 'block';
            }
        });
    </script>
<?php else: ?>
    <!-- Admin Dashboard -->
    <div class="admin-container">
        <aside class="admin-sidebar">
            <div class="logo"><?= htmlspecialchars($appName) ?></div>
            <ul class="nav-menu">
                <li><a href="/admin/dashboard.php" class="active">Dashboard</a></li>
                <li><a href="/admin/orders.php">Orders</a></li>
                <li><a href="/admin/menu.php">Menu</a></li>
                <li><a href="/admin/specials.php">Specials</a></li>
                <li><a href="/admin/notifications.php">Notifications</a></li>
                <li><a href="/admin/staff.php">Staff</a></li>
                <li><a href="/admin/settings.php">Settings</a></li>
                <li><a href="/admin/logout.php">Logout</a></li>
            </ul>
        </aside>
        <main class="admin-main">
            <div class="admin-header">
                <h1>Dashboard</h1>
                <span>Welcome back!</span>
            </div>
            <div class="admin-content">
                <p>Admin panel will be built in Section 9.</p>
            </div>
        </main>
    </div>
<?php endif; ?>
</body>
</html>
