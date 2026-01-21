<?php
/**
 * Admin Menu Management
 */
require_once __DIR__ . '/includes/layout.php';
admin_header('Menu', 'menu');
?>

<div class="menu-editor">
    <div class="categories-list">
        <h3>Categories</h3>
        <div id="categories-container">
            <div class="category-item active" data-category-id="">All Items</div>
        </div>
        <button class="btn btn-primary btn-sm" style="width:100%;margin-top:16px;" onclick="showCategoryModal()">
            + Add Category
        </button>
    </div>

    <div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <div class="btn-group">
                <button class="btn btn-primary" onclick="showItemModal()">+ Add Item</button>
                <button class="btn btn-secondary" onclick="clearCache()">Clear Cache</button>
            </div>
        </div>

        <div class="items-grid" id="items-container">
            <div class="empty-state">Loading menu items...</div>
        </div>
    </div>
</div>

<script>
let categories = [];
let items = [];
let currentCategory = null;

document.addEventListener('DOMContentLoaded', () => {
    loadCategories();
    loadItems();
});

async function loadCategories() {
    const data = await AdminAPI.getCategories();
    if (data?.success) {
        categories = data.data;
        renderCategories();
    }
}

async function loadItems(categoryId = null) {
    const data = await AdminAPI.getItems(categoryId);
    if (data?.success) {
        items = data.data;
        renderItems();
    }
}

function renderCategories() {
    const container = document.getElementById('categories-container');
    container.innerHTML = `
        <div class="category-item ${currentCategory === null ? 'active' : ''}"
             data-category-id=""
             onclick="selectCategory(null)">
            All Items
            <span class="count">${items.length}</span>
        </div>
        ${categories.map(cat => `
            <div class="category-item ${currentCategory === cat.id ? 'active' : ''}"
                 data-category-id="${cat.id}"
                 onclick="selectCategory(${cat.id})">
                ${cat.name}
                <span class="count">${cat.item_count || 0}</span>
            </div>
        `).join('')}
    `;
}

function renderItems() {
    const container = document.getElementById('items-container');

    if (items.length === 0) {
        container.innerHTML = '<div class="empty-state"><h3>No items</h3><p>Add your first menu item</p></div>';
        return;
    }

    container.innerHTML = items.map(item => `
        <div class="item-card ${!item.available ? 'item-unavailable' : ''}">
            <img src="${item.image_path || '/assets/images/placeholder.jpg'}"
                 alt="${item.name}"
                 class="item-card-image"
                 onerror="this.src='/assets/images/placeholder.jpg'">
            <div class="item-card-content">
                <div class="item-card-name">${item.name}</div>
                <div class="item-card-desc">${item.description || ''}</div>
                <div class="item-card-price">${AdminUI.formatPrice(item.price_cents)}</div>
            </div>
            <div class="item-card-footer">
                <label class="toggle-switch">
                    <input type="checkbox" ${item.available ? 'checked' : ''}
                           onchange="toggleItem(${item.id})">
                    <span class="toggle-slider"></span>
                </label>
                <div class="item-card-actions">
                    <button class="btn btn-sm btn-secondary" onclick="editItem(${item.id})">Edit</button>
                    <button class="btn btn-sm btn-danger" onclick="deleteItem(${item.id})">Delete</button>
                </div>
            </div>
        </div>
    `).join('');
}

function selectCategory(categoryId) {
    currentCategory = categoryId;
    loadItems(categoryId);
    renderCategories();
}

async function toggleItem(itemId) {
    await AdminAPI.toggleItem(itemId);
    loadItems(currentCategory);
}

async function deleteItem(itemId) {
    if (await AdminUI.confirm('Are you sure you want to delete this item?')) {
        await AdminAPI.deleteItem(itemId);
        AdminUI.toast('Item deleted');
        loadItems(currentCategory);
    }
}

function showCategoryModal(category = null) {
    const isEdit = category !== null;
    const content = `
        <form id="category-form">
            <div class="form-group">
                <label>Category Name</label>
                <input type="text" class="form-control" name="name" value="${category?.name || ''}" required>
            </div>
            <div class="form-group">
                <label>Sort Order</label>
                <input type="number" class="form-control" name="sort_order" value="${category?.sort_order || 0}">
            </div>
            <div class="form-check">
                <input type="checkbox" name="active" ${category?.active !== false ? 'checked' : ''}>
                <label>Active</label>
            </div>
        </form>
    `;

    AdminUI.showModal(isEdit ? 'Edit Category' : 'Add Category', content, [
        { label: 'Cancel', class: 'btn-secondary' },
        {
            label: isEdit ? 'Update' : 'Create',
            class: 'btn-primary',
            onClick: async (modal) => {
                const form = modal.querySelector('#category-form');
                const formData = new FormData(form);
                const data = {
                    name: formData.get('name'),
                    sort_order: parseInt(formData.get('sort_order')) || 0,
                    active: form.querySelector('[name="active"]').checked ? 1 : 0
                };

                if (isEdit) {
                    await AdminAPI.updateCategory(category.id, data);
                    AdminUI.toast('Category updated');
                } else {
                    await AdminAPI.createCategory(data);
                    AdminUI.toast('Category created');
                }
                loadCategories();
            }
        }
    ]);
}

function showItemModal(item = null) {
    const isEdit = item !== null;
    const content = `
        <form id="item-form">
            <div class="form-group">
                <label>Category</label>
                <select class="form-control" name="category_id" required>
                    ${categories.map(cat => `
                        <option value="${cat.id}" ${item?.category_id === cat.id ? 'selected' : ''}>
                            ${cat.name}
                        </option>
                    `).join('')}
                </select>
            </div>
            <div class="form-group">
                <label>Item Name</label>
                <input type="text" class="form-control" name="name" value="${item?.name || ''}" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea class="form-control" name="description" rows="3">${item?.description || ''}</textarea>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price (Rands)</label>
                    <input type="number" class="form-control" name="price" step="0.01" min="0"
                           value="${item ? (item.price_cents / 100).toFixed(2) : ''}" required>
                </div>
                <div class="form-group">
                    <label>Sort Order</label>
                    <input type="number" class="form-control" name="sort_order" value="${item?.sort_order || 0}">
                </div>
            </div>
            <div class="form-group">
                <label>Image URL (optional)</label>
                <input type="text" class="form-control" name="image_path" value="${item?.image_path || ''}">
            </div>
            <div class="form-check">
                <input type="checkbox" name="available" ${item?.available !== false ? 'checked' : ''}>
                <label>Available</label>
            </div>
        </form>
    `;

    AdminUI.showModal(isEdit ? 'Edit Item' : 'Add Item', content, [
        { label: 'Cancel', class: 'btn-secondary' },
        {
            label: isEdit ? 'Update' : 'Create',
            class: 'btn-primary',
            onClick: async (modal) => {
                const form = modal.querySelector('#item-form');
                const formData = new FormData(form);
                const data = {
                    category_id: parseInt(formData.get('category_id')),
                    name: formData.get('name'),
                    description: formData.get('description'),
                    price_cents: Math.round(parseFloat(formData.get('price')) * 100),
                    sort_order: parseInt(formData.get('sort_order')) || 0,
                    image_path: formData.get('image_path') || null,
                    available: form.querySelector('[name="available"]').checked ? 1 : 0
                };

                if (isEdit) {
                    await AdminAPI.updateItem(item.id, data);
                    AdminUI.toast('Item updated');
                } else {
                    await AdminAPI.createItem(data);
                    AdminUI.toast('Item created');
                }
                loadItems(currentCategory);
                loadCategories();
            }
        }
    ]);
}

async function editItem(itemId) {
    const item = items.find(i => i.id === itemId);
    if (item) showItemModal(item);
}

async function clearCache() {
    await AdminAPI.clearMenuCache();
    AdminUI.toast('Menu cache cleared');
}
</script>

<?php admin_footer(); ?>
