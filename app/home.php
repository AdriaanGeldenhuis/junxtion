<?php
/**
 * Junxtion Restaurant - Home Page
 * Main app home with specials, categories, and popular items
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#C8102E">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <title><?= htmlspecialchars($appName) ?></title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/pwa/manifest.json">

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/assets/images/icon.svg">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="home-header">
            <div class="header-top">
                <div class="greeting">
                    <h1 id="greeting-text">Good evening!</h1>
                    <p class="subtitle" id="user-name">Welcome to <?= htmlspecialchars($appName) ?></p>
                </div>
                <button class="icon-btn" onclick="location.href='/app/profile.php'" aria-label="Profile">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </button>
            </div>
            <div class="search-bar" onclick="location.href='/app/menu.php?search=1'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <span>Search our menu...</span>
            </div>
        </header>

        <!-- Specials Carousel -->
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Today's Specials</h2>
                    <p class="section-subtitle">Limited time offers</p>
                </div>
            </div>
            <div class="specials-carousel" id="specials-carousel">
                <div class="skeleton skeleton-card" style="width: 320px; flex-shrink: 0;"></div>
                <div class="skeleton skeleton-card" style="width: 320px; flex-shrink: 0;"></div>
            </div>
        </section>

        <!-- Categories -->
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Categories</h2>
                    <p class="section-subtitle">Browse by category</p>
                </div>
                <a href="/app/menu.php" class="link-text">
                    View All
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            </div>
            <div class="categories-grid" id="categories-grid">
                <div class="skeleton skeleton-category"></div>
                <div class="skeleton skeleton-category"></div>
                <div class="skeleton skeleton-category"></div>
                <div class="skeleton skeleton-category"></div>
                <div class="skeleton skeleton-category"></div>
                <div class="skeleton skeleton-category"></div>
            </div>
        </section>

        <!-- Popular Items -->
        <section class="section">
            <div class="section-header">
                <div>
                    <h2 class="section-title">Most Popular</h2>
                    <p class="section-subtitle">Customer favorites</p>
                </div>
                <a href="/app/menu.php" class="link-text">
                    See All
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </a>
            </div>
            <div class="menu-grid" id="popular-items">
                <div class="skeleton skeleton-item"></div>
                <div class="skeleton skeleton-item"></div>
                <div class="skeleton skeleton-item"></div>
                <div class="skeleton skeleton-item"></div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="section" style="padding-bottom: 100px;">
            <div class="quick-actions">
                <a href="/app/menu.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </div>
                    <span>Full Menu</span>
                </a>
                <a href="/app/orders.php" class="quick-action">
                    <div class="quick-action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                        </svg>
                    </div>
                    <span>My Orders</span>
                </a>
                <a href="tel:+27000000000" class="quick-action">
                    <div class="quick-action-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72"/>
                        </svg>
                    </div>
                    <span>Call Us</span>
                </a>
            </div>
        </section>
    </div>

    <!-- Sticky Cart Button -->
    <div class="sticky-cart" id="sticky-cart" style="display:none;" onclick="location.href='/app/cart.php'">
        <div class="sticky-cart-info">
            <span class="sticky-cart-count" id="cart-count">0</span>
            <span>View Cart</span>
        </div>
        <span class="sticky-cart-total" id="cart-total">R0.00</span>
    </div>

    <!-- Bottom Navigation -->
    <nav class="bottom-nav">
        <a href="/app/home.php" class="nav-item active">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
            <span>Home</span>
        </a>
        <a href="/app/menu.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <span>Menu</span>
        </a>
        <a href="/app/cart.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
            <span>Cart</span>
            <span class="cart-badge" id="cart-badge" style="display:none;">0</span>
        </a>
        <a href="/app/orders.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/>
            </svg>
            <span>Orders</span>
        </a>
        <a href="/app/profile.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                <circle cx="12" cy="7" r="4"/>
            </svg>
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
        // Category icons mapping
        const categoryIcons = {
            'starters': '<path d="M12 2L2 7l10 5 10-5-10-5z"/><path d="M2 17l10 5 10-5"/><path d="M2 12l10 5 10-5"/>',
            'mains': '<circle cx="12" cy="12" r="10"/><path d="M8 14s1.5 2 4 2 4-2 4-2"/><line x1="9" y1="9" x2="9.01" y2="9"/><line x1="15" y1="9" x2="15.01" y2="9"/>',
            'desserts': '<path d="M12 2a10 10 0 1 0 10 10 4 4 0 0 1-5-5 4 4 0 0 1-5-5"/>',
            'drinks': '<path d="M17 8h1a4 4 0 1 1 0 8h-1"/><path d="M3 8h14v9a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4Z"/><line x1="6" y1="2" x2="6" y2="4"/><line x1="10" y1="2" x2="10" y2="4"/><line x1="14" y1="2" x2="14" y2="4"/>',
            'default': '<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>'
        };

        document.addEventListener('DOMContentLoaded', async () => {
            // Update greeting based on time
            updateGreeting();

            // Load data
            await Promise.all([
                loadSpecials(),
                loadMenu()
            ]);

            // Update cart UI
            updateCartUI();
        });

        function updateGreeting() {
            const hour = new Date().getHours();
            let greeting = 'Good evening';
            if (hour < 12) greeting = 'Good morning';
            else if (hour < 17) greeting = 'Good afternoon';

            const greetingEl = document.getElementById('greeting-text');
            const userNameEl = document.getElementById('user-name');

            greetingEl.textContent = greeting + '!';

            if (JunxtionApp.user) {
                userNameEl.textContent = JunxtionApp.user.name || JunxtionApp.user.phone || 'Welcome back';
            } else {
                userNameEl.textContent = 'Welcome to <?= htmlspecialchars($appName) ?>';
            }
        }

        async function loadSpecials() {
            try {
                const response = await fetch('/api/menu/specials');
                const data = await response.json();

                const carousel = document.getElementById('specials-carousel');

                if (data.success && data.data.length > 0) {
                    carousel.innerHTML = data.data.map(special => `
                        <div class="special-card" onclick="handleSpecialClick('${special.promo_code || ''}')">
                            <img src="${special.image_path || '/assets/images/special-placeholder.jpg'}"
                                 alt="${special.title}"
                                 onerror="this.src='/assets/images/special-placeholder.jpg'">
                            <div class="special-content">
                                ${special.discount_type && special.discount_type !== 'none' ?
                                    `<span class="special-badge">${special.discount_type === 'percentage' ?
                                        special.discount_value + '% OFF' :
                                        'R' + (special.discount_value / 100).toFixed(0) + ' OFF'}</span>` : ''}
                                <div class="special-title">${special.title}</div>
                                ${special.body ? `<div class="special-description">${special.body}</div>` : ''}
                                ${special.promo_code ? `<div class="special-code">Code: ${special.promo_code}</div>` : ''}
                            </div>
                        </div>
                    `).join('');
                } else {
                    // Default welcome card
                    carousel.innerHTML = `
                        <div class="special-card" onclick="location.href='/app/menu.php'" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
                            <div class="special-content" style="position: relative; padding-top: 100px;">
                                <span class="special-badge">NEW</span>
                                <div class="special-title">Welcome to <?= htmlspecialchars($appName) ?>!</div>
                                <div class="special-description">Explore our delicious menu and place your first order today.</div>
                            </div>
                        </div>
                    `;
                }
            } catch (e) {
                console.error('Error loading specials:', e);
            }
        }

        async function loadMenu() {
            try {
                const response = await fetch('/api/menu');
                const data = await response.json();

                if (data.success) {
                    renderCategories(data.data.categories || []);
                    renderPopularItems(data.data.items || []);
                }
            } catch (e) {
                console.error('Error loading menu:', e);
                document.getElementById('categories-grid').innerHTML =
                    '<div class="text-center text-muted" style="grid-column: 1/-1; padding: 2rem;">Failed to load menu</div>';
            }
        }

        function renderCategories(categories) {
            const grid = document.getElementById('categories-grid');

            if (categories.length === 0) {
                grid.innerHTML = '<div class="text-center text-muted" style="grid-column: 1/-1;">No categories available</div>';
                return;
            }

            grid.innerHTML = categories.slice(0, 6).map(cat => {
                const iconKey = cat.name.toLowerCase();
                const iconPath = categoryIcons[iconKey] || categoryIcons.default;

                return `
                    <a href="/app/menu.php?category=${cat.id}" class="category-card">
                        <div class="category-icon">
                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                ${iconPath}
                            </svg>
                        </div>
                        <span class="category-name">${cat.name}</span>
                    </a>
                `;
            }).join('');
        }

        function renderPopularItems(items) {
            const grid = document.getElementById('popular-items');
            const availableItems = items.filter(i => i.available !== false);

            if (availableItems.length === 0) {
                grid.innerHTML = '<div class="empty-state" style="grid-column: 1/-1;"><p>No items available</p></div>';
                return;
            }

            // Get featured items first, then fill with others
            const featured = availableItems.filter(i => i.featured);
            const others = availableItems.filter(i => !i.featured);
            const displayItems = [...featured, ...others].slice(0, 4);

            grid.innerHTML = displayItems.map(item => `
                <div class="menu-item" onclick="showItemModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                    <img src="${item.image_path || '/assets/images/food-placeholder.jpg'}"
                         alt="${item.name}"
                         class="menu-item-image"
                         onerror="this.src='/assets/images/food-placeholder.jpg'">
                    <div class="menu-item-content">
                        <div class="menu-item-name">${item.name}</div>
                        ${item.description ? `<div class="menu-item-description">${item.description}</div>` : ''}
                        <div class="menu-item-footer">
                            <div class="menu-item-price">R${(item.price_cents / 100).toFixed(2)}</div>
                            <button class="menu-item-add" onclick="event.stopPropagation(); quickAddToCart(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        function handleSpecialClick(promoCode) {
            if (promoCode) {
                localStorage.setItem('junxtion_promo', promoCode);
                JunxtionApp.showToast(`Promo code "${promoCode}" saved!`, 'success');
            }
            location.href = '/app/menu.php';
        }

        function quickAddToCart(item) {
            JunxtionApp.addToCart({
                id: item.id,
                name: item.name,
                price: item.price_cents,
                image: item.image_path,
                quantity: 1
            });
            updateCartUI();
        }

        function showItemModal(item) {
            const hasModifiers = item.modifier_groups && item.modifier_groups.length > 0;

            const content = `
                <div class="modal-handle"></div>
                <div class="modal-header">
                    <h3 class="modal-title">${item.name}</h3>
                    <button class="modal-close" onclick="JunxtionApp.closeModal(this.closest('.modal-overlay'))">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <img src="${item.image_path || '/assets/images/food-placeholder.jpg'}"
                     alt="${item.name}"
                     style="width:100%;height:220px;object-fit:cover;border-radius:var(--radius-xl);margin-bottom:var(--space-4);"
                     onerror="this.src='/assets/images/food-placeholder.jpg'">
                <p style="color:var(--gray-600);margin-bottom:var(--space-4);line-height:1.6;">${item.description || 'A delicious item from our menu.'}</p>

                ${hasModifiers ? renderModifierGroups(item.modifier_groups) : ''}

                <div style="display:flex;justify-content:space-between;align-items:center;margin:var(--space-5) 0;padding-top:var(--space-4);border-top:1px solid var(--gray-200);">
                    <div>
                        <div style="font-size:var(--font-size-sm);color:var(--gray-500);margin-bottom:2px;">Price</div>
                        <span style="font-size:var(--font-size-2xl);font-weight:700;color:var(--primary);" id="modal-price">R${(item.price_cents / 100).toFixed(2)}</span>
                    </div>
                    <div class="quantity-control">
                        <button class="quantity-btn" onclick="updateModalQty(-1)">-</button>
                        <span class="quantity-value" id="modal-qty">1</span>
                        <button class="quantity-btn" onclick="updateModalQty(1)">+</button>
                    </div>
                </div>
                <button class="btn btn-primary btn-block btn-lg" onclick="addItemToCart(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                    Add to Cart
                </button>
            `;

            window.currentItemPrice = item.price_cents;
            window.modalQty = 1;
            JunxtionApp.showModal(content);

            // Listen for modifier changes
            if (hasModifiers) {
                document.querySelectorAll('#modifier-groups input').forEach(input => {
                    input.addEventListener('change', updateModalPrice);
                });
            }
        }

        function renderModifierGroups(groups) {
            if (!groups || groups.length === 0) return '';

            return `
                <div id="modifier-groups" style="margin-bottom:var(--space-4);">
                    ${groups.map(group => `
                        <div class="modifier-group" style="margin-bottom:var(--space-4);">
                            <h4 style="font-size:var(--font-size-sm);font-weight:600;margin-bottom:var(--space-3);display:flex;align-items:center;gap:var(--space-2);">
                                ${group.name}
                                ${group.required ? '<span style="font-size:11px;color:var(--danger);font-weight:500;">Required</span>' : ''}
                            </h4>
                            <div class="modifier-options" style="display:flex;flex-direction:column;gap:var(--space-2);">
                                ${(group.modifiers || []).map(mod => `
                                    <label style="display:flex;align-items:center;padding:var(--space-3);background:var(--gray-50);border-radius:var(--radius-lg);cursor:pointer;">
                                        <input type="${group.max_selections === 1 ? 'radio' : 'checkbox'}"
                                               name="mod_${group.id}"
                                               value="${mod.id}"
                                               data-name="${mod.name}"
                                               data-price="${mod.price_delta_cents || 0}"
                                               style="margin-right:var(--space-3);accent-color:var(--primary);">
                                        <span style="flex:1;">${mod.name}</span>
                                        ${mod.price_delta_cents ? `<span style="color:var(--gray-500);font-size:var(--font-size-sm);">+R${(mod.price_delta_cents / 100).toFixed(2)}</span>` : ''}
                                    </label>
                                `).join('')}
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }

        function updateModalQty(delta) {
            window.modalQty = Math.max(1, (window.modalQty || 1) + delta);
            document.getElementById('modal-qty').textContent = window.modalQty;
            updateModalPrice();
        }

        function updateModalPrice() {
            let total = window.currentItemPrice || 0;

            // Add modifier prices
            document.querySelectorAll('#modifier-groups input:checked').forEach(input => {
                total += parseInt(input.dataset.price) || 0;
            });

            total *= window.modalQty || 1;
            document.getElementById('modal-price').textContent = 'R' + (total / 100).toFixed(2);
        }

        function addItemToCart(item) {
            // Get selected modifiers
            const modifiers = [];
            document.querySelectorAll('#modifier-groups input:checked').forEach(input => {
                modifiers.push({
                    id: input.value,
                    name: input.dataset.name,
                    priceDelta: parseInt(input.dataset.price) || 0
                });
            });

            JunxtionApp.addToCart({
                id: item.id,
                name: item.name,
                price: item.price_cents,
                image: item.image_path,
                quantity: window.modalQty || 1,
                modifiers: modifiers.length > 0 ? modifiers : null
            });

            JunxtionApp.closeModal(document.querySelector('.modal-overlay'));
            updateCartUI();
            window.modalQty = 1;
        }

        function updateCartUI() {
            const count = JunxtionApp.getCartCount();
            const total = JunxtionApp.getCartTotal();

            // Update badge
            const badge = document.getElementById('cart-badge');
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';

            // Update sticky cart
            document.getElementById('cart-count').textContent = count;
            document.getElementById('cart-total').textContent = 'R' + (total / 100).toFixed(2);
            document.getElementById('sticky-cart').style.display = count > 0 ? 'flex' : 'none';
        }
    </script>
</body>
</html>
