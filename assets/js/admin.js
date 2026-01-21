/**
 * Junxtion Admin Panel - Core JavaScript
 */

// API Client
const AdminAPI = {
    baseUrl: '/api',
    token: null,

    init() {
        this.token = localStorage.getItem('admin_token');
        if (!this.token && !window.location.pathname.includes('index.php')) {
            window.location.href = '/admin/';
        }
        return this;
    },

    async request(endpoint, options = {}) {
        const url = `${this.baseUrl}${endpoint}`;
        const headers = {
            'Content-Type': 'application/json',
            ...(this.token && { 'Authorization': `Bearer ${this.token}` }),
            ...options.headers
        };

        try {
            const response = await fetch(url, {
                ...options,
                headers
            });

            const data = await response.json();

            if (response.status === 401) {
                localStorage.removeItem('admin_token');
                window.location.href = '/admin/';
                return null;
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    get(endpoint) {
        return this.request(endpoint, { method: 'GET' });
    },

    post(endpoint, body) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    },

    put(endpoint, body) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    },

    delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    },

    // Auth
    async login(email, password) {
        const data = await this.post('/staff/auth/login', { email, password });
        if (data?.success) {
            this.token = data.data.token;
            localStorage.setItem('admin_token', data.data.token);
            if (data.data.refresh_token) {
                localStorage.setItem('admin_refresh_token', data.data.refresh_token);
            }
        }
        return data;
    },

    logout() {
        localStorage.removeItem('admin_token');
        localStorage.removeItem('admin_refresh_token');
        window.location.href = '/admin/';
    },

    // Orders
    getLiveOrders(since = null, status = null) {
        let url = '/admin/orders/live';
        const params = [];
        if (since) params.push(`since=${encodeURIComponent(since)}`);
        if (status) params.push(`status=${encodeURIComponent(status)}`);
        if (params.length) url += '?' + params.join('&');
        return this.get(url);
    },

    getOrders(page = 1, filters = {}) {
        const params = new URLSearchParams({ page, per_page: 50, ...filters });
        return this.get(`/admin/orders?${params}`);
    },

    getOrder(id) {
        return this.get(`/admin/orders/${id}`);
    },

    updateOrderStatus(id, status, notes = null) {
        return this.post(`/admin/orders/${id}/status`, { status, notes });
    },

    getOrderStats() {
        return this.get('/admin/orders/stats');
    },

    // Menu
    getCategories() {
        return this.get('/admin/menu/categories');
    },

    createCategory(data) {
        return this.post('/admin/menu/categories', data);
    },

    updateCategory(id, data) {
        return this.put(`/admin/menu/categories/${id}`, data);
    },

    deleteCategory(id) {
        return this.delete(`/admin/menu/categories/${id}`);
    },

    getItems(categoryId = null) {
        const url = categoryId ? `/admin/menu/items?category_id=${categoryId}` : '/admin/menu/items';
        return this.get(url);
    },

    createItem(data) {
        return this.post('/admin/menu/items', data);
    },

    updateItem(id, data) {
        return this.put(`/admin/menu/items/${id}`, data);
    },

    toggleItem(id) {
        return this.post(`/admin/menu/items/${id}/toggle`);
    },

    deleteItem(id) {
        return this.delete(`/admin/menu/items/${id}`);
    },

    clearMenuCache() {
        return this.post('/admin/menu/clear-cache');
    },

    // Specials
    getSpecials() {
        return this.get('/admin/specials');
    },

    createSpecial(data) {
        return this.post('/admin/specials', data);
    },

    updateSpecial(id, data) {
        return this.put(`/admin/specials/${id}`, data);
    },

    deleteSpecial(id) {
        return this.delete(`/admin/specials/${id}`);
    },

    // Staff
    getStaff() {
        return this.get('/staff/users');
    },

    createStaff(data) {
        return this.post('/staff/users', data);
    },

    updateStaff(id, data) {
        return this.put(`/staff/users/${id}`, data);
    },

    getRoles() {
        return this.get('/staff/roles');
    },

    // Notifications
    sendBroadcast(data) {
        return this.post('/admin/notifications/broadcast', data);
    },

    getNotifications(page = 1) {
        return this.get(`/admin/notifications?page=${page}`);
    },

    // Settings
    getSettings() {
        return this.get('/admin/settings');
    },

    updateSettings(data) {
        return this.put('/admin/settings', data);
    },

    toggleOrdering() {
        return this.post('/admin/settings/toggle-ordering');
    },

    // Promo Codes
    getPromoCodes() {
        return this.get('/admin/promo-codes');
    },

    createPromoCode(data) {
        return this.post('/admin/promo-codes', data);
    },

    updatePromoCode(id, data) {
        return this.put(`/admin/promo-codes/${id}`, data);
    },

    deletePromoCode(id) {
        return this.delete(`/admin/promo-codes/${id}`);
    }
};

// UI Helpers
const AdminUI = {
    // Format currency (cents to rands)
    formatPrice(cents) {
        return 'R' + (cents / 100).toFixed(2);
    },

    // Format date/time
    formatTime(dateString) {
        const date = new Date(dateString);
        return date.toLocaleTimeString('en-ZA', { hour: '2-digit', minute: '2-digit' });
    },

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-ZA', { day: 'numeric', month: 'short', year: 'numeric' });
    },

    formatDateTime(dateString) {
        return `${this.formatDate(dateString)} ${this.formatTime(dateString)}`;
    },

    // Time ago
    timeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const seconds = Math.floor((now - date) / 1000);

        if (seconds < 60) return 'Just now';
        if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
        if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
        return this.formatDate(dateString);
    },

    // Status badge HTML
    statusBadge(status) {
        const statusClass = status.toLowerCase().replace('_', '-');
        const labels = {
            'PENDING_PAYMENT': 'Pending Payment',
            'PLACED': 'Placed',
            'ACCEPTED': 'Accepted',
            'IN_PREP': 'In Prep',
            'READY': 'Ready',
            'OUT_FOR_DELIVERY': 'Out for Delivery',
            'COMPLETED': 'Completed',
            'CANCELLED': 'Cancelled'
        };
        return `<span class="status-badge ${statusClass}">${labels[status] || status}</span>`;
    },

    // Order type badge
    orderTypeBadge(type) {
        const icons = {
            'pickup': 'Pickup',
            'delivery': 'Delivery',
            'dine_in': 'Dine In'
        };
        return `<span class="order-type">${icons[type] || type}</span>`;
    },

    // Show toast notification
    toast(message, type = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        document.body.appendChild(toast);

        setTimeout(() => toast.classList.add('show'), 10);
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    // Confirm dialog
    async confirm(message, title = 'Confirm') {
        return new Promise((resolve) => {
            const modal = document.createElement('div');
            modal.className = 'modal-overlay';
            modal.innerHTML = `
                <div class="modal">
                    <div class="modal-header">
                        <h3>${title}</h3>
                    </div>
                    <div class="modal-body">
                        <p>${message}</p>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" data-action="cancel">Cancel</button>
                        <button class="btn btn-primary" data-action="confirm">Confirm</button>
                    </div>
                </div>
            `;

            modal.querySelector('[data-action="cancel"]').onclick = () => {
                modal.remove();
                resolve(false);
            };
            modal.querySelector('[data-action="confirm"]').onclick = () => {
                modal.remove();
                resolve(true);
            };

            document.body.appendChild(modal);
        });
    },

    // Show modal with content
    showModal(title, content, actions = []) {
        const existingModal = document.querySelector('.modal-overlay');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3>${title}</h3>
                    <button class="modal-close">&times;</button>
                </div>
                <div class="modal-body">${content}</div>
                <div class="modal-footer" id="modal-actions"></div>
            </div>
        `;

        const actionsContainer = modal.querySelector('#modal-actions');
        actions.forEach(action => {
            const btn = document.createElement('button');
            btn.className = `btn ${action.class || 'btn-secondary'}`;
            btn.textContent = action.label;
            btn.onclick = () => {
                if (action.onClick) action.onClick(modal);
                if (action.close !== false) modal.remove();
            };
            actionsContainer.appendChild(btn);
        });

        modal.querySelector('.modal-close').onclick = () => modal.remove();
        modal.onclick = (e) => {
            if (e.target === modal) modal.remove();
        };

        document.body.appendChild(modal);
        return modal;
    },

    // Close modal
    closeModal() {
        const modal = document.querySelector('.modal-overlay');
        if (modal) modal.remove();
    },

    // Loading state
    setLoading(element, loading = true) {
        if (loading) {
            element.classList.add('loading');
            element.disabled = true;
        } else {
            element.classList.remove('loading');
            element.disabled = false;
        }
    },

    // Play notification sound
    playSound(type = 'new_order') {
        const sounds = {
            'new_order': '/assets/sounds/new-order.mp3',
            'ready': '/assets/sounds/ready.mp3'
        };
        const audio = new Audio(sounds[type]);
        audio.play().catch(() => {}); // Ignore autoplay restrictions
    }
};

// Live Orders Manager
class LiveOrdersManager {
    constructor(options = {}) {
        this.container = options.container;
        this.pollInterval = options.pollInterval || 5000;
        this.onNewOrder = options.onNewOrder || (() => {});
        this.lastTimestamp = null;
        this.orders = [];
        this.polling = false;
        this.statusFilter = options.statusFilter || null;
    }

    async start() {
        this.polling = true;
        await this.fetchOrders();
        this.poll();
    }

    stop() {
        this.polling = false;
    }

    async poll() {
        if (!this.polling) return;

        try {
            const data = await AdminAPI.getLiveOrders(this.lastTimestamp, this.statusFilter);
            if (data?.success) {
                this.lastTimestamp = data.data.timestamp;
                const newOrders = data.data.orders.filter(o =>
                    !this.orders.find(existing => existing.id === o.id)
                );

                if (newOrders.length > 0) {
                    this.onNewOrder(newOrders);
                }

                this.orders = data.data.orders;
                this.render();
            }
        } catch (error) {
            console.error('Polling error:', error);
        }

        setTimeout(() => this.poll(), this.pollInterval);
    }

    async fetchOrders() {
        const data = await AdminAPI.getLiveOrders(null, this.statusFilter);
        if (data?.success) {
            this.lastTimestamp = data.data.timestamp;
            this.orders = data.data.orders;
            this.render();
        }
    }

    render() {
        if (!this.container) return;

        if (this.orders.length === 0) {
            this.container.innerHTML = `
                <div class="empty-state">
                    <p>No active orders</p>
                </div>
            `;
            return;
        }

        this.container.innerHTML = this.orders.map(order => this.renderOrderCard(order)).join('');

        // Bind action buttons
        this.container.querySelectorAll('[data-action]').forEach(btn => {
            btn.onclick = () => this.handleAction(btn.dataset.action, btn.dataset.orderId);
        });
    }

    renderOrderCard(order) {
        const isNew = order.status === 'PLACED';
        const items = order.items || [];

        return `
            <div class="order-card ${isNew ? 'new' : ''}" data-order-id="${order.id}">
                <div class="order-card-header">
                    <span class="order-number">#${order.id}</span>
                    <span class="order-time">${AdminUI.timeAgo(order.created_at)}</span>
                </div>
                ${AdminUI.orderTypeBadge(order.order_type)}
                ${AdminUI.statusBadge(order.status)}
                <div class="order-customer">
                    ${order.customer_name || 'Guest'} ${order.customer_phone ? `- ${order.customer_phone}` : ''}
                </div>
                <div class="order-items">
                    ${items.map(item => `
                        <div class="order-item">
                            <span><span class="order-item-qty">${item.qty}x</span> ${item.name_snapshot}</span>
                            <span>${AdminUI.formatPrice(item.subtotal_cents)}</span>
                        </div>
                    `).join('')}
                </div>
                <div class="order-total">
                    <strong>Total: ${AdminUI.formatPrice(order.total_cents)}</strong>
                </div>
                <div class="order-actions">
                    ${this.getActionButtons(order)}
                </div>
            </div>
        `;
    }

    getActionButtons(order) {
        const buttons = {
            'PLACED': `
                <button class="btn btn-success" data-action="accept" data-order-id="${order.id}">Accept</button>
                <button class="btn btn-danger" data-action="cancel" data-order-id="${order.id}">Cancel</button>
            `,
            'ACCEPTED': `
                <button class="btn btn-primary" data-action="start_prep" data-order-id="${order.id}">Start Prep</button>
            `,
            'IN_PREP': `
                <button class="btn btn-success" data-action="ready" data-order-id="${order.id}">Mark Ready</button>
            `,
            'READY': order.order_type === 'delivery'
                ? `<button class="btn btn-primary" data-action="out_for_delivery" data-order-id="${order.id}">Out for Delivery</button>`
                : `<button class="btn btn-success" data-action="complete" data-order-id="${order.id}">Complete</button>`,
            'OUT_FOR_DELIVERY': `
                <button class="btn btn-success" data-action="complete" data-order-id="${order.id}">Complete</button>
            `
        };
        return buttons[order.status] || '';
    }

    async handleAction(action, orderId) {
        const statusMap = {
            'accept': 'ACCEPTED',
            'start_prep': 'IN_PREP',
            'ready': 'READY',
            'out_for_delivery': 'OUT_FOR_DELIVERY',
            'complete': 'COMPLETED',
            'cancel': 'CANCELLED'
        };

        if (action === 'cancel') {
            const confirmed = await AdminUI.confirm('Are you sure you want to cancel this order?');
            if (!confirmed) return;
        }

        const newStatus = statusMap[action];
        if (!newStatus) return;

        const data = await AdminAPI.updateOrderStatus(orderId, newStatus);
        if (data?.success) {
            AdminUI.toast(`Order #${orderId} updated to ${newStatus}`);
            await this.fetchOrders();
        } else {
            AdminUI.toast(data?.error?.message || 'Failed to update order', 'error');
        }
    }
}

// Kitchen Mode Manager (Large tiles for kitchen display)
class KitchenMode {
    constructor(container) {
        this.container = container;
        this.orders = [];
        this.polling = false;
    }

    async start() {
        this.polling = true;
        document.body.classList.add('kitchen-mode');
        await this.fetchOrders();
        this.poll();
    }

    stop() {
        this.polling = false;
        document.body.classList.remove('kitchen-mode');
    }

    async poll() {
        if (!this.polling) return;
        await this.fetchOrders();
        setTimeout(() => this.poll(), 3000);
    }

    async fetchOrders() {
        const data = await AdminAPI.getLiveOrders();
        if (data?.success) {
            const oldCount = this.orders.length;
            this.orders = data.data.orders.filter(o =>
                ['PLACED', 'ACCEPTED', 'IN_PREP'].includes(o.status)
            );

            // Play sound for new orders
            if (this.orders.length > oldCount) {
                AdminUI.playSound('new_order');
            }

            this.render();
        }
    }

    render() {
        if (!this.container) return;

        if (this.orders.length === 0) {
            this.container.innerHTML = `
                <div class="kitchen-empty">
                    <h2>No Active Orders</h2>
                    <p>Waiting for orders...</p>
                </div>
            `;
            return;
        }

        this.container.innerHTML = this.orders.map(order => this.renderTile(order)).join('');

        // Bind buttons
        this.container.querySelectorAll('[data-action]').forEach(btn => {
            btn.onclick = () => this.handleAction(btn.dataset.action, btn.dataset.orderId);
        });
    }

    renderTile(order) {
        const statusColors = {
            'PLACED': 'tile-new',
            'ACCEPTED': 'tile-accepted',
            'IN_PREP': 'tile-prep'
        };

        const items = order.items || [];

        return `
            <div class="kitchen-tile ${statusColors[order.status] || ''}">
                <div class="tile-header">
                    <span class="tile-number">#${order.id}</span>
                    <span class="tile-type">${order.order_type.toUpperCase()}</span>
                </div>
                <div class="tile-time">${AdminUI.timeAgo(order.created_at)}</div>
                <div class="tile-items">
                    ${items.map(item => `
                        <div class="tile-item">
                            <span class="tile-qty">${item.qty}</span>
                            <span class="tile-name">${item.name_snapshot}</span>
                            ${item.modifiers ? `<div class="tile-mods">${item.modifiers}</div>` : ''}
                        </div>
                    `).join('')}
                </div>
                ${order.notes ? `<div class="tile-notes">${order.notes}</div>` : ''}
                <div class="tile-actions">
                    ${this.getTileButton(order)}
                </div>
            </div>
        `;
    }

    getTileButton(order) {
        const buttons = {
            'PLACED': `<button class="tile-btn tile-btn-accept" data-action="accept" data-order-id="${order.id}">ACCEPT</button>`,
            'ACCEPTED': `<button class="tile-btn tile-btn-prep" data-action="start_prep" data-order-id="${order.id}">START PREP</button>`,
            'IN_PREP': `<button class="tile-btn tile-btn-ready" data-action="ready" data-order-id="${order.id}">READY</button>`
        };
        return buttons[order.status] || '';
    }

    async handleAction(action, orderId) {
        const statusMap = {
            'accept': 'ACCEPTED',
            'start_prep': 'IN_PREP',
            'ready': 'READY'
        };

        const newStatus = statusMap[action];
        if (!newStatus) return;

        await AdminAPI.updateOrderStatus(orderId, newStatus);
        await this.fetchOrders();
    }
}

// Export for use
window.AdminAPI = AdminAPI;
window.AdminUI = AdminUI;
window.LiveOrdersManager = LiveOrdersManager;
window.KitchenMode = KitchenMode;

// Auto-init
document.addEventListener('DOMContentLoaded', () => {
    AdminAPI.init();
});
