<?php
/**
 * Admin Settings
 */
require_once __DIR__ . '/includes/layout.php';
admin_header('Settings', 'settings');
?>

<div class="card" style="margin-bottom:24px;">
    <div class="card-header">
        <h3 class="card-title">Order Status</h3>
        <button id="toggle-ordering" class="btn btn-success">Enable Ordering</button>
    </div>
    <p style="color:var(--admin-text-muted);">
        When paused, customers cannot place new orders. Use this during high-volume periods or maintenance.
    </p>
</div>

<div class="card" style="margin-bottom:24px;">
    <h3 class="card-title" style="margin-bottom:20px;">Business Settings</h3>
    <form id="settings-form">
        <div class="form-row">
            <div class="form-group">
                <label>Business Name</label>
                <input type="text" class="form-control" name="business_name">
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="tel" class="form-control" name="business_phone">
            </div>
        </div>
        <div class="form-group">
            <label>Business Address</label>
            <textarea class="form-control" name="business_address" rows="2"></textarea>
        </div>

        <h4 style="margin:24px 0 16px;padding-top:16px;border-top:1px solid var(--admin-border);">Operating Hours</h4>
        <div class="form-row">
            <div class="form-group">
                <label>Opens At</label>
                <input type="time" class="form-control" name="opens_at">
            </div>
            <div class="form-group">
                <label>Closes At</label>
                <input type="time" class="form-control" name="closes_at">
            </div>
        </div>

        <h4 style="margin:24px 0 16px;padding-top:16px;border-top:1px solid var(--admin-border);">Order Settings</h4>
        <div class="form-row">
            <div class="form-group">
                <label>Minimum Order Amount (R)</label>
                <input type="number" class="form-control" name="min_order_amount" step="0.01" min="0">
            </div>
            <div class="form-group">
                <label>Delivery Fee (R)</label>
                <input type="number" class="form-control" name="delivery_fee" step="0.01" min="0">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label>Prep Time (minutes)</label>
                <input type="number" class="form-control" name="default_prep_time" min="5">
                <div class="form-hint">Default preparation time shown to customers</div>
            </div>
            <div class="form-group">
                <label>Delivery Radius (km)</label>
                <input type="number" class="form-control" name="delivery_radius" min="1">
            </div>
        </div>

        <h4 style="margin:24px 0 16px;padding-top:16px;border-top:1px solid var(--admin-border);">Order Types</h4>
        <div class="form-check" style="margin-bottom:12px;">
            <input type="checkbox" name="pickup_enabled">
            <label>Enable Pickup Orders</label>
        </div>
        <div class="form-check" style="margin-bottom:12px;">
            <input type="checkbox" name="delivery_enabled">
            <label>Enable Delivery Orders</label>
        </div>
        <div class="form-check" style="margin-bottom:24px;">
            <input type="checkbox" name="dine_in_enabled">
            <label>Enable Dine-in Orders</label>
        </div>

        <button type="submit" class="btn btn-primary">Save Settings</button>
    </form>
</div>

<div class="card">
    <h3 class="card-title" style="margin-bottom:20px;">Notification Settings</h3>
    <form id="notification-settings-form">
        <div class="form-group">
            <label>New Order Alert Email</label>
            <input type="email" class="form-control" name="order_alert_email"
                   placeholder="email@example.com">
            <div class="form-hint">Receive email alerts for new orders</div>
        </div>
        <div class="form-check" style="margin-bottom:12px;">
            <input type="checkbox" name="email_alerts_enabled">
            <label>Enable Email Alerts</label>
        </div>
        <div class="form-check" style="margin-bottom:24px;">
            <input type="checkbox" name="sms_alerts_enabled">
            <label>Enable SMS Alerts</label>
        </div>
        <button type="submit" class="btn btn-primary">Save Notification Settings</button>
    </form>
</div>

<script>
let settings = {};

document.addEventListener('DOMContentLoaded', () => {
    loadSettings();

    document.getElementById('settings-form').addEventListener('submit', saveSettings);
    document.getElementById('notification-settings-form').addEventListener('submit', saveNotificationSettings);
    document.getElementById('toggle-ordering').addEventListener('click', toggleOrdering);
});

async function loadSettings() {
    const data = await AdminAPI.getSettings();
    if (data?.success) {
        settings = data.data;
        populateForm();
    }
}

function populateForm() {
    const form = document.getElementById('settings-form');
    const notifForm = document.getElementById('notification-settings-form');

    // Business settings
    form.business_name.value = settings.business_name || '';
    form.business_phone.value = settings.business_phone || '';
    form.business_address.value = settings.business_address || '';
    form.opens_at.value = settings.opens_at || '08:00';
    form.closes_at.value = settings.closes_at || '22:00';
    form.min_order_amount.value = settings.min_order_cents ? (settings.min_order_cents / 100) : '';
    form.delivery_fee.value = settings.delivery_fee_cents ? (settings.delivery_fee_cents / 100) : '';
    form.default_prep_time.value = settings.default_prep_time || 20;
    form.delivery_radius.value = settings.delivery_radius || 10;
    form.pickup_enabled.checked = settings.pickup_enabled !== false;
    form.delivery_enabled.checked = settings.delivery_enabled !== false;
    form.dine_in_enabled.checked = settings.dine_in_enabled === true;

    // Notification settings
    notifForm.order_alert_email.value = settings.order_alert_email || '';
    notifForm.email_alerts_enabled.checked = settings.email_alerts_enabled === true;
    notifForm.sms_alerts_enabled.checked = settings.sms_alerts_enabled === true;

    // Ordering status
    updateOrderingButton();
}

function updateOrderingButton() {
    const btn = document.getElementById('toggle-ordering');
    const isPaused = settings.ordering_paused === true;

    if (isPaused) {
        btn.textContent = 'Enable Ordering';
        btn.className = 'btn btn-success';
    } else {
        btn.textContent = 'Pause Ordering';
        btn.className = 'btn btn-danger';
    }
}

async function toggleOrdering() {
    const data = await AdminAPI.toggleOrdering();
    if (data?.success) {
        settings.ordering_paused = data.data.ordering_paused;
        updateOrderingButton();
        AdminUI.toast(data.data.message);
    }
}

async function saveSettings(e) {
    e.preventDefault();
    const form = e.target;

    const data = {
        business_name: form.business_name.value,
        business_phone: form.business_phone.value,
        business_address: form.business_address.value,
        opens_at: form.opens_at.value,
        closes_at: form.closes_at.value,
        min_order_cents: Math.round(parseFloat(form.min_order_amount.value || 0) * 100),
        delivery_fee_cents: Math.round(parseFloat(form.delivery_fee.value || 0) * 100),
        default_prep_time: parseInt(form.default_prep_time.value) || 20,
        delivery_radius: parseInt(form.delivery_radius.value) || 10,
        pickup_enabled: form.pickup_enabled.checked,
        delivery_enabled: form.delivery_enabled.checked,
        dine_in_enabled: form.dine_in_enabled.checked
    };

    const result = await AdminAPI.updateSettings(data);
    if (result?.success) {
        AdminUI.toast('Settings saved');
        loadSettings();
    } else {
        AdminUI.toast('Failed to save settings', 'error');
    }
}

async function saveNotificationSettings(e) {
    e.preventDefault();
    const form = e.target;

    const data = {
        order_alert_email: form.order_alert_email.value,
        email_alerts_enabled: form.email_alerts_enabled.checked,
        sms_alerts_enabled: form.sms_alerts_enabled.checked
    };

    const result = await AdminAPI.updateSettings(data);
    if (result?.success) {
        AdminUI.toast('Notification settings saved');
    } else {
        AdminUI.toast('Failed to save settings', 'error');
    }
}
</script>

<?php admin_footer(); ?>
