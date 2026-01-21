<?php
/**
 * Kitchen Display Mode
 * Large tiles optimized for kitchen staff
 */

$configPath = __DIR__ . '/../../private/config.php';
$config = file_exists($configPath) ? require $configPath : [];
$appName = $config['app']['name'] ?? 'Junxtion';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Kitchen - <?= htmlspecialchars($appName) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/admin.css">

    <style>
        body {
            background: #0f172a;
            min-height: 100vh;
        }

        .kitchen-wrapper {
            padding: 20px;
            min-height: 100vh;
        }

        .kitchen-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            color: #fff;
        }

        .kitchen-header h1 {
            font-size: 32px;
            font-weight: 800;
        }

        .kitchen-stats {
            display: flex;
            gap: 24px;
        }

        .kitchen-stat {
            text-align: center;
        }

        .kitchen-stat-value {
            font-size: 36px;
            font-weight: 800;
        }

        .kitchen-stat-label {
            font-size: 14px;
            opacity: 0.7;
        }

        .stat-new { color: #ef4444; }
        .stat-prep { color: #8b5cf6; }
        .stat-ready { color: #22c55e; }

        .kitchen-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 20px;
        }

        .kitchen-tile {
            background: #1e293b;
            border-radius: 20px;
            padding: 24px;
            color: #fff;
            display: flex;
            flex-direction: column;
            min-height: 320px;
            transition: transform 0.2s;
        }

        .kitchen-tile:hover {
            transform: translateY(-4px);
        }

        .kitchen-tile.tile-new {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            animation: pulse-tile 2s infinite;
        }

        .kitchen-tile.tile-accepted {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
        }

        .kitchen-tile.tile-prep {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
        }

        @keyframes pulse-tile {
            0%, 100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.5); }
            50% { box-shadow: 0 0 30px 10px rgba(220, 38, 38, 0.3); }
        }

        .tile-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }

        .tile-order-num {
            font-size: 48px;
            font-weight: 900;
            line-height: 1;
        }

        .tile-meta {
            text-align: right;
        }

        .tile-type {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            margin-bottom: 8px;
            display: inline-block;
        }

        .tile-time {
            font-size: 18px;
            font-weight: 600;
        }

        .tile-timer {
            font-size: 14px;
            opacity: 0.8;
        }

        .tile-items {
            flex: 1;
            margin: 16px 0;
            overflow-y: auto;
        }

        .tile-item {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .tile-item:last-child {
            border-bottom: none;
        }

        .tile-item-qty {
            background: rgba(255,255,255,0.2);
            min-width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 20px;
        }

        .tile-item-details {
            flex: 1;
        }

        .tile-item-name {
            font-size: 20px;
            font-weight: 700;
        }

        .tile-item-mods {
            font-size: 14px;
            opacity: 0.8;
            margin-top: 4px;
        }

        .tile-notes {
            background: rgba(0,0,0,0.2);
            padding: 16px;
            border-radius: 12px;
            margin-top: 12px;
            font-size: 16px;
        }

        .tile-btn {
            width: 100%;
            padding: 20px;
            border: none;
            border-radius: 16px;
            font-size: 20px;
            font-weight: 800;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.2s;
            margin-top: auto;
        }

        .tile-btn:hover {
            transform: scale(1.02);
        }

        .tile-btn:active {
            transform: scale(0.98);
        }

        .tile-btn-accept {
            background: #22c55e;
            color: #fff;
        }

        .tile-btn-prep {
            background: #f59e0b;
            color: #fff;
        }

        .tile-btn-ready {
            background: #3b82f6;
            color: #fff;
        }

        .kitchen-empty {
            text-align: center;
            padding: 100px 20px;
            color: #fff;
        }

        .kitchen-empty h2 {
            font-size: 48px;
            margin-bottom: 16px;
        }

        .kitchen-empty p {
            font-size: 24px;
            opacity: 0.7;
        }

        .exit-btn {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .exit-btn:hover {
            background: rgba(255,255,255,0.2);
        }

        @media (max-width: 768px) {
            .kitchen-grid {
                grid-template-columns: 1fr;
            }

            .tile-order-num {
                font-size: 36px;
            }

            .kitchen-header {
                flex-direction: column;
                gap: 16px;
                text-align: center;
            }

            .kitchen-stats {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="kitchen-wrapper">
        <header class="kitchen-header">
            <h1>Kitchen Display</h1>
            <div class="kitchen-stats">
                <div class="kitchen-stat">
                    <div class="kitchen-stat-value stat-new" id="stat-new">0</div>
                    <div class="kitchen-stat-label">New</div>
                </div>
                <div class="kitchen-stat">
                    <div class="kitchen-stat-value stat-prep" id="stat-prep">0</div>
                    <div class="kitchen-stat-label">In Prep</div>
                </div>
                <div class="kitchen-stat">
                    <div class="kitchen-stat-value stat-ready" id="stat-ready">0</div>
                    <div class="kitchen-stat-label">Ready</div>
                </div>
            </div>
            <button class="exit-btn" onclick="window.location='/admin/dashboard.php'">
                Exit Kitchen Mode
            </button>
        </header>

        <div class="kitchen-grid" id="kitchen-grid">
            <div class="kitchen-empty">
                <h2>Loading...</h2>
            </div>
        </div>
    </div>

    <script src="/assets/js/admin.js"></script>
    <script>
        let orders = [];
        let polling = true;
        let lastOrderCount = 0;

        document.addEventListener('DOMContentLoaded', () => {
            AdminAPI.init();
            fetchOrders();
            poll();
        });

        function poll() {
            if (!polling) return;
            setTimeout(async () => {
                await fetchOrders();
                poll();
            }, 3000);
        }

        async function fetchOrders() {
            try {
                const data = await AdminAPI.getLiveOrders();
                if (data?.success) {
                    // Filter to kitchen-relevant statuses
                    orders = data.data.orders.filter(o =>
                        ['PLACED', 'ACCEPTED', 'IN_PREP'].includes(o.status)
                    );

                    // Play sound for new orders
                    if (orders.length > lastOrderCount) {
                        playNewOrderSound();
                    }
                    lastOrderCount = orders.length;

                    updateStats();
                    render();
                }
            } catch (e) {
                console.error('Fetch error:', e);
            }
        }

        function updateStats() {
            const newCount = orders.filter(o => o.status === 'PLACED').length;
            const prepCount = orders.filter(o => ['ACCEPTED', 'IN_PREP'].includes(o.status)).length;
            const readyData = orders.filter(o => o.status === 'READY');

            document.getElementById('stat-new').textContent = newCount;
            document.getElementById('stat-prep').textContent = prepCount;

            // Ready count from separate call if needed
            AdminAPI.getLiveOrders(null, 'READY').then(data => {
                if (data?.success) {
                    document.getElementById('stat-ready').textContent = data.data.orders.length;
                }
            });
        }

        function render() {
            const grid = document.getElementById('kitchen-grid');

            if (orders.length === 0) {
                grid.innerHTML = `
                    <div class="kitchen-empty">
                        <h2>All Clear!</h2>
                        <p>No active orders right now</p>
                    </div>
                `;
                return;
            }

            // Sort: PLACED first, then by created_at
            orders.sort((a, b) => {
                if (a.status === 'PLACED' && b.status !== 'PLACED') return -1;
                if (b.status === 'PLACED' && a.status !== 'PLACED') return 1;
                return new Date(a.created_at) - new Date(b.created_at);
            });

            grid.innerHTML = orders.map(order => renderTile(order)).join('');

            // Bind buttons
            grid.querySelectorAll('[data-action]').forEach(btn => {
                btn.onclick = () => handleAction(btn.dataset.action, btn.dataset.orderId);
            });
        }

        function renderTile(order) {
            const tileClass = {
                'PLACED': 'tile-new',
                'ACCEPTED': 'tile-accepted',
                'IN_PREP': 'tile-prep'
            }[order.status] || '';

            const items = order.items || [];
            const createdAt = new Date(order.created_at);
            const waitTime = Math.floor((Date.now() - createdAt) / 60000);

            return `
                <div class="kitchen-tile ${tileClass}">
                    <div class="tile-header">
                        <div class="tile-order-num">#${order.id}</div>
                        <div class="tile-meta">
                            <div class="tile-type">${order.order_type.toUpperCase()}</div>
                            <div class="tile-time">${AdminUI.formatTime(order.created_at)}</div>
                            <div class="tile-timer">${waitTime}m waiting</div>
                        </div>
                    </div>

                    <div class="tile-items">
                        ${items.map(item => `
                            <div class="tile-item">
                                <div class="tile-item-qty">${item.qty}</div>
                                <div class="tile-item-details">
                                    <div class="tile-item-name">${item.name_snapshot}</div>
                                    ${item.modifiers ? `<div class="tile-item-mods">${item.modifiers}</div>` : ''}
                                </div>
                            </div>
                        `).join('')}
                    </div>

                    ${order.notes ? `<div class="tile-notes">${order.notes}</div>` : ''}

                    ${getTileButton(order)}
                </div>
            `;
        }

        function getTileButton(order) {
            const buttons = {
                'PLACED': `<button class="tile-btn tile-btn-accept" data-action="accept" data-order-id="${order.id}">ACCEPT ORDER</button>`,
                'ACCEPTED': `<button class="tile-btn tile-btn-prep" data-action="start_prep" data-order-id="${order.id}">START PREP</button>`,
                'IN_PREP': `<button class="tile-btn tile-btn-ready" data-action="ready" data-order-id="${order.id}">MARK READY</button>`
            };
            return buttons[order.status] || '';
        }

        async function handleAction(action, orderId) {
            const statusMap = {
                'accept': 'ACCEPTED',
                'start_prep': 'IN_PREP',
                'ready': 'READY'
            };

            const newStatus = statusMap[action];
            if (!newStatus) return;

            // Optimistic update
            const order = orders.find(o => o.id == orderId);
            if (order) {
                order.status = newStatus;
                if (newStatus === 'READY') {
                    orders = orders.filter(o => o.id != orderId);
                }
                render();
            }

            // Actual API call
            await AdminAPI.updateOrderStatus(orderId, newStatus);

            // Play sound for ready orders
            if (newStatus === 'READY') {
                playReadySound();
            }
        }

        function playNewOrderSound() {
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleA0fLZvu2qaIRR0+cIy5xJdpQTU4ZIe42q6LOR8ZP4bI2aZ5GSo5T2mQ0tSnewxIY3JuVHh6dYatuMWmZBslKkmTnqi3pYpaTEFQaYifqaCZlZSUl5iWlJOTk5OTk5OTk5OT');
                audio.play();
            } catch (e) {}
        }

        function playReadySound() {
            try {
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2teleA0fLZvu2qaIRR0+cIy5xJdpQTU4ZIe42q6LOR8ZP4bI2aZ5GSo5T2mQ0tSnewxIY3JuVHh6dYatuMWmZBslKkmTnqi3pYpaTEFQaYifqaCZlZSUl5iWlJOTk5OTk5OTk5OT');
                audio.play();
            } catch (e) {}
        }
    </script>
</body>
</html>
