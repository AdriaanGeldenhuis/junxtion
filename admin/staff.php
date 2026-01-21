<?php
/**
 * Admin Staff Management
 */
require_once __DIR__ . '/includes/layout.php';
admin_header('Staff', 'staff');
?>

<div style="margin-bottom:20px;">
    <button class="btn btn-primary" onclick="showStaffModal()">+ Add Staff Member</button>
</div>

<div class="table-container">
    <table class="data-table" id="staff-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Last Login</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="staff-tbody">
            <tr><td colspan="6" class="empty-state">Loading...</td></tr>
        </tbody>
    </table>
</div>

<script>
let staffList = [];
let roles = [];

document.addEventListener('DOMContentLoaded', () => {
    loadRoles();
    loadStaff();
});

async function loadRoles() {
    const data = await AdminAPI.getRoles();
    if (data?.success) {
        roles = data.data;
    }
}

async function loadStaff() {
    const data = await AdminAPI.getStaff();
    if (data?.success) {
        staffList = data.data;
        renderStaff();
    }
}

function renderStaff() {
    const tbody = document.getElementById('staff-tbody');

    if (staffList.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No staff members</td></tr>';
        return;
    }

    tbody.innerHTML = staffList.map(staff => `
        <tr>
            <td><strong>${staff.full_name}</strong></td>
            <td>${staff.email}</td>
            <td><span class="status-badge">${staff.role_name || 'Unknown'}</span></td>
            <td>
                <span class="status-badge ${staff.active ? 'completed' : 'cancelled'}">
                    ${staff.active ? 'Active' : 'Inactive'}
                </span>
            </td>
            <td>${staff.last_login ? AdminUI.formatDateTime(staff.last_login) : 'Never'}</td>
            <td>
                <div class="btn-group">
                    <button class="btn btn-sm btn-secondary" onclick="editStaff(${staff.id})">Edit</button>
                    <button class="btn btn-sm btn-${staff.active ? 'danger' : 'success'}"
                            onclick="toggleStaffStatus(${staff.id}, ${staff.active})">
                        ${staff.active ? 'Deactivate' : 'Activate'}
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function showStaffModal(staff = null) {
    const isEdit = staff !== null;
    const content = `
        <form id="staff-form">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" class="form-control" name="full_name" value="${staff?.full_name || ''}" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" class="form-control" name="email" value="${staff?.email || ''}" required>
            </div>
            ${!isEdit ? `
            <div class="form-group">
                <label>Password</label>
                <input type="password" class="form-control" name="password" minlength="8" required>
                <div class="form-hint">Minimum 8 characters</div>
            </div>
            ` : ''}
            <div class="form-group">
                <label>Role</label>
                <select class="form-control" name="role_id" required>
                    ${roles.map(role => `
                        <option value="${role.id}" ${staff?.role_id === role.id ? 'selected' : ''}>
                            ${role.name} - ${role.description || ''}
                        </option>
                    `).join('')}
                </select>
            </div>
            <div class="form-group">
                <label>Phone (optional)</label>
                <input type="tel" class="form-control" name="phone" value="${staff?.phone || ''}">
            </div>
            <div class="form-check">
                <input type="checkbox" name="active" ${staff?.active !== false ? 'checked' : ''}>
                <label>Active</label>
            </div>
        </form>
    `;

    AdminUI.showModal(isEdit ? 'Edit Staff Member' : 'Add Staff Member', content, [
        { label: 'Cancel', class: 'btn-secondary' },
        {
            label: isEdit ? 'Update' : 'Create',
            class: 'btn-primary',
            onClick: async (modal) => {
                const form = modal.querySelector('#staff-form');
                const formData = new FormData(form);
                const data = {
                    full_name: formData.get('full_name'),
                    email: formData.get('email'),
                    role_id: parseInt(formData.get('role_id')),
                    phone: formData.get('phone') || null,
                    active: form.querySelector('[name="active"]').checked ? 1 : 0
                };

                if (!isEdit) {
                    data.password = formData.get('password');
                }

                if (isEdit) {
                    await AdminAPI.updateStaff(staff.id, data);
                    AdminUI.toast('Staff member updated');
                } else {
                    const result = await AdminAPI.createStaff(data);
                    if (result?.success) {
                        AdminUI.toast('Staff member created');
                    } else {
                        AdminUI.toast(result?.error?.message || 'Failed to create staff', 'error');
                        return;
                    }
                }
                loadStaff();
            }
        }
    ]);
}

function editStaff(staffId) {
    const staff = staffList.find(s => s.id === staffId);
    if (staff) showStaffModal(staff);
}

async function toggleStaffStatus(staffId, currentStatus) {
    const action = currentStatus ? 'deactivate' : 'activate';
    if (await AdminUI.confirm(`Are you sure you want to ${action} this staff member?`)) {
        await AdminAPI.updateStaff(staffId, { active: currentStatus ? 0 : 1 });
        AdminUI.toast(`Staff member ${action}d`);
        loadStaff();
    }
}
</script>

<?php admin_footer(); ?>
