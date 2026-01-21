<?php
/**
 * Customer Menu Page
 */
$configPath = __DIR__ . '/../../private/config.php';
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
    <title>Menu - <?= htmlspecialchars($appName) ?></title>
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
            <h1 class="page-title">Menu</h1>
            <div style="width:24px;"></div>
        </header>

        <!-- Search -->
        <div class="search-container">
            <div class="search-input-wrapper">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text" id="search-input" class="search-input" placeholder="Search menu...">
            </div>
        </div>

        <!-- Category Tabs -->
        <div class="category-tabs" id="category-tabs">
            <button class="category-tab active" data-category="">All</button>
        </div>

        <!-- Menu Grid -->
        <div class="page" style="padding-top:0;">
            <div class="menu-grid" id="menu-grid">
                <div class="empty-state">Loading menu...</div>
            </div>
        </div>
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
        <a href="/app/home.php" class="nav-item">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
            <span>Home</span>
        </a>
        <a href="/app/menu.php" class="nav-item active">
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
        let menuData = { categories: [], items: [] };
        let currentCategory = '';
        let searchTerm = '';

        document.addEventListener('DOMContentLoaded', async () => {
            // Check URL params
            const params = new URLSearchParams(window.location.search);
            if (params.get('category')) {
                currentCategory = params.get('category');
            }
            if (params.get('search')) {
                document.getElementById('search-input').focus();
            }

            // Load menu
            await loadMenu();
            updateCartUI();

            // Search handler
            let searchTimeout;
            document.getElementById('search-input').addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchTerm = e.target.value.toLowerCase();
                    renderItems();
                }, 300);
            });
        });

        async function loadMenu() {
            try {
                const response = await fetch('/api/menu');
                const data = await response.json();

                if (data.success) {
                    menuData = data.data;
                    renderCategories();
                    renderItems();
                }
            } catch (e) {
                console.error('Error loading menu:', e);
                document.getElementById('menu-grid').innerHTML = `
                    <div class="empty-state">
                        <p>Failed to load menu</p>
                        <button class="btn btn-primary" onclick="location.reload()">Try Again</button>
                    </div>
                `;
            }
        }

        function renderCategories() {
            const tabs = document.getElementById('category-tabs');
            tabs.innerHTML = `
                <button class="category-tab ${currentCategory === '' ? 'active' : ''}"
                        data-category="" onclick="selectCategory('')">All</button>
                ${menuData.categories.map(cat => `
                    <button class="category-tab ${currentCategory == cat.id ? 'active' : ''}"
                            data-category="${cat.id}" onclick="selectCategory('${cat.id}')">
                        ${cat.name}
                    </button>
                `).join('')}
            `;
        }

        function selectCategory(categoryId) {
            currentCategory = categoryId;
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.category == categoryId);
            });
            renderItems();
        }

        function renderItems() {
            const grid = document.getElementById('menu-grid');
            let items = menuData.items?.filter(i => i.available) || [];

            // Filter by category
            if (currentCategory) {
                items = items.filter(i => i.category_id == currentCategory);
            }

            // Filter by search
            if (searchTerm) {
                items = items.filter(i =>
                    i.name.toLowerCase().includes(searchTerm) ||
                    (i.description && i.description.toLowerCase().includes(searchTerm))
                );
            }

            if (items.length === 0) {
                grid.innerHTML = `
                    <div class="empty-state" style="grid-column:1/-1;">
                        <p>No items found</p>
                        ${searchTerm ? '<p class="text-muted">Try a different search term</p>' : ''}
                    </div>
                `;
                return;
            }

            grid.innerHTML = items.map(item => `
                <div class="menu-item" onclick="showItemModal(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                    <img src="${item.image_path || '/assets/images/placeholder.svg'}" alt="${item.name}" class="menu-item-image">
                    <div class="menu-item-content">
                        <div class="menu-item-name">${item.name}</div>
                        ${item.description ? `<div class="menu-item-description">${item.description}</div>` : ''}
                        <div class="menu-item-price">R${(item.price_cents / 100).toFixed(2)}</div>
                    </div>
                </div>
            `).join('');
        }

        function showItemModal(item) {
            const modifiers = item.modifier_groups || [];

            const content = `
                <div class="modal-header">
                    <h3 class="modal-title">${item.name}</h3>
                    <button class="modal-close" onclick="JunxtionApp.closeModal(this.closest('.modal-overlay'))">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                    </button>
                </div>
                <img src="${item.image_path || '/assets/images/placeholder.svg'}" alt="${item.name}"
                     style="width:100%;height:200px;object-fit:cover;border-radius:var(--radius-lg);margin-bottom:16px;">
                <p style="color:var(--gray-600);margin-bottom:16px;">${item.description || ''}</p>

                ${modifiers.length > 0 ? `
                    <div id="modifier-groups">
                        ${modifiers.map(group => `
                            <div class="modifier-group" data-group-id="${group.id}">
                                <h4>${group.name} ${group.required ? '<span class="required">*Required</span>' : ''}</h4>
                                <div class="modifier-options">
                                    ${(group.modifiers || []).map(mod => `
                                        <label class="modifier-option">
                                            <input type="${group.max_selections === 1 ? 'radio' : 'checkbox'}"
                                                   name="mod_${group.id}"
                                                   value="${mod.id}"
                                                   data-name="${mod.name}"
                                                   data-price="${mod.price_delta_cents || 0}">
                                            <span class="modifier-name">${mod.name}</span>
                                            ${mod.price_delta_cents ? `<span class="modifier-price">+R${(mod.price_delta_cents / 100).toFixed(2)}</span>` : ''}
                                        </label>
                                    `).join('')}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                ` : ''}

                <div class="item-modal-footer">
                    <div class="quantity-row">
                        <span>Quantity</span>
                        <div class="quantity-control">
                            <button class="quantity-btn" onclick="updateModalQty(-1)">-</button>
                            <span class="quantity-value" id="modal-qty">1</span>
                            <button class="quantity-btn" onclick="updateModalQty(1)">+</button>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-block btn-lg" onclick="addItemToCart(${JSON.stringify(item).replace(/"/g, '&quot;')})">
                        Add to Cart - <span id="modal-price">R${(item.price_cents / 100).toFixed(2)}</span>
                    </button>
                </div>
            `;
            window.currentItemPrice = item.price_cents;
            window.modalQty = 1;
            JunxtionApp.showModal(content);

            // Listen for modifier changes
            document.querySelectorAll('#modifier-groups input').forEach(input => {
                input.addEventListener('change', updateModalPrice);
            });
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

            document.getElementById('cart-badge').textContent = count;
            document.getElementById('cart-badge').style.display = count > 0 ? 'flex' : 'none';
            document.getElementById('cart-count').textContent = count;
            document.getElementById('cart-total').textContent = 'R' + (total / 100).toFixed(2);
            document.getElementById('sticky-cart').style.display = count > 0 ? 'flex' : 'none';
        }
    </script>

    <style>
        .back-btn {
            width: 40px;
            height: 40px;
            border: none;
            background: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            color: var(--gray-700);
        }
        .search-container {
            padding: 0 16px 16px;
            background: white;
            border-bottom: 1px solid var(--gray-200);
        }
        .search-input-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            background: var(--gray-100);
            padding: 12px 16px;
            border-radius: 12px;
        }
        .search-input-wrapper svg {
            color: var(--gray-500);
            flex-shrink: 0;
        }
        .search-input {
            flex: 1;
            border: none;
            background: none;
            font-size: 16px;
            outline: none;
        }
        .modifier-group {
            margin-bottom: 20px;
        }
        .modifier-group h4 {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .required {
            font-size: 11px;
            color: var(--danger);
            font-weight: 500;
        }
        .modifier-option {
            display: flex;
            align-items: center;
            padding: 12px;
            background: var(--gray-50);
            border-radius: 8px;
            margin-bottom: 8px;
            cursor: pointer;
        }
        .modifier-option input {
            margin-right: 12px;
            accent-color: var(--primary);
        }
        .modifier-name {
            flex: 1;
        }
        .modifier-price {
            color: var(--gray-500);
            font-size: 14px;
        }
        .item-modal-footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid var(--gray-200);
        }
        .quantity-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
    </style>
</body>
</html>
