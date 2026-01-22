<?php
/**
 * Customer Cart & Checkout Page
 */
$configPath = __DIR__ . '/../private/config.php';
$config = file_exists($configPath) ? require $configPath : [];
$appName = $config['app']['name'] ?? 'Junxtion';
$baseUrl = $config['app']['base_url'] ?? 'https://junxtionapp.co.za';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#FF6B35">
    <title>Cart - <?= htmlspecialchars($appName) ?></title>
    <link rel="manifest" href="/pwa/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="page-header">
            <button class="back-btn" onclick="history.back()">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M19 12H5M12 19l-7-7 7-7"/>
                </svg>
            </button>
            <h1 class="page-title">Your Cart</h1>
            <button class="clear-btn" id="clear-cart-btn" onclick="clearCart()" style="display:none;">Clear</button>
        </header>

        <!-- Cart Content -->
        <div class="page" id="cart-page">
            <div class="cart-items" id="cart-items">
                <!-- Items loaded via JS -->
            </div>

            <!-- Promo Code -->
            <div class="promo-section" id="promo-section" style="display:none;">
                <div class="promo-input-row">
                    <input type="text" id="promo-input" class="form-input" placeholder="Promo code">
                    <button class="btn btn-secondary" onclick="applyPromo()">Apply</button>
                </div>
                <div class="promo-applied" id="promo-applied" style="display:none;">
                    <span id="promo-code-text"></span>
                    <button class="promo-remove" onclick="removePromo()">Remove</button>
                </div>
            </div>

            <!-- Order Type Selection -->
            <div class="order-type-section" id="order-type-section" style="display:none;">
                <h3>Order Type</h3>
                <div class="order-type-options">
                    <label class="order-type-option">
                        <input type="radio" name="order_type" value="pickup" checked>
                        <div class="option-content">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            </svg>
                            <span>Pickup</span>
                        </div>
                    </label>
                    <label class="order-type-option">
                        <input type="radio" name="order_type" value="delivery">
                        <div class="option-content">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/>
                                <circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>
                            </svg>
                            <span>Delivery</span>
                        </div>
                    </label>
                </div>

                <!-- Delivery Address -->
                <div class="delivery-address" id="delivery-address" style="display:none;">
                    <label>Delivery Address</label>
                    <textarea id="address-input" class="form-input" placeholder="Enter your full delivery address" rows="2"></textarea>
                </div>
            </div>

            <!-- Notes -->
            <div class="notes-section" id="notes-section" style="display:none;">
                <label>Special Instructions (optional)</label>
                <textarea id="notes-input" class="form-input" placeholder="Any special requests?" rows="2"></textarea>
            </div>

            <!-- Order Summary -->
            <div class="order-summary" id="order-summary" style="display:none;">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal">R0.00</span>
                </div>
                <div class="summary-row" id="delivery-fee-row" style="display:none;">
                    <span>Delivery Fee</span>
                    <span id="delivery-fee">R0.00</span>
                </div>
                <div class="summary-row discount-row" id="discount-row" style="display:none;">
                    <span>Discount</span>
                    <span id="discount">-R0.00</span>
                </div>
                <div class="summary-row total-row">
                    <span>Total</span>
                    <span id="total">R0.00</span>
                </div>
            </div>

            <!-- Empty Cart -->
            <div class="empty-cart" id="empty-cart">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                    <circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
                <h3>Your cart is empty</h3>
                <p>Add some delicious items to get started!</p>
                <a href="/app/menu.php" class="btn btn-primary">Browse Menu</a>
            </div>
        </div>
    </div>

    <!-- Checkout Button (Fixed) -->
    <div class="checkout-bar" id="checkout-bar" style="display:none;">
        <button class="btn btn-primary btn-block btn-lg" id="checkout-btn" onclick="proceedToCheckout()">
            Proceed to Payment - <span id="checkout-total">R0.00</span>
        </button>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav" id="bottom-nav">
        <a href="/app/home.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <span>Home</span>
        </a>
        <a href="/app/menu.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Menu</span>
        </a>
        <a href="/app/cart.php" class="nav-item active">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            <span>Cart</span>
            <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
        </a>
        <a href="/app/orders.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <span>Orders</span>
        </a>
        <a href="/app/profile.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span>Profile</span>
        </a>
    </nav>

    <script>
        window.APP_CONFIG = {
            baseUrl: '<?= $baseUrl ?>',
            apiUrl: '<?= $baseUrl ?>/api'
        };
    </script>
    <script src="/assets/js/app.js"></script>
    <script>
        let deliveryFee = 0;
        let discountAmount = 0;
        let appliedPromo = null;

        document.addEventListener('DOMContentLoaded', () => {
            renderCart();

            // Order type change
            document.querySelectorAll('input[name="order_type"]').forEach(radio => {
                radio.addEventListener('change', (e) => {
                    const isDelivery = e.target.value === 'delivery';
                    document.getElementById('delivery-address').style.display = isDelivery ? 'block' : 'none';
                    document.getElementById('delivery-fee-row').style.display = isDelivery ? 'flex' : 'none';
                    deliveryFee = isDelivery ? 2500 : 0; // R25 delivery fee
                    updateTotals();
                });
            });

            // Check for saved promo
            const savedPromo = localStorage.getItem('junxtion_promo');
            if (savedPromo) {
                document.getElementById('promo-input').value = savedPromo;
            }
        });

        function renderCart() {
            const cart = JunxtionApp.getCart();
            const itemsContainer = document.getElementById('cart-items');
            const emptyCart = document.getElementById('empty-cart');
            const checkoutBar = document.getElementById('checkout-bar');
            const clearBtn = document.getElementById('clear-cart-btn');
            const orderTypeSection = document.getElementById('order-type-section');
            const promoSection = document.getElementById('promo-section');
            const notesSection = document.getElementById('notes-section');
            const orderSummary = document.getElementById('order-summary');
            const bottomNav = document.getElementById('bottom-nav');

            if (cart.length === 0) {
                emptyCart.style.display = 'flex';
                itemsContainer.style.display = 'none';
                checkoutBar.style.display = 'none';
                clearBtn.style.display = 'none';
                orderTypeSection.style.display = 'none';
                promoSection.style.display = 'none';
                notesSection.style.display = 'none';
                orderSummary.style.display = 'none';
                bottomNav.style.display = 'flex';
                return;
            }

            emptyCart.style.display = 'none';
            itemsContainer.style.display = 'block';
            checkoutBar.style.display = 'block';
            clearBtn.style.display = 'block';
            orderTypeSection.style.display = 'block';
            promoSection.style.display = 'block';
            notesSection.style.display = 'block';
            orderSummary.style.display = 'block';
            bottomNav.style.display = 'none';

            itemsContainer.innerHTML = cart.map((item, index) => {
                const itemTotal = (item.price + (item.modifiers?.reduce((sum, m) => sum + m.priceDelta, 0) || 0)) * item.quantity;
                return `
                    <div class="cart-item">
                        <img src="${item.image || '/assets/images/placeholder.svg'}" alt="${item.name}" class="cart-item-image">
                        <div class="cart-item-content">
                            <div class="cart-item-name">${item.name}</div>
                            ${item.modifiers ? `<div class="cart-item-mods">${item.modifiers.map(m => m.name).join(', ')}</div>` : ''}
                            <div class="cart-item-price">R${(itemTotal / 100).toFixed(2)}</div>
                        </div>
                        <div class="cart-item-actions">
                            <div class="quantity-control small">
                                <button class="quantity-btn" onclick="updateQuantity(${index}, -1)">-</button>
                                <span class="quantity-value">${item.quantity}</span>
                                <button class="quantity-btn" onclick="updateQuantity(${index}, 1)">+</button>
                            </div>
                            <button class="remove-btn" onclick="removeItem(${index})">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
            }).join('');

            updateTotals();
            updateCartBadge();
        }

        function updateQuantity(index, delta) {
            const cart = JunxtionApp.getCart();
            cart[index].quantity = Math.max(1, cart[index].quantity + delta);
            JunxtionApp.saveCart(cart);
            renderCart();
        }

        function removeItem(index) {
            const cart = JunxtionApp.getCart();
            cart.splice(index, 1);
            JunxtionApp.saveCart(cart);
            renderCart();
        }

        function clearCart() {
            if (confirm('Clear all items from your cart?')) {
                JunxtionApp.saveCart([]);
                renderCart();
            }
        }

        function updateTotals() {
            const subtotal = JunxtionApp.getCartTotal();
            const total = subtotal + deliveryFee - discountAmount;

            document.getElementById('subtotal').textContent = 'R' + (subtotal / 100).toFixed(2);
            document.getElementById('delivery-fee').textContent = 'R' + (deliveryFee / 100).toFixed(2);
            document.getElementById('discount').textContent = '-R' + (discountAmount / 100).toFixed(2);
            document.getElementById('total').textContent = 'R' + (total / 100).toFixed(2);
            document.getElementById('checkout-total').textContent = 'R' + (total / 100).toFixed(2);
        }

        function updateCartBadge() {
            const count = JunxtionApp.getCartCount();
            document.getElementById('cart-badge').textContent = count;
            document.getElementById('cart-badge').style.display = count > 0 ? 'flex' : 'none';
        }

        async function applyPromo() {
            const code = document.getElementById('promo-input').value.trim().toUpperCase();
            if (!code) return;

            try {
                const response = await fetch('/api/promo/validate', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        code: code,
                        subtotal_cents: JunxtionApp.getCartTotal()
                    })
                });
                const data = await response.json();

                if (data.success) {
                    appliedPromo = data.data;
                    discountAmount = data.data.discount_cents;
                    document.getElementById('promo-applied').style.display = 'flex';
                    document.getElementById('promo-code-text').textContent = `${code} - R${(discountAmount / 100).toFixed(2)} off`;
                    document.getElementById('discount-row').style.display = 'flex';
                    document.querySelector('.promo-input-row').style.display = 'none';
                    localStorage.removeItem('junxtion_promo');
                    updateTotals();
                    JunxtionApp.showToast('Promo code applied!', 'success');
                } else {
                    JunxtionApp.showToast(data.error?.message || 'Invalid promo code', 'error');
                }
            } catch (e) {
                JunxtionApp.showToast('Error applying promo code', 'error');
            }
        }

        function removePromo() {
            appliedPromo = null;
            discountAmount = 0;
            document.getElementById('promo-applied').style.display = 'none';
            document.getElementById('discount-row').style.display = 'none';
            document.querySelector('.promo-input-row').style.display = 'flex';
            document.getElementById('promo-input').value = '';
            updateTotals();
        }

        async function proceedToCheckout() {
            const btn = document.getElementById('checkout-btn');
            btn.disabled = true;
            btn.innerHTML = '<span class="loading-spinner"></span> Processing...';

            // Check if user is logged in
            if (!JunxtionApp.user) {
                JunxtionApp.showAuthModal(() => {
                    btn.disabled = false;
                    btn.innerHTML = 'Proceed to Payment - <span id="checkout-total">R' + ((JunxtionApp.getCartTotal() + deliveryFee - discountAmount) / 100).toFixed(2) + '</span>';
                    proceedToCheckout();
                });
                return;
            }

            const orderType = document.querySelector('input[name="order_type"]:checked').value;
            const address = document.getElementById('address-input').value;
            const notes = document.getElementById('notes-input').value;

            if (orderType === 'delivery' && !address.trim()) {
                JunxtionApp.showToast('Please enter your delivery address', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Proceed to Payment - <span id="checkout-total">R' + ((JunxtionApp.getCartTotal() + deliveryFee - discountAmount) / 100).toFixed(2) + '</span>';
                return;
            }

            const cart = JunxtionApp.getCart();
            const orderData = {
                order_type: orderType,
                items: cart.map(item => ({
                    item_id: item.id,
                    qty: item.quantity,
                    modifiers: item.modifiers?.map(m => m.id) || []
                })),
                notes: notes || null,
                delivery_address: orderType === 'delivery' ? address : null,
                promo_code: appliedPromo?.code || null
            };

            try {
                const response = await fetch('/api/orders', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': `Bearer ${JunxtionApp.token}`
                    },
                    body: JSON.stringify(orderData)
                });

                const data = await response.json();

                if (data.success) {
                    // Redirect to Yoco checkout
                    if (data.data.checkout_url) {
                        JunxtionApp.saveCart([]);
                        window.location.href = data.data.checkout_url;
                    } else {
                        // Order placed without payment (e.g., cash on delivery)
                        JunxtionApp.saveCart([]);
                        window.location.href = '/app/orders.php?id=' + data.data.order_id;
                    }
                } else {
                    JunxtionApp.showToast(data.error?.message || 'Failed to place order', 'error');
                    btn.disabled = false;
                    btn.innerHTML = 'Proceed to Payment - <span id="checkout-total">R' + ((JunxtionApp.getCartTotal() + deliveryFee - discountAmount) / 100).toFixed(2) + '</span>';
                }
            } catch (e) {
                JunxtionApp.showToast('Connection error. Please try again.', 'error');
                btn.disabled = false;
                btn.innerHTML = 'Proceed to Payment - <span id="checkout-total">R' + ((JunxtionApp.getCartTotal() + deliveryFee - discountAmount) / 100).toFixed(2) + '</span>';
            }
        }
    </script>

    <style>
        .back-btn, .clear-btn {
            background: none;
            border: none;
            cursor: pointer;
        }
        .clear-btn {
            color: var(--danger);
            font-weight: 600;
        }
        .cart-item {
            display: flex;
            gap: 12px;
            padding: 16px;
            background: white;
            border-radius: 12px;
            margin-bottom: 12px;
        }
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            background: var(--gray-100);
        }
        .cart-item-content {
            flex: 1;
        }
        .cart-item-name {
            font-weight: 600;
            margin-bottom: 4px;
        }
        .cart-item-mods {
            font-size: 12px;
            color: var(--gray-500);
            margin-bottom: 8px;
        }
        .cart-item-price {
            font-weight: 600;
            color: var(--primary);
        }
        .cart-item-actions {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 8px;
        }
        .quantity-control.small .quantity-btn {
            width: 28px;
            height: 28px;
            font-size: 14px;
        }
        .quantity-control.small .quantity-value {
            width: 28px;
            font-size: 14px;
        }
        .remove-btn {
            background: none;
            border: none;
            color: var(--danger);
            padding: 4px;
            cursor: pointer;
        }
        .promo-section, .order-type-section, .notes-section {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 12px;
        }
        .promo-input-row {
            display: flex;
            gap: 8px;
        }
        .promo-input-row input {
            flex: 1;
        }
        .promo-applied {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--success-bg);
            padding: 12px;
            border-radius: 8px;
            color: var(--success);
        }
        .promo-remove {
            background: none;
            border: none;
            color: var(--danger);
            cursor: pointer;
            font-weight: 600;
        }
        .order-type-section h3, .notes-section label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            display: block;
        }
        .order-type-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        .order-type-option {
            cursor: pointer;
        }
        .order-type-option input {
            display: none;
        }
        .order-type-option .option-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px;
            border: 2px solid var(--gray-200);
            border-radius: 12px;
            transition: all 0.2s;
        }
        .order-type-option input:checked + .option-content {
            border-color: var(--primary);
            background: var(--primary-light);
        }
        .delivery-address {
            margin-top: 16px;
        }
        .delivery-address label {
            display: block;
            font-size: 13px;
            color: var(--gray-600);
            margin-bottom: 8px;
        }
        .order-summary {
            background: white;
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 100px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            color: var(--gray-600);
        }
        .summary-row.total-row {
            border-top: 1px solid var(--gray-200);
            margin-top: 8px;
            padding-top: 16px;
            font-size: 18px;
            font-weight: 700;
            color: var(--gray-900);
        }
        .discount-row {
            color: var(--success);
        }
        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 60px 20px;
            color: var(--gray-500);
        }
        .empty-cart svg {
            margin-bottom: 16px;
            color: var(--gray-300);
        }
        .empty-cart h3 {
            color: var(--gray-700);
            margin-bottom: 8px;
        }
        .empty-cart .btn {
            margin-top: 20px;
        }
        .checkout-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 16px;
            background: white;
            box-shadow: 0 -4px 20px rgba(0,0,0,0.1);
            z-index: 100;
        }
    </style>
</body>
</html>
