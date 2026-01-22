/**
 * Junxtion Customer App - Core JavaScript
 */

const JunxtionApp = {
    config: window.APP_CONFIG || {},
    cart: [],
    user: null,
    token: null,

    /**
     * Initialize the app
     */
    init() {
        this.loadState();
        this.updateCartBadge();
        console.log('Junxtion App initialized');
        return this;
    },

    /**
     * Load state from localStorage
     */
    loadState() {
        try {
            this.cart = JSON.parse(localStorage.getItem('junxtion_cart')) || [];
            this.token = localStorage.getItem('junxtion_token');
            const userJson = localStorage.getItem('junxtion_user');
            this.user = userJson ? JSON.parse(userJson) : null;
        } catch (e) {
            console.error('Error loading state:', e);
            this.cart = [];
            this.token = null;
            this.user = null;
        }
    },

    /**
     * API Request
     */
    async request(endpoint, options = {}) {
        const url = (this.config.apiUrl || '/api') + endpoint;
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
                this.logout();
                return null;
            }

            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    // ========================================
    // Cart Management
    // ========================================
    getCart() {
        return this.cart;
    },

    saveCart(cart) {
        this.cart = cart;
        localStorage.setItem('junxtion_cart', JSON.stringify(cart));
        this.updateCartBadge();
    },

    addToCart(item) {
        // Generate unique key for item with modifiers
        const modifierKey = item.modifiers
            ? item.modifiers.map(m => m.id).sort().join('-')
            : '';
        const itemKey = `${item.id}-${modifierKey}`;

        // Check if same item+modifiers exists
        const existingIndex = this.cart.findIndex(cartItem => {
            const existingModKey = cartItem.modifiers
                ? cartItem.modifiers.map(m => m.id).sort().join('-')
                : '';
            return `${cartItem.id}-${existingModKey}` === itemKey;
        });

        if (existingIndex >= 0) {
            this.cart[existingIndex].quantity += item.quantity || 1;
        } else {
            this.cart.push({
                id: item.id,
                name: item.name,
                price: item.price,
                image: item.image,
                quantity: item.quantity || 1,
                modifiers: item.modifiers || null
            });
        }

        this.saveCart(this.cart);
        this.showToast(`${item.name} added to cart`, 'success');
    },

    removeFromCart(index) {
        this.cart.splice(index, 1);
        this.saveCart(this.cart);
    },

    updateCartQuantity(index, quantity) {
        if (quantity <= 0) {
            this.removeFromCart(index);
        } else {
            this.cart[index].quantity = quantity;
            this.saveCart(this.cart);
        }
    },

    clearCart() {
        this.cart = [];
        localStorage.removeItem('junxtion_cart');
        this.updateCartBadge();
    },

    getCartCount() {
        return this.cart.reduce((sum, item) => sum + item.quantity, 0);
    },

    getCartTotal() {
        return this.cart.reduce((sum, item) => {
            const modifiersCost = item.modifiers
                ? item.modifiers.reduce((mSum, m) => mSum + (m.priceDelta || 0), 0)
                : 0;
            return sum + ((item.price + modifiersCost) * item.quantity);
        }, 0);
    },

    updateCartBadge() {
        const badge = document.getElementById('cart-badge');
        if (badge) {
            const count = this.getCartCount();
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }
    },

    // ========================================
    // Authentication
    // ========================================

    /**
     * Show Sign In Modal - for existing users only
     */
    showSignInModal(onSuccess = null) {
        const content = `
            <div class="modal-header">
                <h3 class="modal-title">Sign In</h3>
                <button class="modal-close" onclick="JunxtionApp.closeModal(this.closest('.modal-overlay'))">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="auth-step-phone">
                <p style="color:var(--gray-600);margin-bottom:20px;">Enter your phone number to sign in</p>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" class="form-input" id="auth-phone" placeholder="0XX XXX XXXX" autocomplete="tel">
                </div>
                <button class="btn btn-primary btn-block" id="auth-send-otp">Continue</button>
            </div>
            <div id="auth-step-otp" style="display:none;">
                <p style="color:var(--gray-600);margin-bottom:20px;">Enter the 6-digit code sent to <span id="auth-phone-display"></span></p>
                <div class="form-group">
                    <label>Verification Code</label>
                    <input type="text" class="form-input otp-input" id="auth-otp" placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code" style="font-size:24px;text-align:center;letter-spacing:8px;">
                </div>
                <button class="btn btn-primary btn-block" id="auth-verify-otp">Verify</button>
                <button class="btn btn-secondary btn-block" id="auth-back" style="margin-top:12px;">Back</button>
            </div>
        `;

        this.showModal(content);
        this._setupAuthHandlers(onSuccess, 'signin');
    },

    /**
     * Show Register Modal - for new users with full details
     */
    showRegisterModal(onSuccess = null) {
        const content = `
            <div class="modal-header">
                <h3 class="modal-title">Create Account</h3>
                <button class="modal-close" onclick="JunxtionApp.closeModal(this.closest('.modal-overlay'))">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="auth-step-phone" style="max-height:70vh;overflow-y:auto;">
                <p style="color:var(--gray-600);margin-bottom:16px;">Enter your details to create an account</p>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label>First Name</label>
                        <input type="text" class="form-input" id="auth-firstname" placeholder="John" autocomplete="given-name">
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label>Surname</label>
                        <input type="text" class="form-input" id="auth-surname" placeholder="Doe" autocomplete="family-name">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:12px;">
                    <label>Phone Number</label>
                    <input type="tel" class="form-input" id="auth-phone" placeholder="0XX XXX XXXX" autocomplete="tel">
                </div>

                <div class="form-group" style="margin-bottom:12px;">
                    <label>Email (Optional)</label>
                    <input type="email" class="form-input" id="auth-email" placeholder="john@example.com" autocomplete="email">
                </div>

                <h4 style="margin:16px 0 12px;font-size:14px;color:var(--gray-700);">Delivery Address</h4>

                <div class="form-group" style="margin-bottom:12px;">
                    <label>Street Address</label>
                    <input type="text" class="form-input" id="auth-street" placeholder="123 Main Road" autocomplete="street-address">
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    <div class="form-group" style="margin-bottom:12px;">
                        <label>Suburb</label>
                        <input type="text" class="form-input" id="auth-suburb" placeholder="Suburb">
                    </div>
                    <div class="form-group" style="margin-bottom:12px;">
                        <label>City</label>
                        <input type="text" class="form-input" id="auth-city" placeholder="City" autocomplete="address-level2">
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:16px;">
                    <label>Postal Code</label>
                    <input type="text" class="form-input" id="auth-postal" placeholder="0000" autocomplete="postal-code" style="width:120px;">
                </div>

                <button class="btn btn-primary btn-block" id="auth-send-otp">Continue</button>
            </div>
            <div id="auth-step-otp" style="display:none;">
                <p style="color:var(--gray-600);margin-bottom:20px;">Enter the 6-digit code sent to <span id="auth-phone-display"></span></p>
                <div class="form-group">
                    <label>Verification Code</label>
                    <input type="text" class="form-input otp-input" id="auth-otp" placeholder="000000" maxlength="6" inputmode="numeric" autocomplete="one-time-code" style="font-size:24px;text-align:center;letter-spacing:8px;">
                </div>
                <button class="btn btn-primary btn-block" id="auth-verify-otp">Verify & Create Account</button>
                <button class="btn btn-secondary btn-block" id="auth-back" style="margin-top:12px;">Back</button>
            </div>
        `;

        this.showModal(content);
        this._setupRegisterHandlers(onSuccess);
    },

    /**
     * Internal: Setup register modal event handlers
     */
    _setupRegisterHandlers(onSuccess) {
        const firstnameInput = document.getElementById('auth-firstname');
        const surnameInput = document.getElementById('auth-surname');
        const phoneInput = document.getElementById('auth-phone');
        const emailInput = document.getElementById('auth-email');
        const streetInput = document.getElementById('auth-street');
        const suburbInput = document.getElementById('auth-suburb');
        const cityInput = document.getElementById('auth-city');
        const postalInput = document.getElementById('auth-postal');
        const otpInput = document.getElementById('auth-otp');

        let registrationData = {};

        // Send OTP step
        document.getElementById('auth-send-otp').onclick = async () => {
            const firstname = firstnameInput.value.trim();
            const surname = surnameInput.value.trim();
            const phone = phoneInput.value.replace(/\s/g, '');
            const email = emailInput.value.trim();
            const street = streetInput.value.trim();
            const suburb = suburbInput.value.trim();
            const city = cityInput.value.trim();
            const postal = postalInput.value.trim();

            // Validation
            if (!firstname || firstname.length < 2) {
                this.showToast('Please enter your first name', 'error');
                firstnameInput.focus();
                return;
            }
            if (!surname || surname.length < 2) {
                this.showToast('Please enter your surname', 'error');
                surnameInput.focus();
                return;
            }
            if (!phone || phone.length < 10) {
                this.showToast('Please enter a valid phone number', 'error');
                phoneInput.focus();
                return;
            }
            if (!street) {
                this.showToast('Please enter your street address', 'error');
                streetInput.focus();
                return;
            }
            if (!city) {
                this.showToast('Please enter your city', 'error');
                cityInput.focus();
                return;
            }

            // Store registration data
            registrationData = {
                firstname,
                surname,
                full_name: `${firstname} ${surname}`,
                phone,
                email: email || null,
                address: {
                    address_line1: street,
                    suburb: suburb || null,
                    city,
                    postal_code: postal || null
                }
            };

            const btn = document.getElementById('auth-send-otp');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner small"></span> Sending...';

            try {
                const response = await fetch('/api/customer/auth/request-otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ phone, mode: 'register' })
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById('auth-step-phone').style.display = 'none';
                    document.getElementById('auth-step-otp').style.display = 'block';
                    document.getElementById('auth-phone-display').textContent = phone;
                    otpInput.focus();

                    if (data.data?.otp_dev) {
                        console.log('DEV OTP:', data.data.otp_dev);
                        this.showToast(`DEV: OTP is ${data.data.otp_dev}`, 'warning');
                    }
                } else {
                    this.showToast(data.error?.message || 'Failed to send code', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Continue';
                }
            } catch (e) {
                this.showToast('Connection error', 'error');
                btn.disabled = false;
                btn.textContent = 'Continue';
            }
        };

        // OTP verification
        document.getElementById('auth-verify-otp').onclick = async () => {
            const otp = otpInput.value.trim();
            if (!otp || otp.length !== 6) {
                this.showToast('Please enter the 6-digit code', 'error');
                return;
            }

            const btn = document.getElementById('auth-verify-otp');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner small"></span> Creating account...';

            try {
                const response = await fetch('/api/customer/auth/verify-otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        phone: registrationData.phone,
                        code: otp,
                        mode: 'register',
                        full_name: registrationData.full_name,
                        email: registrationData.email,
                        address: registrationData.address
                    })
                });
                const data = await response.json();

                if (data.success) {
                    this.token = data.data.token;
                    this.user = data.data.user;
                    localStorage.setItem('junxtion_token', this.token);
                    localStorage.setItem('junxtion_user', JSON.stringify(this.user));

                    this.closeModal(document.querySelector('.modal-overlay'));
                    this.showToast('Account created successfully!', 'success');

                    if (onSuccess) onSuccess();
                } else {
                    this.showToast(data.error?.message || 'Failed to create account', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Verify & Create Account';
                }
            } catch (e) {
                this.showToast('Connection error', 'error');
                btn.disabled = false;
                btn.textContent = 'Verify & Create Account';
            }
        };

        // Back button
        document.getElementById('auth-back').onclick = () => {
            document.getElementById('auth-step-otp').style.display = 'none';
            document.getElementById('auth-step-phone').style.display = 'block';
            document.getElementById('auth-send-otp').disabled = false;
            document.getElementById('auth-send-otp').textContent = 'Continue';
        };

        // Auto-focus
        firstnameInput.focus();

        // Enter key handling
        postalInput.onkeypress = (e) => {
            if (e.key === 'Enter') document.getElementById('auth-send-otp').click();
        };
        otpInput.onkeypress = (e) => {
            if (e.key === 'Enter') document.getElementById('auth-verify-otp').click();
        };
    },

    /**
     * Legacy method - shows combined auth (defaults to sign in)
     */
    showAuthModal(onSuccess = null) {
        this.showSignInModal(onSuccess);
    },

    /**
     * Internal: Setup auth modal event handlers
     */
    _setupAuthHandlers(onSuccess, mode) {
        const phoneInput = document.getElementById('auth-phone');
        const otpInput = document.getElementById('auth-otp');
        const nameInput = document.getElementById('auth-name');
        let phone = '';
        let name = '';

        // Phone step
        document.getElementById('auth-send-otp').onclick = async () => {
            phone = phoneInput.value.replace(/\s/g, '');
            if (!phone || phone.length < 10) {
                this.showToast('Please enter a valid phone number', 'error');
                return;
            }

            // For register mode, validate name
            if (mode === 'register') {
                name = nameInput ? nameInput.value.trim() : '';
                if (!name || name.length < 2) {
                    this.showToast('Please enter your name', 'error');
                    return;
                }
            }

            const btn = document.getElementById('auth-send-otp');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner small"></span> Sending...';

            try {
                const response = await fetch('/api/customer/auth/request-otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ phone, mode })
                });
                const data = await response.json();

                if (data.success) {
                    document.getElementById('auth-step-phone').style.display = 'none';
                    document.getElementById('auth-step-otp').style.display = 'block';
                    document.getElementById('auth-phone-display').textContent = phone;
                    otpInput.focus();

                    // DEV MODE: Show OTP if returned
                    if (data.data?.otp_dev) {
                        console.log('DEV OTP:', data.data.otp_dev);
                        this.showToast(`DEV: OTP is ${data.data.otp_dev}`, 'warning');
                    }
                } else {
                    this.showToast(data.error?.message || 'Failed to send code', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Continue';
                }
            } catch (e) {
                this.showToast('Connection error', 'error');
                btn.disabled = false;
                btn.textContent = 'Continue';
            }
        };

        // OTP verification
        document.getElementById('auth-verify-otp').onclick = async () => {
            const otp = otpInput.value.trim();
            if (!otp || otp.length !== 6) {
                this.showToast('Please enter the 6-digit code', 'error');
                return;
            }

            const btn = document.getElementById('auth-verify-otp');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner small"></span> Verifying...';

            try {
                const payload = { phone, code: otp, mode };
                if (mode === 'register' && name) {
                    payload.name = name;
                }

                const response = await fetch('/api/customer/auth/verify-otp', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await response.json();

                if (data.success) {
                    this.token = data.data.token;
                    this.user = data.data.user;
                    localStorage.setItem('junxtion_token', this.token);
                    localStorage.setItem('junxtion_user', JSON.stringify(this.user));

                    this.closeModal(document.querySelector('.modal-overlay'));
                    this.showToast(mode === 'register' ? 'Account created!' : 'Welcome back!', 'success');

                    if (onSuccess) onSuccess();
                } else {
                    this.showToast(data.error?.message || 'Invalid code', 'error');
                    btn.disabled = false;
                    btn.textContent = 'Verify';
                }
            } catch (e) {
                this.showToast('Connection error', 'error');
                btn.disabled = false;
                btn.textContent = 'Verify';
            }
        };

        // Back button
        document.getElementById('auth-back').onclick = () => {
            document.getElementById('auth-step-otp').style.display = 'none';
            document.getElementById('auth-step-phone').style.display = 'block';
            document.getElementById('auth-send-otp').disabled = false;
            document.getElementById('auth-send-otp').textContent = 'Continue';
        };

        // Auto-focus
        if (mode === 'register' && nameInput) {
            nameInput.focus();
        } else {
            phoneInput.focus();
        }

        // Enter key handling
        if (nameInput) {
            nameInput.onkeypress = (e) => {
                if (e.key === 'Enter') phoneInput.focus();
            };
        }
        phoneInput.onkeypress = (e) => {
            if (e.key === 'Enter') document.getElementById('auth-send-otp').click();
        };
        otpInput.onkeypress = (e) => {
            if (e.key === 'Enter') document.getElementById('auth-verify-otp').click();
        };
    },

    logout() {
        this.token = null;
        this.user = null;
        localStorage.removeItem('junxtion_token');
        localStorage.removeItem('junxtion_user');
    },

    isAuthenticated() {
        return !!this.token && !!this.user;
    },

    // ========================================
    // UI Helpers
    // ========================================
    showToast(message, type = 'info') {
        // Remove existing toast
        const existingToast = document.querySelector('.app-toast');
        if (existingToast) existingToast.remove();

        const toast = document.createElement('div');
        toast.className = `app-toast toast-${type}`;
        toast.innerHTML = message;

        document.body.appendChild(toast);

        // Trigger animation
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Auto-hide
        setTimeout(() => {
            toast.classList.remove('show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    },

    showModal(content) {
        // Remove existing modal
        const existingModal = document.querySelector('.modal-overlay');
        if (existingModal) existingModal.remove();

        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `<div class="modal">${content}</div>`;

        // Close on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeModal(modal);
            }
        });

        document.body.appendChild(modal);
        document.body.style.overflow = 'hidden';

        // Animate in
        requestAnimationFrame(() => {
            modal.classList.add('show');
        });

        return modal;
    },

    closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
        document.body.style.overflow = '';
        setTimeout(() => modal.remove(), 300);
    },

    formatPrice(cents) {
        return 'R' + (cents / 100).toFixed(2);
    }
};

// Auto-initialize
JunxtionApp.init();

// Expose globally
window.JunxtionApp = JunxtionApp;
