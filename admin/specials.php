<?php
/**
 * Admin Specials Management
 */
require_once __DIR__ . '/includes/layout.php';
admin_header('Specials', 'specials');
?>

<div style="margin-bottom:20px;">
    <button class="btn btn-primary" onclick="showSpecialModal()">+ Add Special</button>
</div>

<div class="items-grid" id="specials-container">
    <div class="empty-state">Loading specials...</div>
</div>

<script>
let specials = [];

document.addEventListener('DOMContentLoaded', () => {
    loadSpecials();
});

async function loadSpecials() {
    const data = await AdminAPI.getSpecials();
    if (data?.success) {
        specials = data.data;
        renderSpecials();
    }
}

function renderSpecials() {
    const container = document.getElementById('specials-container');

    if (specials.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No specials</h3><p>Create your first special offer</p></div>';
        return;
    }

    container.innerHTML = specials.map(special => `
        <div class="item-card ${!special.active ? 'item-unavailable' : ''}">
            ${special.image_path ? `
                <img src="${special.image_path}" alt="${special.title}" class="item-card-image">
            ` : `
                <div class="item-card-image" style="display:flex;align-items:center;justify-content:center;font-size:48px;">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polygon points="12,2 15,8.5 22,9.3 17,14 18.2,21 12,17.8 5.8,21 7,14 2,9.3 9,8.5"/></svg>
                </div>
            `}
            <div class="item-card-content">
                <div class="item-card-name">${special.title}</div>
                <div class="item-card-desc">${special.body || ''}</div>
                ${special.discount_type !== 'none' ? `
                    <div class="item-card-price">
                        ${special.discount_type === 'percentage' ? special.discount_value + '%' : AdminUI.formatPrice(special.discount_value * 100)} off
                    </div>
                ` : ''}
                ${special.promo_code ? `<div style="margin-top:8px;"><span class="status-badge">Code: ${special.promo_code}</span></div>` : ''}
            </div>
            <div class="item-card-footer">
                <div style="font-size:12px;color:var(--admin-text-muted);">
                    ${special.start_at ? `From: ${AdminUI.formatDate(special.start_at)}` : 'No start date'}
                    ${special.end_at ? `<br>To: ${AdminUI.formatDate(special.end_at)}` : ''}
                </div>
                <div class="item-card-actions">
                    <button class="btn btn-sm btn-secondary" onclick="editSpecial(${special.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteSpecial(${special.id})">Delete</button>
                </div>
            </div>
        </div>
    `).join('');
}

function showSpecialModal(special = null) {
    const isEdit = special !== null;
    const content = `
        <form id="special-form">
            <div class="form-group">
                <label>Title</label>
                <input type="text" class="form-control" name="title" value="${special?.title || ''}" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="body" rows="3">${special?.body || ''}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Discount Type</label>
                    <select class="form-control" name="discount_type">
                        <option value="none" ${special?.discount_type === 'none' ? 'selected' : ''}>None</option>
                        <option value="percentage" ${special?.discount_type === 'percentage' ? 'selected' : ''}>Percentage</option>
                        <option value="fixed" ${special?.discount_type === 'fixed' ? 'selected' : ''}>Fixed Amount</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Discount Value</label>
                    <input type="number" class="form-control" name="discount_value" step="0.01"
                           value="${special?.discount_value || 0}">
                </div>
            </div>
            <div class="form-group">
                <label>Promo Code (optional)</label>
                <input type="text" class="form-control" name="promo_code" value="${special?.promo_code || ''}"
                       style="text-transform:uppercase;">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Start Date (optional)</label>
                    <input type="date" class="form-control" name="start_at"
                           value="${special?.start_at ? special.start_at.split(' ')[0] : ''}">
                </div>
                <div class="form-group">
                    <label>End Date (optional)</label>
                    <input type="date" class="form-control" name="end_at"
                           value="${special?.end_at ? special.end_at.split(' ')[0] : ''}">
                </div>
            </div>
            <div class="form-group">
                <label>Image URL (optional)</label>
                <input type="text" class="form-control" name="image_path" value="${special?.image_path || ''}">
            </div>
            <div class="form-check">
                <input type="checkbox" name="active" ${special?.active !== 0 ? 'checked' : ''}>
                <label>Active</label>
            </div>
        </form>
    `;

    AdminUI.showModal(isEdit ? 'Edit Special' : 'Add Special', content, [
        { label: 'Cancel', class: 'btn-secondary' },
        {
            label: isEdit ? 'Update' : 'Create',
            class: 'btn-primary',
            onClick: async (modal) => {
                const form = modal.querySelector('#special-form');
                const formData = new FormData(form);
                const data = {
                    title: formData.get('title'),
                    body: formData.get('body'),
                    discount_type: formData.get('discount_type'),
                    discount_value: parseFloat(formData.get('discount_value')) || 0,
                    promo_code: formData.get('promo_code')?.toUpperCase() || null,
                    start_at: formData.get('start_at') || null,
                    end_at: formData.get('end_at') || null,
                    image_path: formData.get('image_path') || null,
                    active: form.querySelector('[name="active"]').checked ? 1 : 0
                };

                if (isEdit) {
                    await AdminAPI.updateSpecial(special.id, data);
                    AdminUI.toast('Special updated');
                } else {
                    await AdminAPI.createSpecial(data);
                    AdminUI.toast('Special created');
                }
                loadSpecials();
            }
        }
    ]);
}

function editSpecial(specialId) {
    const special = specials.find(s => s.id === specialId);
    if (special) showSpecialModal(special);
}

async function deleteSpecial(specialId) {
    if (await AdminUI.confirm('Are you sure you want to delete this special?')) {
        await AdminAPI.deleteSpecial(specialId);
        AdminUI.toast('Special deleted');
        loadSpecials();
    }
}
</script>

<?php admin_footer(); ?>
