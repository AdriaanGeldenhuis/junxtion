<?php
/**
 * Junxtion Restaurant - Menu Page
 * Browse menu by category with search functionality
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

    <title>Menu - <?= htmlspecialchars($appName) ?></title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    <link rel="stylesheet" href="/assets/css/app.css">

    <style>
        /* Menu Page Specific */
        .menu-header {
            background: var(--warm-white);
            padding: calc(var(--space-4) + var(--safe-area-top)) var(--space-4) var(--space-4);
            position: sticky;
            top: 0;
            z-index: 100;
            border-bottom: 1px solid var(--gray-200);
        }

        .search-input-wrapper {
            display: flex;
            align-items: center;
            gap: var(--space-3);
            background: var(--gray-100);
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-xl);
            transition: all var(--transition-fast);
        }

        .search-input-wrapper:focus-within {
            background: white;
            box-shadow: 0 0 0 2px var(--primary);
        }

        .search-input-wrapper svg {
            color: var(--gray-500);
            flex-shrink: 0;
        }

        .search-input-wrapper input {
            flex: 1;
            border: none;
            background: none;
            font-size: var(--font-size-base);
            outline: none;
        }

        .search-input-wrapper input::placeholder {
            color: var(--gray-400);
        }

        .clear-search {
            width: 24px;
            height: 24px;
            border: none;
            background: var(--gray-300);
            border-radius: 50%;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            color: var(--gray-600);
        }

        .clear-search.show {
            display: flex;
        }

        /* Category Tabs */
        .category-tabs {
            display: flex;
            gap: var(--space-2);
            overflow-x: auto;
            padding: var(--space-4);
            background: var(--warm-white);
            scrollbar-width: none;
            -webkit-overflow-scrolling: touch;
            border-bottom: 1px solid var(--gray-100);
        }

        .category-tabs::-webkit-scrollbar {
            display: none;
        }

        .category-tab {
            flex-shrink: 0;
            padding: var(--space-3) var(--space-5);
            background: var(--gray-100);
            border: none;
            border-radius: var(--radius-full);
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--gray-600);
            cursor: pointer;
            transition: all var(--transition-fast);
            white-space: nowrap;
        }

        .category-tab:hover {
            background: var(--gray-200);
        }

        .category-tab.active {
            background: var(--primary);
            color: white;
        }

        /* Menu Content */
        .menu-content {
            padding: var(--space-4);
            padding-bottom: 180px;
        }

        .category-section {
            margin-bottom: var(--space-6);
        }

        .category-section-title {
            font-family: var(--font-display);
            font-size: var(--font-size-xl);
            font-weight: 600;
            margin-bottom: var(--space-4);
            padding-bottom: var(--space-2);
            border-bottom: 2px solid var(--gray-200);
            display: flex;
            align-items: center;
            gap: var(--space-2);
        }

        .category-section-title .count {
            font-family: var(--font-sans);
            font-size: var(--font-size-sm);
            font-weight: 500;
            color: var(--gray-500);
            background: var(--gray-100);
            padding: 2px 10px;
            border-radius: var(--radius-full);
        }

        /* Menu List View */
        .menu-list {
            display: flex;
            flex-direction: column;
            gap: var(--space-3);
        }

        .menu-list-item {
            display: flex;
            gap: var(--space-4);
            padding: var(--space-4);
            background: var(--warm-white);
            border-radius: var(--radius-xl);
            box-shadow: var(--shadow-sm);
            cursor: pointer;
            transition: all var(--transition-normal);
            border: 1px solid transparent;
        }

        .menu-list-item:hover {
            box-shadow: var(--shadow-md);
            border-color: var(--primary-glow);
        }

        .menu-list-item:active {
            transform: scale(0.99);
        }

        .menu-list-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: var(--radius-lg);
            background: var(--gray-100);
            flex-shrink: 0;
        }

        .menu-list-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-width: 0;
        }

        .menu-list-name {
            font-family: var(--font-display);
            font-weight: 600;
            font-size: var(--font-size-base);
            color: var(--gray-900);
            margin-bottom: var(--space-1);
        }

        .menu-list-description {
            font-size: var(--font-size-sm);
            color: var(--gray-500);
            line-height: 1.4;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .menu-list-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: var(--space-3);
        }

        .menu-list-price {
            font-weight: 700;
            font-size: var(--font-size-lg);
            color: var(--primary);
        }

        .menu-list-add {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            border: none;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--transition-fast);
            box-shadow: 0 2px 8px rgba(200, 16, 46, 0.25);
        }

        .menu-list-add:hover {
            transform: scale(1.1);
        }

        /* Search Results */
        .search-results-header {
            padding: var(--space-4);
            background: var(--cream-dark);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .search-results-text {
            font-size: var(--font-size-sm);
            color: var(--gray-600);
        }

        .search-results-text strong {
            color: var(--gray-900);
        }

        /* No Results */
        .no-results {
            text-align: center;
            padding: var(--space-12) var(--space-6);
        }

        .no-results svg {
            width: 80px;
            height: 80px;
            color: var(--gray-300);
            margin-bottom: var(--space-4);
        }

        .no-results h3 {
            font-family: var(--font-display);
            margin-bottom: var(--space-2);
            color: var(--gray-700);
        }

        .no-results p {
            color: var(--gray-500);
            margin-bottom: var(--space-6);
        }

        /* Responsive - Tablet */
        @media (min-width: 768px) {
            .menu-header {
                padding: calc(var(--space-5) + var(--safe-area-top)) var(--space-6) var(--space-5);
            }

            .search-input-wrapper {
                max-width: 500px;
                margin: 0 auto;
            }

            .category-tabs {
                padding: var(--space-4) var(--space-6);
                justify-content: center;
                flex-wrap: wrap;
            }

            .category-tab {
                padding: var(--space-3) var(--space-6);
            }

            .menu-content {
                padding: var(--space-6);
                max-width: 900px;
                margin: 0 auto;
            }

            .menu-list {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: var(--space-4);
            }

            .menu-list-image {
                width: 120px;
                height: 120px;
            }

            .category-section-title {
                font-size: var(--font-size-2xl);
            }
        }

        /* Responsive - Desktop */
        @media (min-width: 1024px) {
            .menu-content {
                max-width: 1200px;
                padding: var(--space-8);
            }

            .menu-list {
                grid-template-columns: repeat(3, 1fr);
            }

            .category-tabs {
                gap: var(--space-3);
            }
        }

        /* Responsive - Large Desktop */
        @media (min-width: 1280px) {
            .menu-content {
                max-width: 1400px;
            }

            .menu-list {
                grid-template-columns: repeat(4, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="app-container">
        <!-- Header with Search -->
        <header class="menu-header">
            <div class="search-input-wrapper">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="M21 21l-4.35-4.35"/>
                </svg>
                <input type="text"
                       id="search-input"
                       placeholder="Search menu..."
                       autocomplete="off">
                <button class="clear-search" id="clear-search" onclick="clearSearch()">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </header>

        <!-- Category Tabs -->
        <div class="category-tabs" id="category-tabs">
            <button class="category-tab active" data-id="all">All</button>
        </div>

        <!-- Search Results Info -->
        <div class="search-results-header" id="search-results-header" style="display:none;">
            <span class="search-results-text" id="search-results-text"></span>
            <button onclick="clearSearch()" style="background:none;border:none;color:var(--primary);font-weight:600;cursor:pointer;">Clear</button>
        </div>

        <!-- Menu Content -->
        <div class="menu-content" id="menu-content">
            <!-- Loading skeleton -->
            <div class="menu-list">
                <div class="skeleton" style="height:120px;border-radius:var(--radius-xl);"></div>
                <div class="skeleton" style="height:120px;border-radius:var(--radius-xl);"></div>
                <div class="skeleton" style="height:120px;border-radius:var(--radius-xl);"></div>
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
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
            </svg>
            <span>Home</span>
        </a>
        <a href="/app/menu.php" class="nav-item active">
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
        let menuData = { categories: [], items: [] };
        let activeCategory = 'all';
        let searchQuery = '';
        let searchTimeout;

        document.addEventListener('DOMContentLoaded', async () => {
            await loadMenu();

            // Check URL params
            const params = new URLSearchParams(window.location.search);
            const categoryId = params.get('category');
            const focusSearch = params.get('search');

            if (categoryId) {
                selectCategory(categoryId);
            }

            if (focusSearch) {
                document.getElementById('search-input').focus();
            }

            // Setup search
            setupSearch();

            // Update cart UI
            updateCartUI();
        });

        async function loadMenu() {
            try {
                const response = await fetch('/api/menu');
                const data = await response.json();

                if (data.success) {
                    menuData = data.data;
                    renderCategoryTabs();
                    renderMenu();
                }
            } catch (e) {
                console.error('Error loading menu:', e);
                document.getElementById('menu-content').innerHTML = `
                    <div class="no-results">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 8v4M12 16h.01"/>
                        </svg>
                        <h3>Failed to load menu</h3>
                        <p>Please check your connection and try again.</p>
                        <button class="btn btn-primary" onclick="location.reload()">Retry</button>
                    </div>
                `;
            }
        }

        function renderCategoryTabs() {
            const tabs = document.getElementById('category-tabs');
            const categories = menuData.categories || [];

            tabs.innerHTML = `
                <button class="category-tab active" data-id="all" onclick="selectCategory('all')">All</button>
                ${categories.map(cat => `
                    <button class="category-tab" data-id="${cat.id}" onclick="selectCategory('${cat.id}')">${cat.name}</button>
                `).join('')}
            `;
        }

        function selectCategory(id) {
            activeCategory = id;

            // Update tab styles
            document.querySelectorAll('.category-tab').forEach(tab => {
                tab.classList.toggle('active', tab.dataset.id === String(id));
            });

            // Scroll tab into view
            const activeTab = document.querySelector(`.category-tab[data-id="${id}"]`);
            if (activeTab) {
                activeTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
            }

            renderMenu();
        }

        function setupSearch() {
            const input = document.getElementById('search-input');
            const clearBtn = document.getElementById('clear-search');

            input.addEventListener('input', (e) => {
                searchQuery = e.target.value.trim();
                clearBtn.classList.toggle('show', searchQuery.length > 0);

                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    renderMenu();
                }, 300);
            });

            input.addEventListener('keyup', (e) => {
                if (e.key === 'Escape') {
                    clearSearch();
                }
            });
        }

        function clearSearch() {
            searchQuery = '';
            document.getElementById('search-input').value = '';
            document.getElementById('clear-search').classList.remove('show');
            document.getElementById('search-results-header').style.display = 'none';
            renderMenu();
        }

        function renderMenu() {
            const content = document.getElementById('menu-content');
            const searchHeader = document.getElementById('search-results-header');
            const searchText = document.getElementById('search-results-text');

            let items = menuData.items || [];
            const categories = menuData.categories || [];

            // Filter by search
            if (searchQuery) {
                const query = searchQuery.toLowerCase();
                items = items.filter(item =>
                    item.name.toLowerCase().includes(query) ||
                    (item.description && item.description.toLowerCase().includes(query))
                );

                searchHeader.style.display = 'flex';
                searchText.innerHTML = `Found <strong>${items.length}</strong> result${items.length !== 1 ? 's' : ''} for "${searchQuery}"`;
            } else {
                searchHeader.style.display = 'none';
            }

            // Filter by category
            if (activeCategory !== 'all' && !searchQuery) {
                items = items.filter(item => String(item.category_id) === String(activeCategory));
            }

            // Check for empty
            if (items.length === 0) {
                content.innerHTML = `
                    <div class="no-results">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <circle cx="11" cy="11" r="8"/>
                            <path d="M21 21l-4.35-4.35"/>
                        </svg>
                        <h3>No items found</h3>
                        <p>${searchQuery ? 'Try a different search term' : 'This category is empty'}</p>
                        ${searchQuery ? '<button class="btn btn-primary" onclick="clearSearch()">Clear Search</button>' : ''}
                    </div>
                `;
                return;
            }

            // Render by category sections if showing all
            if (activeCategory === 'all' && !searchQuery) {
                content.innerHTML = categories.map(cat => {
                    const catItems = items.filter(i => String(i.category_id) === String(cat.id));
                    if (catItems.length === 0) return '';

                    return `
                        <section class="category-section" id="cat-${cat.id}">
                            <h3 class="category-section-title">
                                ${cat.name}
                                <span class="count">${catItems.length}</span>
                            </h3>
                            <div class="menu-list">
                                ${catItems.map(renderMenuItem).join('')}
                            </div>
                        </section>
                    `;
                }).join('');
            } else {
                // Single list view
                const categoryName = activeCategory === 'all'
                    ? 'Search Results'
                    : (categories.find(c => String(c.id) === String(activeCategory))?.name || 'Menu');

                content.innerHTML = `
                    <section class="category-section">
                        ${!searchQuery ? `<h3 class="category-section-title">${categoryName}<span class="count">${items.length}</span></h3>` : ''}
                        <div class="menu-list">
                            ${items.map(renderMenuItem).join('')}
                        </div>
                    </section>
                `;
            }
        }

        function renderMenuItem(item) {
            const itemJson = JSON.stringify(item).replace(/"/g, '&quot;');
            return `
                <div class="menu-list-item" onclick="showItemModal(${itemJson})">
                    <img src="${item.image_path || '/assets/images/food-placeholder.jpg'}"
                         alt="${item.name}"
                         class="menu-list-image"
                         onerror="this.src='/assets/images/food-placeholder.jpg'">
                    <div class="menu-list-content">
                        <div>
                            <div class="menu-list-name">${item.name}</div>
                            ${item.description ? `<div class="menu-list-description">${item.description}</div>` : ''}
                        </div>
                        <div class="menu-list-footer">
                            <div class="menu-list-price">R${(item.price_cents / 100).toFixed(2)}</div>
                            <button class="menu-list-add" onclick="event.stopPropagation(); quickAddToCart(${itemJson})">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                    <line x1="5" y1="12" x2="19" y2="12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            `;
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

            document.querySelectorAll('#modifier-groups input:checked').forEach(input => {
                total += parseInt(input.dataset.price) || 0;
            });

            total *= window.modalQty || 1;
            document.getElementById('modal-price').textContent = 'R' + (total / 100).toFixed(2);
        }

        function addItemToCart(item) {
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

            const badge = document.getElementById('cart-badge');
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';

            document.getElementById('cart-count').textContent = count;
            document.getElementById('cart-total').textContent = 'R' + (total / 100).toFixed(2);
            document.getElementById('sticky-cart').style.display = count > 0 ? 'flex' : 'none';
        }
    </script>
</body>
</html>
