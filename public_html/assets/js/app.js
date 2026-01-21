/**
 * Junxtion App JavaScript
 *
 * Core functionality for customer webapp
 */

(function() {
  'use strict';

  // ========================================
  // App State
  // ========================================
  const App = {
    config: window.APP_CONFIG || {},
    cart: [],
    user: null,
    token: null,

    init() {
      this.loadState();
      this.setupEventListeners();
      this.updateCartBadge();
      console.log('Junxtion App initialized');
    },

    // ========================================
    // State Management
    // ========================================
    loadState() {
      try {
        this.cart = JSON.parse(localStorage.getItem('junxtion_cart')) || [];
        this.token = localStorage.getItem('junxtion_token');
        this.user = JSON.parse(localStorage.getItem('junxtion_user'));
      } catch (e) {
        console.error('Error loading state:', e);
        this.cart = [];
        this.token = null;
        this.user = null;
      }
    },

    saveState() {
      try {
        localStorage.setItem('junxtion_cart', JSON.stringify(this.cart));
        if (this.token) {
          localStorage.setItem('junxtion_token', this.token);
        }
        if (this.user) {
          localStorage.setItem('junxtion_user', JSON.stringify(this.user));
        }
      } catch (e) {
        console.error('Error saving state:', e);
      }
    },

    // ========================================
    // Event Listeners
    // ========================================
    setupEventListeners() {
      // Navigation
      document.querySelectorAll('.nav-item').forEach(item => {
        item.addEventListener('click', (e) => {
          document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
          e.currentTarget.classList.add('active');
        });
      });

      // Quantity controls
      document.addEventListener('click', (e) => {
        if (e.target.closest('.quantity-btn')) {
          const btn = e.target.closest('.quantity-btn');
          const action = btn.dataset.action;
          const itemId = btn.dataset.itemId;
          if (action === 'increase') this.increaseQuantity(itemId);
          if (action === 'decrease') this.decreaseQuantity(itemId);
        }
      });
    },

    // ========================================
    // API Helpers
    // ========================================
    async api(endpoint, options = {}) {
      const url = `${this.config.apiUrl}${endpoint}`;

      const headers = {
        'Content-Type': 'application/json',
        ...options.headers
      };

      if (this.token) {
        headers['Authorization'] = `Bearer ${this.token}`;
      }

      try {
        const response = await fetch(url, {
          ...options,
          headers
        });

        const data = await response.json();

        if (!response.ok) {
          throw new Error(data.error?.message || 'Request failed');
        }

        return data;
      } catch (error) {
        console.error('API Error:', error);
        throw error;
      }
    },

    // ========================================
    // Cart Functions
    // ========================================
    addToCart(item) {
      const existingIndex = this.cart.findIndex(
        i => i.id === item.id && JSON.stringify(i.modifiers) === JSON.stringify(item.modifiers)
      );

      if (existingIndex > -1) {
        this.cart[existingIndex].quantity += item.quantity || 1;
      } else {
        this.cart.push({
          ...item,
          quantity: item.quantity || 1,
          cartId: Date.now().toString(36)
        });
      }

      this.saveState();
      this.updateCartBadge();
      this.showToast('Added to cart', 'success');
    },

    removeFromCart(cartId) {
      this.cart = this.cart.filter(item => item.cartId !== cartId);
      this.saveState();
      this.updateCartBadge();
    },

    increaseQuantity(cartId) {
      const item = this.cart.find(i => i.cartId === cartId);
      if (item) {
        item.quantity++;
        this.saveState();
        this.renderCart();
      }
    },

    decreaseQuantity(cartId) {
      const item = this.cart.find(i => i.cartId === cartId);
      if (item) {
        if (item.quantity > 1) {
          item.quantity--;
        } else {
          this.removeFromCart(cartId);
        }
        this.saveState();
        this.renderCart();
      }
    },

    clearCart() {
      this.cart = [];
      this.saveState();
      this.updateCartBadge();
    },

    getCartTotal() {
      return this.cart.reduce((total, item) => {
        let itemTotal = item.price * item.quantity;
        if (item.modifiers) {
          item.modifiers.forEach(mod => {
            itemTotal += (mod.priceDelta || 0) * item.quantity;
          });
        }
        return total + itemTotal;
      }, 0);
    },

    getCartCount() {
      return this.cart.reduce((count, item) => count + item.quantity, 0);
    },

    updateCartBadge() {
      const badge = document.getElementById('cart-badge');
      if (badge) {
        const count = this.getCartCount();
        badge.textContent = count;
        badge.style.display = count > 0 ? 'flex' : 'none';
      }
    },

    renderCart() {
      const cartContainer = document.getElementById('cart-items');
      if (!cartContainer) return;

      if (this.cart.length === 0) {
        cartContainer.innerHTML = `
          <div class="text-center mt-4">
            <p class="text-muted">Your cart is empty</p>
            <a href="/app/menu.php" class="btn btn-primary mt-4">Browse Menu</a>
          </div>
        `;
        return;
      }

      cartContainer.innerHTML = this.cart.map(item => `
        <div class="cart-item" data-cart-id="${item.cartId}">
          <img src="${item.image || '/assets/img/placeholder.png'}" alt="${item.name}" class="cart-item-image">
          <div class="cart-item-details">
            <div>
              <div class="cart-item-name">${item.name}</div>
              ${item.modifiers ? `<div class="cart-item-modifiers">${item.modifiers.map(m => m.name).join(', ')}</div>` : ''}
            </div>
            <div class="cart-item-actions">
              <span class="cart-item-price">R${this.formatPrice(item.price * item.quantity)}</span>
              <div class="quantity-control">
                <button class="quantity-btn" data-action="decrease" data-item-id="${item.cartId}">-</button>
                <span class="quantity-value">${item.quantity}</span>
                <button class="quantity-btn" data-action="increase" data-item-id="${item.cartId}">+</button>
              </div>
            </div>
          </div>
        </div>
      `).join('');
    },

    // ========================================
    // Auth Functions
    // ========================================
    async requestOtp(phone) {
      return this.api('/auth/otp/request', {
        method: 'POST',
        body: JSON.stringify({ phone })
      });
    },

    async verifyOtp(phone, code) {
      const response = await this.api('/auth/otp/verify', {
        method: 'POST',
        body: JSON.stringify({ phone, code })
      });

      if (response.success) {
        this.token = response.data.token;
        this.user = response.data.user;
        this.saveState();
      }

      return response;
    },

    async logout() {
      try {
        await this.api('/auth/logout', { method: 'POST' });
      } catch (e) {
        // Ignore errors on logout
      }

      this.token = null;
      this.user = null;
      localStorage.removeItem('junxtion_token');
      localStorage.removeItem('junxtion_user');
    },

    isAuthenticated() {
      return !!this.token && !!this.user;
    },

    // ========================================
    // Menu Functions
    // ========================================
    async loadMenu() {
      try {
        const response = await this.api('/menu');
        return response.data;
      } catch (error) {
        console.error('Error loading menu:', error);
        return null;
      }
    },

    // ========================================
    // Order Functions
    // ========================================
    async createOrder(orderData) {
      return this.api('/orders', {
        method: 'POST',
        body: JSON.stringify(orderData)
      });
    },

    async getOrders() {
      return this.api('/orders');
    },

    async getOrder(orderId) {
      return this.api(`/orders/${orderId}`);
    },

    // ========================================
    // UI Helpers
    // ========================================
    formatPrice(cents) {
      return (cents / 100).toFixed(2);
    },

    showToast(message, type = 'info') {
      // Remove existing toast
      const existingToast = document.querySelector('.toast');
      if (existingToast) {
        existingToast.remove();
      }

      // Create new toast
      const toast = document.createElement('div');
      toast.className = `toast ${type}`;
      toast.textContent = message;
      document.body.appendChild(toast);

      // Show toast
      setTimeout(() => toast.classList.add('show'), 10);

      // Hide and remove toast
      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
      }, 3000);
    },

    showModal(content) {
      const overlay = document.createElement('div');
      overlay.className = 'modal-overlay';
      overlay.innerHTML = `
        <div class="modal">
          <div class="modal-handle"></div>
          ${content}
        </div>
      `;

      document.body.appendChild(overlay);
      document.body.style.overflow = 'hidden';

      // Show modal
      setTimeout(() => overlay.classList.add('show'), 10);

      // Close on overlay click
      overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
          this.closeModal(overlay);
        }
      });

      // Close button
      const closeBtn = overlay.querySelector('.modal-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', () => this.closeModal(overlay));
      }

      return overlay;
    },

    closeModal(overlay) {
      overlay.classList.remove('show');
      document.body.style.overflow = '';
      setTimeout(() => overlay.remove(), 300);
    },

    // Skeleton loading
    showSkeleton(container, count = 4) {
      const skeletons = Array(count).fill(`
        <div class="menu-item">
          <div class="skeleton skeleton-image"></div>
          <div class="menu-item-content">
            <div class="skeleton skeleton-text"></div>
            <div class="skeleton skeleton-text short"></div>
          </div>
        </div>
      `).join('');

      container.innerHTML = `<div class="menu-grid">${skeletons}</div>`;
    }
  };

  // ========================================
  // Initialize
  // ========================================
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => App.init());
  } else {
    App.init();
  }

  // Expose to global scope
  window.JunxtionApp = App;
})();
