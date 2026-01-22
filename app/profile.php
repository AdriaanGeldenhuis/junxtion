<?php
/**
 * Customer Profile Page
 */
$configPath = __DIR__ . '/../private/config.php';
$config = file_exists($configPath) ? require $configPath : [];
$appName = $config['app']['name'] ?? 'Junxtion';
$baseUrl = $config['app']['base_url'] ?? 'https://junxtionapp.co.za';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Profile - <?= htmlspecialchars($appName) ?></title>
    <link rel="manifest" href="/pwa/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="page-header">
            <button class="back-btn" onclick="location.href='/app/home.php'">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 class="page-title">Profile</h1>
            <div style="width:24px;"></div>
        </header>

        <!-- Profile Content -->
        <div class="page" id="profile-page">
            <!-- Logged In State -->
            <div id="logged-in-state" style="display:none;">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar" id="profile-avatar">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="profile-info">
                        <h2 id="profile-name">User</h2>
                        <p id="profile-phone"></p>
                    </div>
                </div>

                <!-- Profile Menu -->
                <div class="profile-menu">
                    <a href="/app/orders.php" class="menu-item">
                        <div class="menu-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <polyline points="14 2 14 8 20 8"/>
                            </svg>
                        </div>
                        <span>My Orders</span>
                        <svg class="chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>

                    <a href="#" onclick="showAddressesModal()" class="menu-item">
                        <div class="menu-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <span>Saved Addresses</span>
                        <svg class="chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>

                    <a href="#" onclick="showEditProfile()" class="menu-item">
                        <div class="menu-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                        </div>
                        <span>Edit Profile</span>
                        <svg class="chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>

                    <a href="#" onclick="showNotificationSettings()" class="menu-item">
                        <div class="menu-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                            </svg>
                        </div>
                        <span>Notifications</span>
                        <svg class="chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>
                </div>

                <!-- Support Section -->
                <div class="profile-menu" style="margin-top:16px;">
                    <h3 class="menu-section-title">Support</h3>

                    <a href="tel:+27000000000" class="menu-item">
                        <div class="menu-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/>
                            </svg>
                        </div>
                        <span>Call Support</span>
                        <svg class="chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>

                    <a href="mailto:support@junxtionapp.co.za" class="menu-item">
                        <div class="menu-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                <polyline points="22,6 12,13 2,6"/>
                            </svg>
                        </div>
                        <span>Email Support</span>
                        <svg class="chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </a>
                </div>

                <!-- Logout -->
                <button class="logout-btn" onclick="logout()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Sign Out
                </button>
            </div>

            <!-- Logged Out State -->
            <div id="logged-out-state">
                <div class="login-prompt">
                    <div class="login-icon">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <h2>Welcome to <?= htmlspecialchars($appName) ?></h2>
                    <p>Sign in to track orders, save addresses, and more!</p>
                    <button class="btn btn-primary btn-lg" onclick="JunxtionApp.showAuthModal(updateProfileUI)">
                        Sign In
                    </button>
                </div>

                <!-- Features -->
                <div class="features-list">
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 16 14"/>
                            </svg>
                        </div>
                        <div>
                            <h4>Track Orders</h4>
                            <p>Real-time updates on your orders</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                                <circle cx="12" cy="10" r="3"/>
                            </svg>
                        </div>
                        <div>
                            <h4>Save Addresses</h4>
                            <p>Quick checkout with saved locations</p>
                        </div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>
                            </svg>
                        </div>
                        <div>
                            <h4>Exclusive Offers</h4>
                            <p>Get special deals and promos</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="/app/home.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <span>Home</span>
        </a>
        <a href="/app/menu.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Menu</span>
        </a>
        <a href="/app/cart.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span>Cart</span>
            <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
        </a>
        <a href="/app/orders.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Orders</span>
        </a>
        <a href="/app/profile.php" class="nav-item active">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Profile</span>
        </a>
    </nav>

    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= $baseUrl ?>',
            apiUrl: '<?= $baseUrl ?>/api'
        };
    </script>
    <script src="/assets/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            updateProfileUI();
            updateCartBadge();
        });

        function updateProfileUI() {
            if (JunxtionApp.user) {
                document.getElementById('logged-in-state').style.display = 'block';
                document.getElementById('logged-out-state').style.display = 'none';
                document.getElementById('profile-name').textContent = JunxtionApp.user.name || 'User';
                document.getElementById('profile-phone').textContent = JunxtionApp.user.phone || '';

                if (JunxtionApp.user.name) {
                    const initials = JunxtionApp.user.name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2);
                    document.getElementById('profile-avatar').innerHTML = initials;
                    document.getElementById('profile-avatar').classList.add('has-initials');
                }
            } else {
                document.getElementById('logged-in-state').style.display = 'none';
                document.getElementById('logged-out-state').style.display = 'block';
            }
        }

        function logout() {
            if (confirm('Are you sure you want to sign out?')) {
                JunxtionApp.logout();
                updateProfileUI();
                JunxtionApp.showToast('Signed out successfully');
            }
        }

        function showEditProfile() {
            const content = `
                <div class="modal-header">
                    <h3 class="modal-title">Edit Profile</h3>
                    <button class="modal-close" onclick="JunxtionApp.closeModal(this.closest('.modal-overlay'))">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="profile-form">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" class="form-input" id="edit-name" value="${JunxtionApp.user?.name || ''}" placeholder="Your name">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" class="form-input" value="${JunxtionApp.user?.phone || ''}" disabled>
                        <p class="form-hint">Contact support to change your phone number</p>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
                </form>
            `;

            JunxtionApp.showModal(content);

            document.getElementById('profile-form').addEventListener('submit', async (e) => {
                e.preventDefault();
                const name = document.getElementById('edit-name').value.trim();

                try {
                    const response = await fetch('/api/customer/profile', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': `Bearer ${JunxtionApp.token}`
                        },
                        body: JSON.stringify({ name })
                    });
                    const data = await response.json();

                    if (data.success) {
                        JunxtionApp.user.name = name;
                        localStorage.setItem('junxtion_user', JSON.stringify(JunxtionApp.user));
                        JunxtionApp.closeModal(document.querySelector('.modal-overlay'));
                        updateProfileUI();
                        JunxtionApp.showToast('Profile updated!', 'success');
                    } else {
                        JunxtionApp.showToast(data.error?.message || 'Failed to update', 'error');
                    }
                } catch (e) {
                    JunxtionApp.showToast('Connection error', 'error');
                }
            });
        }

        function showAddressesModal() {
            JunxtionApp.showToast('Coming soon!');
        }

        function showNotificationSettings() {
            const content = `
                <div class="modal-header">
                    <h3 class="modal-title">Notifications</h3>
                    <button class="modal-close" onclick="JunxtionApp.closeModal(this.closest('.modal-overlay'))">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="notification-settings">
                    <div class="setting-item">
                        <div>
                            <h4>Push Notifications</h4>
                            <p>Get notified about order updates</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" id="push-toggle" ${Notification.permission === 'granted' ? 'checked' : ''}>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                    <div class="setting-item">
                        <div>
                            <h4>Promotional Offers</h4>
                            <p>Receive special deals and discounts</p>
                        </div>
                        <label class="toggle">
                            <input type="checkbox" id="promo-toggle" checked>
                            <span class="toggle-slider"></span>
                        </label>
                    </div>
                </div>
            `;

            JunxtionApp.showModal(content);

            document.getElementById('push-toggle').addEventListener('change', async (e) => {
                if (e.target.checked) {
                    const permission = await Notification.requestPermission();
                    if (permission !== 'granted') {
                        e.target.checked = false;
                        JunxtionApp.showToast('Notifications blocked. Enable in browser settings.', 'error');
                    } else {
                        JunxtionApp.showToast('Notifications enabled!', 'success');
                    }
                }
            });
        }

        function updateCartBadge() {
            const count = JunxtionApp.getCartCount();
            document.getElementById('cart-badge').textContent = count;
            document.getElementById('cart-badge').style.display = count > 0 ? 'flex' : 'none';
        }
    </script>

    <style>
        .back-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 24px;
            background: white;
            border-radius: 16px;
            margin-bottom: 16px;
        }
        .profile-avatar {
            width: 64px;
            height: 64px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }
        .profile-avatar.has-initials {
            font-size: 24px;
            font-weight: 700;
        }
        .profile-info h2 {
            font-size: 20px;
            margin-bottom: 4px;
        }
        .profile-info p {
            color: var(--gray-500);
            font-size: 14px;
        }
        .profile-menu {
            background: white;
            border-radius: 16px;
            overflow: hidden;
        }
        .menu-section-title {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--gray-500);
            padding: 16px 16px 8px;
        }
        .menu-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            border-bottom: 1px solid var(--gray-100);
            text-decoration: none;
            color: var(--gray-700);
            transition: background 0.2s;
        }
        .menu-item:last-child {
            border-bottom: none;
        }
        .menu-item:active {
            background: var(--gray-50);
        }
        .menu-icon {
            width: 40px;
            height: 40px;
            background: var(--gray-100);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
        }
        .menu-item span {
            flex: 1;
            font-weight: 500;
        }
        .chevron {
            color: var(--gray-400);
        }
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 16px;
            margin-top: 24px;
            background: none;
            border: 2px solid var(--danger);
            border-radius: 12px;
            color: var(--danger);
            font-weight: 600;
            font-size: 16px;
            cursor: pointer;
        }
        .login-prompt {
            text-align: center;
            padding: 40px 20px;
        }
        .login-icon {
            width: 100px;
            height: 100px;
            background: var(--primary-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            color: var(--primary);
        }
        .login-prompt h2 {
            margin-bottom: 8px;
        }
        .login-prompt p {
            color: var(--gray-500);
            margin-bottom: 24px;
        }
        .features-list {
            padding: 24px;
            background: white;
            border-radius: 16px;
            margin-top: 24px;
        }
        .feature-item {
            display: flex;
            gap: 16px;
            margin-bottom: 20px;
        }
        .feature-item:last-child {
            margin-bottom: 0;
        }
        .feature-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-light);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            flex-shrink: 0;
        }
        .feature-item h4 {
            font-size: 15px;
            margin-bottom: 4px;
        }
        .feature-item p {
            font-size: 13px;
            color: var(--gray-500);
        }
        .notification-settings {
            margin-top: 16px;
        }
        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .setting-item:last-child {
            border-bottom: none;
        }
        .setting-item h4 {
            font-size: 15px;
            margin-bottom: 4px;
        }
        .setting-item p {
            font-size: 13px;
            color: var(--gray-500);
        }
        .toggle {
            position: relative;
            width: 50px;
            height: 28px;
            display: inline-block;
        }
        .toggle input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: var(--gray-300);
            border-radius: 28px;
            transition: 0.3s;
        }
        .toggle-slider:before {
            content: "";
            position: absolute;
            width: 22px;
            height: 22px;
            left: 3px;
            bottom: 3px;
            background: white;
            border-radius: 50%;
            transition: 0.3s;
        }
        .toggle input:checked + .toggle-slider {
            background: var(--primary);
        }
        .toggle input:checked + .toggle-slider:before {
            transform: translateX(22px);
        }
    </style>
</body>
</html>
