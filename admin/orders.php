<?php
/**
 * Admin Orders Management
 */
require_once __DIR__ . '/includes/layout.php';
admin_header('Orders', 'orders');
?>

<div class="tabs" id="order-tabs">
    <button class="tab active" data-status="">All</button>
    <button class="tab" data-status="PLACED">New</button>
    <button class="tab" data-status="ACCEPTED">Accepted</button>
    <button class="tab" data-status="IN_PREP">In Prep</button>
    <button class="tab" data-status="READY">Ready</button>
    <button class="tab" data-status="COMPLETED">Completed</button>
</div>

<div class="live-orders" id="orders-grid">
    <div class="empty-state">Loading orders...</div>
</div>

<div class="pagination" id="pagination" style="display: none;">
    <button id="prev-page" disabled>Previous</button>
    <span id="page-info">Page 1</span>
    <button id="next-page">Next</button>
</div>

<!-- Order Detail Modal Template -->
<template id="order-detail-template">
    <div class="order-detail">
        <div class="order-detail-section">
            <h4>Customer</h4>
            <p id="detail-customer"></p>
            <p id="detail-phone"></p>
        </div>
        <div class="order-detail-section">
            <h4>Delivery Address</h4>
            <p id="detail-address"></p>
        </div>
        <div class="order-detail-section">
            <h4>Items</h4>
            <div id="detail-items"></div>
        </div>
        <div class="order-detail-section">
            <h4>Notes</h4>
            <p id="detail-notes"></p>
        </div>
        <div class="order-detail-total">
            <strong>Total: <span id="detail-total"></span></strong>
        </div>
    </div>
</template>

<style>
.order-detail-section {
    margin-bottom: 20px;
}
.order-detail-section h4 {
    font-size: 13px;
    text-transform: uppercase;
    color: var(--admin-text-muted);
    margin-bottom: 8px;
}
.order-detail-total {
    border-top: 2px solid var(--admin-border);
    padding-top: 16px;
    font-size: 18px;
}
</style>

<script>
let currentPage = 1;
let currentStatus = '';
let liveManager = null;

document.addEventListener('DOMContentLoaded', () => {
    // Tab switching
    document.querySelectorAll('.tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelector('.tab.active').classList.remove('active');
            tab.classList.add('active');
            currentStatus = tab.dataset.status;
            currentPage = 1;
            loadOrders();
        });
    });

    // Pagination
    document.getElementById('prev-page').addEventListener('click', () => {
        if (currentPage > 1) {
            currentPage--;
            loadOrders();
        }
    });

    document.getElementById('next-page').addEventListener('click', () => {
        currentPage++;
        loadOrders();
    });

    // Initial load with live updates for active orders
    startLiveUpdates();
});

function startLiveUpdates() {
    liveManager = new LiveOrdersManager({
        container: document.getElementById('orders-grid'),
        pollInterval: 5000,
        statusFilter: currentStatus || null,
        onNewOrder: (newOrders) => {
            AdminUI.playSound('new_order');
            AdminUI.toast(`${newOrders.length} new order(s)!`);
        }
    });
    liveManager.start();
}

async function loadOrders() {
    if (liveManager) {
        liveManager.stop();
    }

    const grid = document.getElementById('orders-grid');
    grid.innerHTML = '<div class="empty-state">Loading...</div>';

    const filters = {};
    if (currentStatus) filters.status = currentStatus;

    const data = await AdminAPI.getOrders(currentPage, filters);

    if (data?.success) {
        const { items, total, page, per_page, total_pages } = data.data;

        // Show pagination for completed orders (not live)
        const pagination = document.getElementById('pagination');
        if (currentStatus === 'COMPLETED' || currentStatus === 'CANCELLED') {
            pagination.style.display = 'flex';
            document.getElementById('page-info').textContent = `Page ${page} of ${total_pages}`;
            document.getElementById('prev-page').disabled = page <= 1;
            document.getElementById('next-page').disabled = page >= total_pages;

            renderOrderCards(items);
        } else {
            pagination.style.display = 'none';
            // Use live updates for active orders
            liveManager = new LiveOrdersManager({
                container: grid,
                pollInterval: 5000,
                statusFilter: currentStatus || null,
                onNewOrder: (newOrders) => {
                    AdminUI.playSound('new_order');
                }
            });
            liveManager.start();
        }
    }
}

function renderOrderCards(orders) {
    const grid = document.getElementById('orders-grid');

    if (orders.length === 0) {
        grid.innerHTML = '<div class="empty-state"><h3>No orders</h3><p>No orders match the current filter</p></div>';
        return;
    }

    grid.innerHTML = orders.map(order => `
        <div class="order-card ${order.status === 'PLACED' ? 'new' : ''}" data-order-id="${order.id}">
            <div class="order-card-header">
                <span class="order-number">#${order.id}</span>
                <span class="order-time">${AdminUI.timeAgo(order.created_at)}</span>
            </div>
            ${AdminUI.orderTypeBadge(order.order_type)}
            ${AdminUI.statusBadge(order.status)}
            <div class="order-customer">
                ${order.customer_name || 'Guest'}
            </div>
            <div class="order-total">
                <strong>Total: ${AdminUI.formatPrice(order.total_cents)}</strong>
            </div>
            <div class="order-actions">
                <button class="btn btn-secondary btn-sm" onclick="viewOrder(${order.id})">View Details</button>
            </div>
        </div>
    `).join('');
}

async function viewOrder(orderId) {
    const data = await AdminAPI.getOrder(orderId);
    if (!data?.success) {
        AdminUI.toast('Failed to load order', 'error');
        return;
    }

    const order = data.data;
    const items = order.items || [];

    const content = `
        <div class="order-detail">
            <div class="order-detail-section">
                <h4>Order Info</h4>
                <p><strong>Order #${order.id}</strong> - ${AdminUI.statusBadge(order.status)}</p>
                <p>${AdminUI.orderTypeBadge(order.order_type)} - ${AdminUI.formatDateTime(order.created_at)}</p>
            </div>
            <div class="order-detail-section">
                <h4>Customer</h4>
                <p>${order.customer_name || 'Guest'}</p>
                <p>${order.customer_phone || 'No phone'}</p>
                ${order.order_type === 'delivery' ? `<p><strong>Address:</strong> ${order.delivery_address || 'Not provided'}</p>` : ''}
            </div>
            <div class="order-detail-section">
                <h4>Items</h4>
                ${items.map(item => `
                    <div class="order-item">
                        <span><span class="order-item-qty">${item.qty}x</span> ${item.name_snapshot}</span>
                        <span>${AdminUI.formatPrice(item.subtotal_cents)}</span>
                    </div>
                    ${item.modifiers ? `<div style="font-size:12px;color:var(--admin-text-muted);margin-left:32px;">${item.modifiers}</div>` : ''}
                `).join('')}
            </div>
            ${order.notes ? `
            <div class="order-detail-section">
                <h4>Notes</h4>
                <p>${order.notes}</p>
            </div>
            ` : ''}
            <div class="order-detail-section">
                <h4>Payment</h4>
                <p>Status: <span class="status-badge ${order.payment_status}">${order.payment_status}</span></p>
                ${order.payment_method ? `<p>Method: ${order.payment_method}</p>` : ''}
            </div>
            <div class="order-detail-total">
                ${order.discount_cents ? `<p>Discount: -${AdminUI.formatPrice(order.discount_cents)}</p>` : ''}
                ${order.delivery_fee_cents ? `<p>Delivery: ${AdminUI.formatPrice(order.delivery_fee_cents)}</p>` : ''}
                <strong>Total: ${AdminUI.formatPrice(order.total_cents)}</strong>
            </div>
        </div>
    `;

    const actions = getOrderActions(order);

    AdminUI.showModal(`Order #${order.id}`, content, actions);
}

function getOrderActions(order) {
    const actions = [
        { label: 'Close', class: 'btn-secondary' }
    ];

    const statusActions = {
        'PLACED': { label: 'Accept Order', status: 'ACCEPTED', class: 'btn-success' },
        'ACCEPTED': { label: 'Start Prep', status: 'IN_PREP', class: 'btn-primary' },
        'IN_PREP': { label: 'Mark Ready', status: 'READY', class: 'btn-success' },
        'READY': order.order_type === 'delivery'
            ? { label: 'Out for Delivery', status: 'OUT_FOR_DELIVERY', class: 'btn-primary' }
            : { label: 'Complete', status: 'COMPLETED', class: 'btn-success' },
        'OUT_FOR_DELIVERY': { label: 'Complete', status: 'COMPLETED', class: 'btn-success' }
    };

    const nextAction = statusActions[order.status];
    if (nextAction) {
        actions.unshift({
            label: nextAction.label,
            class: nextAction.class,
            close: true,
            onClick: async () => {
                await AdminAPI.updateOrderStatus(order.id, nextAction.status);
                AdminUI.toast(`Order updated to ${nextAction.status}`);
                loadOrders();
            }
        });
    }

    if (!['COMPLETED', 'CANCELLED'].includes(order.status)) {
        actions.push({
            label: 'Cancel',
            class: 'btn-danger',
            close: false,
            onClick: async (modal) => {
                if (await AdminUI.confirm('Are you sure you want to cancel this order?')) {
                    await AdminAPI.updateOrderStatus(order.id, 'CANCELLED');
                    AdminUI.toast('Order cancelled');
                    modal.remove();
                    loadOrders();
                }
            }
        });
    }

    return actions;
}
</script>

<?php admin_footer(); ?>
