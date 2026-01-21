<?php
/**
 * Admin Promo Codes Management
 */
require_once __DIR__ . '/includes/layout.php';
admin_header('Promo Codes', 'promo-codes');
?>

<div style="margin-bottom:20px;">
    <button class="btn btn-primary" onclick="showPromoModal()">+ Add Promo Code</button>
</div>

<div class="table-container">
    <table class="data-table" id="promo-table">
        <thead>
            <tr>
                <th>Code</th>
                <th>Discount</th>
                <th>Usage</th>
                <th>Valid Period</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="promo-tbody">
            <tr><td colspan="6" class="empty-state">Loading...</td></tr>
        </tbody>
    </table>
</div>

<script>
let promoCodes = [];

document.addEventListener('DOMContentLoaded', () => {
    loadPromoCodes();
});

async function loadPromoCodes() {
    const data = await AdminAPI.getPromoCodes();
    if (data?.success) {
        promoCodes = data.data;
        renderPromoCodes();
    }
}

function renderPromoCodes() {
    const tbody = document.getElementById('promo-tbody');

    if (promoCodes.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No promo codes</td></tr>';
        return;
    }

    tbody.innerHTML = promoCodes.map(promo => {
        const discount = promo.discount_type === 'percentage'
            ? `${promo.discount_value}%`
            : AdminUI.formatPrice(promo.discount_value * 100);

        const usageText = promo.usage_limit
            ? `${promo.usage_count || 0} / ${promo.usage_limit}`
            : `${promo.usage_count || 0} uses`;

        const validPeriod = [];
        if (promo.start_at) validPeriod.push(`From: ${AdminUI.formatDate(promo.start_at)}`);
        if (promo.end_at) validPeriod.push(`To: ${AdminUI.formatDate(promo.end_at)}`);

        return `
            <tr>
                <td>
                    <strong>${promo.code}</strong>
                    ${promo.description ? `<div style="font-size:12px;color:var(--admin-text-muted);">${promo.description}</div>` : ''}
                </td>
                <td>
                    ${discount} off
                    ${promo.min_order_cents ? `<div style="font-size:11px;color:var(--admin-text-muted);">Min: ${AdminUI.formatPrice(promo.min_order_cents)}</div>` : ''}
                </td>
                <td>${usageText}</td>
                <td style="font-size:13px;">
                    ${validPeriod.length ? validPeriod.join('<br>') : 'No limit'}
                </td>
                <td>
                    <span class="status-badge ${promo.active ? 'completed' : 'cancelled'}">
                        ${promo.active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <div class="btn-group">
                        <button class="btn btn-sm btn-secondary" onclick="editPromo(${promo.id})">Edit</button>
                        <button class="btn btn-sm btn-danger" onclick="deletePromo(${promo.id})">Delete</button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function showPromoModal(promo = null) {
    const isEdit = promo !== null;
    const content = `
        <form id="promo-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Promo Code</label>
                    <input type="text" class="form-control" name="code" value="${promo?.code || ''}"
                           style="text-transform:uppercase;" required ${isEdit ? 'readonly' : ''}>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <input type="text" class="form-control" name="description" value="${promo?.description || ''}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Discount Type</label>
                    <select class="form-control" name="discount_type" required>
                        <option value="percentage" ${promo?.discount_type === 'percentage' ? 'selected' : ''}>Percentage</option>
                        <option value="fixed" ${promo?.discount_type === 'fixed' ? 'selected' : ''}>Fixed Amount (R)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Discount Value</label>
                    <input type="number" class="form-control" name="discount_value" step="0.01" min="0"
                           value="${promo?.discount_value || ''}" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Minimum Order (R)</label>
                    <input type="number" class="form-control" name="min_order" step="0.01" min="0"
                           value="${promo?.min_order_cents ? (promo.min_order_cents / 100) : ''}">
                </div>
                <div class="form-group">
                    <label>Max Discount (R)</label>
                    <input type="number" class="form-control" name="max_discount" step="0.01" min="0"
                           value="${promo?.max_discount_cents ? (promo.max_discount_cents / 100) : ''}">
                    <div class="form-hint">For percentage discounts</div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Total Usage Limit</label>
                    <input type="number" class="form-control" name="usage_limit" min="0"
                           value="${promo?.usage_limit || ''}" placeholder="Unlimited">
                </div>
                <div class="form-group">
                    <label>Per User Limit</label>
                    <input type="number" class="form-control" name="per_user_limit" min="1"
                           value="${promo?.per_user_limit || 1}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" class="form-control" name="start_at"
                           value="${promo?.start_at ? promo.start_at.split(' ')[0] : ''}">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" class="form-control" name="end_at"
                           value="${promo?.end_at ? promo.end_at.split(' ')[0] : ''}">
                </div>
            </div>
            <div class="form-group">
                <label>Applies To</label>
                <select class="form-control" name="applies_to">
                    <option value="all" ${promo?.applies_to === 'all' ? 'selected' : ''}>All Items</option>
                    <option value="delivery" ${promo?.applies_to === 'delivery' ? 'selected' : ''}>Delivery Orders Only</option>
                    <option value="pickup" ${promo?.applies_to === 'pickup' ? 'selected' : ''}>Pickup Orders Only</option>
                </select>
            </div>
            <div class="form-check" style="margin-bottom:12px;">
                <input type="checkbox" name="first_order_only" ${promo?.first_order_only ? 'checked' : ''}>
                <label>First Order Only</label>
            </div>
            <div class="form-check">
                <input type="checkbox" name="active" ${promo?.active !== 0 ? 'checked' : ''}>
                <label>Active</label>
            </div>
        </form>
    `;

    AdminUI.showModal(isEdit ? 'Edit Promo Code' : 'Add Promo Code', content, [
        { label: 'Cancel', class: 'btn-secondary' },
        {
            label: isEdit ? 'Update' : 'Create',
            class: 'btn-primary',
            onClick: async (modal) => {
                const form = modal.querySelector('#promo-form');
                const formData = new FormData(form);
                const data = {
                    code: formData.get('code').toUpperCase(),
                    description: formData.get('description') || null,
                    discount_type: formData.get('discount_type'),
                    discount_value: parseFloat(formData.get('discount_value')),
                    min_order_cents: Math.round((parseFloat(formData.get('min_order')) || 0) * 100),
                    max_discount_cents: formData.get('max_discount') ? Math.round(parseFloat(formData.get('max_discount')) * 100) : null,
                    usage_limit: formData.get('usage_limit') ? parseInt(formData.get('usage_limit')) : null,
                    per_user_limit: parseInt(formData.get('per_user_limit')) || 1,
                    start_at: formData.get('start_at') || null,
                    end_at: formData.get('end_at') || null,
                    applies_to: formData.get('applies_to'),
                    first_order_only: form.querySelector('[name="first_order_only"]').checked ? 1 : 0,
                    active: form.querySelector('[name="active"]').checked ? 1 : 0
                };

                if (isEdit) {
                    await AdminAPI.updatePromoCode(promo.id, data);
                    AdminUI.toast('Promo code updated');
                } else {
                    const result = await AdminAPI.createPromoCode(data);
                    if (result?.success) {
                        AdminUI.toast('Promo code created');
                    } else {
                        AdminUI.toast(result?.error?.message || 'Failed to create', 'error');
                        return;
                    }
                }
                loadPromoCodes();
            }
        }
    ]);
}

function editPromo(promoId) {
    const promo = promoCodes.find(p => p.id === promoId);
    if (promo) showPromoModal(promo);
}

async function deletePromo(promoId) {
    if (await AdminUI.confirm('Are you sure you want to delete this promo code?')) {
        await AdminAPI.deletePromoCode(promoId);
        AdminUI.toast('Promo code deleted');
        loadPromoCodes();
    }
}
</script>

<?php admin_footer(); ?>
