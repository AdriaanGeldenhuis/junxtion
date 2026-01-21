<?php
/**
 * Admin Dashboard
 */
require_once __DIR__ . '/includes/layout.php';
admin_header('Dashboard', 'dashboard');
?>

<div class="dashboard-grid" id="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" id="stat-today-orders">-</div>
                <div class="stat-label">Today's Orders</div>
            </div>
            <div class="stat-card-icon orange">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" id="stat-revenue">-</div>
                <div class="stat-label">Today's Revenue</div>
            </div>
            <div class="stat-card-icon green">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" id="stat-pending">-</div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card-icon yellow">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <div>
                <div class="stat-value" id="stat-ready">-</div>
                <div class="stat-label">Ready for Pickup</div>
            </div>
            <div class="stat-card-icon blue">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 11-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2 class="card-title">Recent Orders</h2>
        <a href="/admin/orders.php" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="table-container">
        <table class="data-table" id="recent-orders">
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Type</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody id="orders-tbody">
                <tr><td colspan="6" class="empty-state">Loading...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', async () => {
    // Load stats
    loadStats();
    loadRecentOrders();

    // Refresh every 30 seconds
    setInterval(() => {
        loadStats();
        loadRecentOrders();
    }, 30000);
});

async function loadStats() {
    const data = await AdminAPI.getOrderStats();
    if (data?.success) {
        const stats = data.data;
        document.getElementById('stat-today-orders').textContent = stats.today.total_orders;
        document.getElementById('stat-revenue').textContent = AdminUI.formatPrice(stats.today.revenue_cents || 0);
        document.getElementById('stat-pending').textContent = stats.pending_orders;
        document.getElementById('stat-ready').textContent = stats.ready_orders;
    }
}

async function loadRecentOrders() {
    const data = await AdminAPI.getOrders(1, { per_page: 10 });
    if (data?.success) {
        const tbody = document.getElementById('orders-tbody');
        if (data.data.items.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No orders yet</td></tr>';
            return;
        }

        tbody.innerHTML = data.data.items.map(order => `
            <tr onclick="window.location='/admin/orders.php?id=${order.id}'" style="cursor:pointer">
                <td><strong>#${order.id}</strong></td>
                <td>${order.customer_name || 'Guest'}</td>
                <td><span class="order-type">${order.order_type}</span></td>
                <td>${AdminUI.formatPrice(order.total_cents)}</td>
                <td>${AdminUI.statusBadge(order.status)}</td>
                <td>${AdminUI.timeAgo(order.created_at)}</td>
            </tr>
        `).join('');
    }
}
</script>

<?php admin_footer(); ?>
