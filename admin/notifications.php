<?php
/**
 * Admin Notifications Management
 */
require_once __DIR__ . '/includes/layout.php';
admin_header('Notifications', 'notifications');
?>

<div class="tabs">
    <button class="tab active" onclick="showTab('send')">Send Notification</button>
    <button class="tab" onclick="showTab('history')">History</button>
</div>

<div id="tab-send" class="card">
    <h3 class="card-title" style="margin-bottom:20px;">Broadcast Notification</h3>
    <form id="notification-form">
        <div class="form-group">
            <label>Title</label>
            <input type="text" class="form-control" name="title" maxlength="255" required
                   placeholder="e.g., Special Offer Today!">
        </div>
        <div class="form-group">
            <label>Message</label>
            <textarea class="form-control" name="body" rows="4" required
                      placeholder="Enter notification message..."></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Target Audience</label>
                <select class="form-control" name="audience">
                    <option value="all">All Users</option>
                    <option value="customers">Customers Only</option>
                    <option value="staff">Staff Only</option>
                </select>
            </div>
            <div class="form-group">
                <label>Schedule (optional)</label>
                <input type="datetime-local" class="form-control" name="scheduled_at">
            </div>
        </div>
        <div class="form-group">
            <label>Deep Link URL (optional)</label>
            <input type="text" class="form-control" name="deep_link"
                   placeholder="e.g., /specials or /menu">
            <div class="form-hint">Where users go when they tap the notification</div>
        </div>
        <button type="submit" class="btn btn-primary">Send Notification</button>
    </form>
</div>

<div id="tab-history" class="card" style="display:none;">
    <h3 class="card-title" style="margin-bottom:20px;">Notification History</h3>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Sent</th>
                    <th>Delivered</th>
                    <th>Clicked</th>
                    <th>Status</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="history-tbody">
                <tr><td colspan="6" class="empty-state">Loading...</td></tr>
            </tbody>
        </table>
    </div>
    <div class="pagination" id="pagination" style="display:none;">
        <button id="prev-page">Previous</button>
        <span id="page-info">Page 1</span>
        <button id="next-page">Next</button>
    </div>
</div>

<script>
let currentPage = 1;

document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('notification-form').addEventListener('submit', sendNotification);
    loadHistory();
});

function showTab(tab) {
    document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
    document.querySelector(`[onclick="showTab('${tab}')"]`).classList.add('active');

    document.getElementById('tab-send').style.display = tab === 'send' ? 'block' : 'none';
    document.getElementById('tab-history').style.display = tab === 'history' ? 'block' : 'none';

    if (tab === 'history') loadHistory();
}

async function sendNotification(e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    const data = {
        title: formData.get('title'),
        body: formData.get('body'),
        audience: formData.get('audience'),
        scheduled_at: formData.get('scheduled_at') || null,
        data: formData.get('deep_link') ? { deep_link: formData.get('deep_link') } : {}
    };

    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = 'Sending...';

    try {
        const result = await AdminAPI.sendBroadcast(data);
        if (result?.success) {
            AdminUI.toast('Notification sent successfully!');
            form.reset();
            loadHistory();
        } else {
            AdminUI.toast(result?.error?.message || 'Failed to send', 'error');
        }
    } finally {
        btn.disabled = false;
        btn.textContent = 'Send Notification';
    }
}

async function loadHistory() {
    const data = await AdminAPI.getNotifications(currentPage);
    if (data?.success) {
        renderHistory(data.data);
    }
}

function renderHistory(data) {
    const tbody = document.getElementById('history-tbody');

    if (data.items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No notifications sent yet</td></tr>';
        return;
    }

    tbody.innerHTML = data.items.map(n => `
        <tr>
            <td>
                <strong>${n.title}</strong>
                <div style="font-size:12px;color:var(--admin-text-muted);">${n.body?.substring(0, 50)}...</div>
            </td>
            <td>${n.sent_count || 0}</td>
            <td>${n.delivered_count || 0}</td>
            <td>${n.clicked_count || 0}</td>
            <td>
                <span class="status-badge ${n.send_status === 'sent' ? 'completed' : n.send_status === 'scheduled' ? 'pending' : 'cancelled'}">
                    ${n.send_status}
                </span>
            </td>
            <td>${AdminUI.formatDateTime(n.created_at)}</td>
        </tr>
    `).join('');

    // Update pagination
    const pagination = document.getElementById('pagination');
    if (data.total > data.per_page) {
        pagination.style.display = 'flex';
        document.getElementById('page-info').textContent = `Page ${data.page} of ${Math.ceil(data.total / data.per_page)}`;
    }
}
</script>

<?php admin_footer(); ?>
