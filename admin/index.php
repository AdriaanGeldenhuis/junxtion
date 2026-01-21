<?php
/**
 * Junxtion Admin Panel - Login Page
 */
session_start();

$configPath = __DIR__ . '/../private/config.php';
$config = file_exists($configPath) ? require $configPath : [];

$baseUrl = $config['app']['base_url'] ?? 'https://junxtionapp.co.za';
$appName = $config['app']['name'] ?? 'Junxtion';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Login - <?= htmlspecialchars($appName) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="login-page">
    <div class="login-form">
        <div class="logo"><?= htmlspecialchars($appName) ?></div>
        <p class="subtitle">Admin Panel</p>

        <div id="login-error" class="alert alert-error" style="display:none;"></div>

        <form id="login-form">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" required autofocus
                       placeholder="admin@example.com">
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" class="form-control" required
                       placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary btn-lg" style="width:100%;">Sign In</button>
        </form>

        <p style="text-align:center;margin-top:24px;color:var(--admin-text-muted);font-size:14px;">
            <a href="/" style="color:var(--admin-primary);text-decoration:none;">Back to App</a>
        </p>
    </div>

    <script src="/assets/js/admin.js"></script>
    <script>
        // Check if already logged in
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('admin_token');
            if (token) {
                // Verify token is still valid
                fetch('/api/staff/profile', {
                    headers: { 'Authorization': `Bearer ${token}` }
                }).then(res => {
                    if (res.ok) {
                        window.location.href = '/admin/dashboard.php';
                    } else {
                        localStorage.removeItem('admin_token');
                    }
                }).catch(() => {});
            }
        });

        document.getElementById('login-form').addEventListener('submit', async function(e) {
            e.preventDefault();

            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const errorDiv = document.getElementById('login-error');
            const submitBtn = this.querySelector('button[type="submit"]');

            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="loading-spinner"></span> Signing in...';
            errorDiv.style.display = 'none';

            try {
                const response = await fetch('/api/staff/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, password })
                });

                const data = await response.json();

                if (data.success) {
                    localStorage.setItem('admin_token', data.data.token);
                    if (data.data.refresh_token) {
                        localStorage.setItem('admin_refresh_token', data.data.refresh_token);
                    }
                    window.location.href = '/admin/dashboard.php';
                } else {
                    errorDiv.textContent = data.error?.message || 'Invalid credentials';
                    errorDiv.style.display = 'block';
                }
            } catch (err) {
                errorDiv.textContent = 'Connection error. Please try again.';
                errorDiv.style.display = 'block';
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Sign In';
            }
        });
    </script>
</body>
</html>
