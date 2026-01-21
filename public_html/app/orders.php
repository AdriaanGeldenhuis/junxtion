<?php
/**
 * Customer Orders Page
 */
$configPath = __DIR__ . '/../../private/config.php';
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
    <title>Orders - <?= htmlspecialchars($appName) ?></title>
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
            <h1 class="page-title">My Orders</h1>
            <div style="width:24px;"></div>
        </header>

        <!-- Tabs -->
        <div class="tabs-container">
            <button class="tab active" data-tab="active">Active</button>
            <button class="tab" data-tab="history">History</button>
        </div>

        <!-- Orders Content -->
        <div class="page" id="orders-page">
            <!-- Active Orders -->
            <div class="tab-content active" id="tab-active">
                <div class="orders-list" id="active-orders">
                    <div class="loading-state">Loading orders...</div>
                </div>
            </div>

            <!-- Order History -->
            <div class="tab-content" id="tab-history" style="display:none;">
                <div class="orders-list" id="history-orders">
                    <div class="loading-state">Loading history...</div>
                </div>
            </div>

            <!-- Login Required -->
            <div class="login-prompt" id="login-prompt" style="display:none;">
                <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                    <circle cx="12" cy="7" r="4"/>
                </svg>
                <h3>Sign in to view orders</h3>
                <p>Track your orders and see your order history</p>
                <button class="btn btn-primary" onclick="JunxtionApp.showAuthModal(loadOrders)">Sign In</button>
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
        <a href="/app/orders.php" class="nav-item active">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Orders</span>
        </a>
        <a href="/app/profile.php" class="nav-item">
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
        let activeOrders = [];
        let historyOrders = [];
        let pollingInterval = null;

        document.addEventListener('DOMContentLoaded', () => {
            // Tab switching
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', () => {
                    document.querySelector('.tab.active').classList.remove('active');
                    tab.classList.add('active');

                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.style.display = 'none';
                    });
                    document.getElementById('tab-' + tab.dataset.tab).style.display = 'block';
                });
            });

            // Check if logged in
            if (JunxtionApp.user) {
                loadOrders();
                // Poll for active orders
                pollingInterval = setInterval(loadActiveOrders, 10000);
            } else {
                document.getElementById('login-prompt').style.display = 'flex';
                document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
            }

            updateCartBadge();

            // Check URL params for specific order
            const params = new URLSearchParams(window.location.search);
            if (params.get('id')) {
                showOrderDetail(params.get('id'));
            }
        });

        async function loadOrders() {
            document.getElementById('login-prompt').style.display = 'none';
            document.getElementById('tab-active').style.display = 'block';

            await Promise.all([loadActiveOrders(), loadOrderHistory()]);
        }

        async function loadActiveOrders() {
            try {
                const response = await fetch('/api/orders?status=active', {
                    headers: { 'Authorization': `Bearer ${JunxtionApp.token}` }
                });
                const data = await response.json();

                if (data.success) {
                    activeOrders = data.data.items || [];
                    renderActiveOrders();
                }
            } catch (e) {
                console.error('Error loading active orders:', e);
            }
        }

        async function loadOrderHistory() {
            try {
                const response = await fetch('/api/orders?status=completed', {
                    headers: { 'Authorization': `Bearer ${JunxtionApp.token}` }
                });
                const data = await response.json();

                if (data.success) {
                    historyOrders = data.data.items || [];
                    renderHistoryOrders();
                }
            } catch (e) {
                console.error('Error loading order history:', e);
            }
        }

        function renderActiveOrders() {
            const container = document.getElementById('active-orders');

            if (activeOrders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                        <h3>No active orders</h3>
                        <p>Your current orders will appear here</p>
                        <a href="/app/menu.php" class="btn btn-primary">Order Now</a>
                    </div>
                `;
                return;
            }

            container.innerHTML = activeOrders.map(order => renderOrderCard(order, true)).join('');
        }

        function renderHistoryOrders() {
            const container = document.getElementById('history-orders');

            if (historyOrders.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <h3>No order history</h3>
                        <p>Your completed orders will appear here</p>
                    </div>
                `;
                return;
            }

            container.innerHTML = historyOrders.map(order => renderOrderCard(order, false)).join('');
        }

        function renderOrderCard(order, isActive) {
            const statusInfo = getStatusInfo(order.status);
            const items = order.items || [];
            const itemsText = items.slice(0, 2).map(i => `${i.qty}x ${i.name_snapshot}`).join(', ');
            const moreItems = items.length > 2 ? ` +${items.length - 2} more` : '';

            return `
                <div class="order-card" onclick="showOrderDetail(${order.id})">
                    <div class="order-card-header">
                        <span class="order-number">Order #${order.id}</span>
                        <span class="order-status status-${order.status.toLowerCase().replace('_', '-')}">${statusInfo.label}</span>
                    </div>
                    ${isActive ? `
                        <div class="order-progress">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${statusInfo.progress}%"></div>
                            </div>
                            <div class="progress-steps">
                                <span class="${statusInfo.step >= 1 ? 'active' : ''}">Placed</span>
                                <span class="${statusInfo.step >= 2 ? 'active' : ''}">Preparing</span>
                                <span class="${statusInfo.step >= 3 ? 'active' : ''}">Ready</span>
                            </div>
                        </div>
                    ` : ''}
                    <div class="order-card-body">
                        <div class="order-items-preview">${itemsText}${moreItems}</div>
                        <div class="order-meta">
                            <span class="order-type">${order.order_type}</span>
                            <span class="order-date">${formatDate(order.created_at)}</span>
                        </div>
                    </div>
                    <div class="order-card-footer">
                        <span class="order-total">R${(order.total_cents / 100).toFixed(2)}</span>
                        <span class="view-details">View Details</span>
                    </div>
                </div>
            `;
        }

        function getStatusInfo(status) {
            const statuses = {
                'PENDING_PAYMENT': { label: 'Pending Payment', step: 0, progress: 0 },
                'PLACED': { label: 'Order Placed', step: 1, progress: 25 },
                'ACCEPTED': { label: 'Accepted', step: 1, progress: 33 },
                'IN_PREP': { label: 'Being Prepared', step: 2, progress: 50 },
                'READY': { label: 'Ready!', step: 3, progress: 100 },
                'OUT_FOR_DELIVERY': { label: 'On the Way', step: 3, progress: 90 },
                'COMPLETED': { label: 'Completed', step: 3, progress: 100 },
                'CANCELLED': { label: 'Cancelled', step: 0, progress: 0 }
            };
            return statuses[status] || { label: status, step: 0, progress: 0 };
        }

        async function showOrderDetail(orderId) {
            try {
                const response = await fetch(`/api/orders/${orderId}`, {
                    headers: { 'Authorization': `Bearer ${JunxtionApp.token}` }
                });
                const data = await response.json();

                if (!data.success) {
                    JunxtionApp.showToast('Could not load order', 'error');
                    return;
                }

                const order = data.data;
                const statusInfo = getStatusInfo(order.status);
                const items = order.items || [];

                const content = `
                    <div class="modal-header">
                        <h3 class="modal-title">Order #${order.id}</h3>
                        <button class="modal-close" onclick="JunxtionApp.closeModal(this.closest('.modal-overlay'))">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="order-detail-status">
                        <span class="order-status status-${order.status.toLowerCase().replace('_', '-')}">${statusInfo.label}</span>
                        <span class="order-type-badge">${order.order_type}</span>
                    </div>

                    ${['PLACED', 'ACCEPTED', 'IN_PREP', 'READY'].includes(order.status) ? `
                        <div class="order-progress" style="margin-bottom:20px;">
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: ${statusInfo.progress}%"></div>
                            </div>
                        </div>
                    ` : ''}

                    <div class="order-detail-section">
                        <h4>Items</h4>
                        ${items.map(item => `
                            <div class="detail-item">
                                <span class="item-qty">${item.qty}x</span>
                                <span class="item-name">${item.name_snapshot}</span>
                                <span class="item-price">R${(item.subtotal_cents / 100).toFixed(2)}</span>
                            </div>
                            ${item.modifiers ? `<div class="item-mods">${item.modifiers}</div>` : ''}
                        `).join('')}
                    </div>

                    ${order.notes ? `
                        <div class="order-detail-section">
                            <h4>Notes</h4>
                            <p>${order.notes}</p>
                        </div>
                    ` : ''}

                    <div class="order-detail-summary">
                        <div class="summary-row">
                            <span>Subtotal</span>
                            <span>R${((order.total_cents - (order.delivery_fee_cents || 0) + (order.discount_cents || 0)) / 100).toFixed(2)}</span>
                        </div>
                        ${order.delivery_fee_cents ? `
                            <div class="summary-row">
                                <span>Delivery</span>
                                <span>R${(order.delivery_fee_cents / 100).toFixed(2)}</span>
                            </div>
                        ` : ''}
                        ${order.discount_cents ? `
                            <div class="summary-row discount">
                                <span>Discount</span>
                                <span>-R${(order.discount_cents / 100).toFixed(2)}</span>
                            </div>
                        ` : ''}
                        <div class="summary-row total">
                            <span>Total</span>
                            <span>R${(order.total_cents / 100).toFixed(2)}</span>
                        </div>
                    </div>

                    <div class="order-detail-meta">
                        <p>Ordered: ${formatDateTime(order.created_at)}</p>
                        ${order.delivery_address ? `<p>Delivery to: ${order.delivery_address}</p>` : ''}
                    </div>

                    ${order.status === 'READY' ? `
                        <div class="ready-notice">
                            Your order is ready for ${order.order_type === 'pickup' ? 'pickup' : 'delivery'}!
                        </div>
                    ` : ''}
                `;

                JunxtionApp.showModal(content);
            } catch (e) {
                JunxtionApp.showToast('Error loading order details', 'error');
            }
        }

        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-ZA', { day: 'numeric', month: 'short' });
        }

        function formatDateTime(dateString) {
            const date = new Date(dateString);
            return date.toLocaleString('en-ZA', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
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
        .tabs-container {
            display: flex;
            gap: 8px;
            padding: 0 16px 16px;
            background: white;
            border-bottom: 1px solid var(--gray-200);
        }
        .tab {
            flex: 1;
            padding: 12px;
            border: none;
            background: var(--gray-100);
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .tab.active {
            background: var(--primary);
            color: white;
        }
        .login-prompt {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }
        .login-prompt svg {
            margin-bottom: 16px;
            color: var(--gray-300);
        }
        .login-prompt h3 {
            color: var(--gray-700);
            margin-bottom: 8px;
        }
        .login-prompt .btn {
            margin-top: 20px;
        }
        .order-card {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .order-card:active {
            transform: scale(0.98);
        }
        .order-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }
        .order-number {
            font-weight: 700;
        }
        .order-status {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            text-transform: uppercase;
        }
        .status-placed, .status-accepted { background: #dbeafe; color: #2563eb; }
        .status-in-prep { background: #fce7f3; color: #db2777; }
        .status-ready { background: #d1fae5; color: #059669; }
        .status-out-for-delivery { background: #cffafe; color: #0891b2; }
        .status-completed { background: #dcfce7; color: #16a34a; }
        .status-cancelled { background: #fee2e2; color: #dc2626; }
        .status-pending-payment { background: #fef3c7; color: #d97706; }
        .order-progress {
            margin-bottom: 12px;
        }
        .progress-bar {
            height: 4px;
            background: var(--gray-200);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 8px;
        }
        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 2px;
            transition: width 0.5s;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            font-size: 11px;
            color: var(--gray-400);
        }
        .progress-steps span.active {
            color: var(--primary);
            font-weight: 600;
        }
        .order-items-preview {
            font-size: 14px;
            color: var(--gray-600);
            margin-bottom: 8px;
        }
        .order-meta {
            display: flex;
            gap: 8px;
            font-size: 12px;
        }
        .order-type {
            text-transform: capitalize;
            color: var(--gray-500);
        }
        .order-date {
            color: var(--gray-400);
        }
        .order-card-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 12px;
            padding-top: 12px;
            border-top: 1px solid var(--gray-100);
        }
        .order-total {
            font-weight: 700;
            font-size: 16px;
        }
        .view-details {
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
        }
        .order-detail-status {
            display: flex;
            gap: 8px;
            margin-bottom: 16px;
        }
        .order-type-badge {
            font-size: 12px;
            padding: 4px 10px;
            background: var(--gray-100);
            border-radius: 20px;
            text-transform: capitalize;
        }
        .order-detail-section {
            margin-bottom: 20px;
        }
        .order-detail-section h4 {
            font-size: 12px;
            text-transform: uppercase;
            color: var(--gray-500);
            margin-bottom: 12px;
        }
        .detail-item {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid var(--gray-100);
        }
        .detail-item:last-child {
            border-bottom: none;
        }
        .item-qty {
            font-weight: 600;
            color: var(--primary);
            margin-right: 12px;
            min-width: 30px;
        }
        .item-name {
            flex: 1;
        }
        .item-mods {
            font-size: 12px;
            color: var(--gray-500);
            margin-left: 42px;
            margin-bottom: 8px;
        }
        .order-detail-summary {
            background: var(--gray-50);
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 16px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 0;
            color: var(--gray-600);
        }
        .summary-row.discount {
            color: var(--success);
        }
        .summary-row.total {
            font-weight: 700;
            font-size: 16px;
            color: var(--gray-900);
            border-top: 1px solid var(--gray-200);
            margin-top: 8px;
            padding-top: 12px;
        }
        .order-detail-meta {
            font-size: 13px;
            color: var(--gray-500);
        }
        .ready-notice {
            background: var(--success-bg);
            color: var(--success);
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            font-weight: 600;
            margin-top: 16px;
        }
    </style>
</body>
</html>
