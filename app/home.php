<?php
/**
 * Customer Home Page
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
    <title><?= htmlspecialchars($appName) ?></title>
    <link rel="manifest" href="/pwa/manifest.json">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="home-header">
            <div class="header-top">
                <div class="greeting">
                    <h1 id="greeting-text">Welcome!</h1>
                    <p class="subtitle" id="user-name"></p>
                </div>
                <button class="icon-btn" onclick="location.href='/app/profile.php'">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </button>
            </div>
            <div class="search-bar" onclick="location.href='/app/menu.php?search=1'">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                <span>Search menu...</span>
            </div>
        </header>

        <!-- Specials Carousel -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Today's Specials</h2>
            </div>
            <div class="specials-carousel" id="specials-carousel">
                <div class="skeleton-card"></div>
            </div>
        </section>

        <!-- Categories -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Categories</h2>
                <a href="/app/menu.php" class="link-text">View All</a>
            </div>
            <div class="categories-grid" id="categories-grid">
                <div class="skeleton-category"></div>
                <div class="skeleton-category"></div>
                <div class="skeleton-category"></div>
                <div class="skeleton-category"></div>
            </div>
        </section>

        <!-- Popular Items -->
        <section class="section">
            <div class="section-header">
                <h2 class="section-title">Popular Items</h2>
                <a href="/app/menu.php" class="link-text">See All</a>
            </div>
            <div class="menu-grid" id="popular-items">
                <div class="skeleton-item"></div>
                <div class="skeleton-item"></div>
                <div class="skeleton-item"></div>
                <div class="skeleton-item"></div>
            </div>
        </section>

        <!-- Quick Actions -->
        <section class="section">
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
                    <span>Track Order</span>
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
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <span>Home</span>
        </a>
        <a href="/app/menu.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
            <span>Menu</span>
        </a>
        <a href="/app/cart.php" class="nav-item">
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
        document.addEventListener('DOMContentLoaded', async () => {
            // Update greeting
            const hour = new Date().getHours();
            let greeting = 'Good evening';
            if (hour < 12) greeting = 'Good morning';
            else if (hour < 17) greeting = 'Good afternoon';

            const greetingEl = document.getElementById('greeting-text');
            const userNameEl = document.getElementById('user-name');

            if (JunxtionApp.user) {
                greetingEl.textContent = `${greeting}!`;
                userNameEl.textContent = JunxtionApp.user.name || JunxtionApp.user.phone;
            } else {
                greetingEl.textContent = greeting + '!';
                userNameEl.textContent = 'Welcome to <?= htmlspecialchars($appName) ?>';
            }

            // Load data
            loadSpecials();
            loadMenu();
            updateCartUI();
        });

        async function loadSpecials() {
            try {
                const response = await fetch('/api/menu/specials');
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    document.getElementById('specials-carousel').innerHTML = data.data.map(special => `
                        <div class="special-card" onclick="handleSpecialClick('${special.promo_code || ''}')">
                            <img src="${special.image_path || '/assets/images/placeholder.svg'}" alt="${special.title}">
                            <div class="special-content">
                                <div class="special-title">${special.title}</div>
                                ${special.body ? `<div class="special-description">${special.body}</div>` : ''}
                                ${special.promo_code ? `<div class="special-code">Use code: ${special.promo_code}</div>` : ''}
                            </div>
                        </div>
                    `).join('');
                } else {
                    document.getElementById('specials-carousel').innerHTML = `
                        <div class="special-card" style="background:linear-gradient(135deg, var(--primary), var(--primary-dark));">
                            <div class="special-content" style="position:relative;">
                                <div class="special-title">Welcome to <?= htmlspecialchars($appName) ?>!</div>
                                <div class="special-description">Browse our menu and order your favorites</div>
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
                    // Render categories
                    const categories = data.data.categories || [];
                    document.getElementById('categories-grid').innerHTML = categories.slice(0, 6).map(cat => `
                        <a href="/app/menu.php?category=${cat.id}" class="category-card">
                            <div class="category-icon">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                </svg>
                            </div>
                            <span class="category-name">${cat.name}</span>
                        </a>
                    `).join('');

                    // Render popular items (first 4 available items)
                    const items = data.data.items?.filter(i => i.available).slice(0, 4) || [];
                    document.getElementById('popular-items').innerHTML = items.map(item => `
                        <div class="menu-item" onclick="showItemModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                            <img src="${item.image_path || '/assets/images/placeholder.svg'}" alt="${item.name}" class="menu-item-image">
                            <div class="menu-item-content">
                                <div class="menu-item-name">${item.name}</div>
                                <div class="menu-item-price">R${(item.price_cents / 100).toFixed(2)}</div>
                            </div>
                        </div>
                    `).join('');
                }
            } catch (e) {
                console.error('Error loading menu:', e);
            }
        }

        function handleSpecialClick(promoCode) {
            if (promoCode) {
                localStorage.setItem('junxtion_promo', promoCode);
                JunxtionApp.showToast(`Code ${promoCode} saved!`, 'success');
            }
            location.href = '/app/menu.php';
        }

        function showItemModal(item) {
            const content = `
                <div class="modal-header">
                    <h3 class="modal-title">${item.name}</h3>
                    <button class="modal-close" onclick="JunxtionApp.closeModal(this.closest('.modal-overlay'))">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <img src="${item.image_path || '/assets/images/placeholder.svg'}" alt="${item.name}"
                     style="width:100%;height:200px;object-fit:cover;border-radius:var(--radius-lg);margin-bottom:16px;">
                <p style="color:var(--gray-600);margin-bottom:16px;">${item.description || 'Delicious menu item'}</p>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
                    <span style="font-size:24px;font-weight:700;color:var(--primary);">R${(item.price_cents / 100).toFixed(2)}</span>
                    <div class="quantity-control">
                        <button class="quantity-btn" onclick="updateModalQty(-1)">-</button>
                        <span class="quantity-value" id="modal-qty">1</span>
                        <button class="quantity-btn" onclick="updateModalQty(1)">+</button>
                    </div>
                </div>
                <button class="btn btn-primary btn-block" onclick="addItemToCart(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                    Add to Cart
                </button>
            `;
            JunxtionApp.showModal(content);
        }

        let modalQty = 1;
        function updateModalQty(delta) {
            modalQty = Math.max(1, modalQty + delta);
            document.getElementById('modal-qty').textContent = modalQty;
        }

        function addItemToCart(item) {
            JunxtionApp.addToCart({
                id: item.id,
                name: item.name,
                price: item.price_cents,
                image: item.image_path,
                quantity: modalQty
            });
            JunxtionApp.closeModal(document.querySelector('.modal-overlay'));
            updateCartUI();
            modalQty = 1;
        }

        function updateCartUI() {
            const count = JunxtionApp.getCartCount();
            const total = JunxtionApp.getCartTotal();

            document.getElementById('cart-badge').textContent = count;
            document.getElementById('cart-badge').style.display = count > 0 ? 'flex' : 'none';
            document.getElementById('cart-count').textContent = count;
            document.getElementById('cart-total').textContent = 'R' + (total / 100).toFixed(2);
            document.getElementById('sticky-cart').style.display = count > 0 ? 'flex' : 'none';
        }
    </script>

    <style>
        .home-header {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 20px 16px 24px;
            border-radius: 0 0 24px 24px;
        }
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }
        .greeting h1 {
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .subtitle {
            opacity: 0.9;
            font-size: 14px;
        }
        .icon-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            cursor: pointer;
        }
        .search-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            background: white;
            padding: 14px 16px;
            border-radius: 12px;
            color: var(--gray-500);
            cursor: pointer;
        }
        .section {
            padding: 20px 16px;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        .section-title {
            font-size: 18px;
            font-weight: 700;
        }
        .link-text {
            color: var(--primary);
            font-weight: 600;
            font-size: 14px;
        }
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
        }
        .category-card {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            color: var(--gray-700);
            transition: transform 0.2s;
        }
        .category-card:active {
            transform: scale(0.95);
        }
        .category-icon {
            width: 56px;
            height: 56px;
            background: var(--gray-100);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
        }
        .category-name {
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }
        .quick-actions {
            display: flex;
            gap: 12px;
        }
        .quick-action {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 16px;
            background: white;
            border-radius: 12px;
            text-decoration: none;
            color: var(--gray-700);
        }
        .quick-action-icon {
            width: 48px;
            height: 48px;
            background: var(--primary);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .quick-action span {
            font-size: 12px;
            font-weight: 600;
        }
        .special-code {
            margin-top: 8px;
            background: rgba(255,255,255,0.2);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        .skeleton-card {
            width: calc(100vw - 48px);
            height: 180px;
            background: var(--gray-200);
            border-radius: 16px;
            animation: shimmer 1.5s infinite;
        }
        .skeleton-category, .skeleton-item {
            background: var(--gray-200);
            border-radius: 12px;
            animation: shimmer 1.5s infinite;
        }
        .skeleton-category {
            height: 100px;
        }
        .skeleton-item {
            height: 180px;
        }
    </style>
</body>
</html>
