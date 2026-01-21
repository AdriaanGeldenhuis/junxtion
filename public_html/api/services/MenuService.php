<?php
/**
 * Menu Service
 *
 * Handles menu categories, items, modifiers, and specials
 */

class MenuService
{
    private Database $db;
    private AuditService $audit;
    private string $cachePath;
    private int $cacheTtl;

    public function __construct()
    {
        $this->db = $GLOBALS['db'];
        $this->audit = new AuditService();
        $this->cachePath = ($GLOBALS['config']['paths']['private'] ?? __DIR__ . '/../../private') . '/cache/menu.json';
        $this->cacheTtl = $GLOBALS['config']['cache']['menu_ttl'] ?? 60;
    }

    /**
     * Get full menu (public, with caching)
     */
    public function getFullMenu(): array
    {
        // Try cache first
        if ($this->isCacheValid()) {
            $cached = file_get_contents($this->cachePath);
            return json_decode($cached, true);
        }

        $menu = $this->buildMenu();

        // Cache the result
        $this->cacheMenu($menu);

        return $menu;
    }

    /**
     * Build menu from database
     */
    private function buildMenu(): array
    {
        // Get active categories
        $categories = $this->db->query(
            "SELECT id, name, description, image_path
             FROM categories
             WHERE active = 1
             ORDER BY sort_order ASC, name ASC"
        );

        // Get active items
        $items = $this->db->query(
            "SELECT id, category_id, name, description, price_cents, image_path,
                    prep_minutes, calories, allergens, tags, featured
             FROM items
             WHERE active = 1
             ORDER BY sort_order ASC, name ASC"
        );

        // Get modifier groups
        $modifierGroups = $this->db->query(
            "SELECT mg.id, mg.item_id, mg.name, mg.description, mg.required,
                    mg.min_select, mg.max_select
             FROM modifier_groups mg
             INNER JOIN items i ON mg.item_id = i.id AND i.active = 1
             WHERE mg.active = 1
             ORDER BY mg.sort_order ASC"
        );

        // Get modifiers
        $modifiers = $this->db->query(
            "SELECT m.id, m.group_id, m.name, m.price_cents_delta
             FROM modifiers m
             INNER JOIN modifier_groups mg ON m.group_id = mg.id AND mg.active = 1
             WHERE m.active = 1
             ORDER BY m.sort_order ASC"
        );

        // Get active specials
        $specials = $this->db->query(
            "SELECT id, title, body, image_path, discount_type, discount_value,
                    applies_to, applies_to_id, promo_code, start_at, end_at
             FROM specials
             WHERE active = 1
               AND (start_at IS NULL OR start_at <= NOW())
               AND (end_at IS NULL OR end_at >= NOW())
             ORDER BY sort_order ASC"
        );

        // Build modifier lookup
        $modifiersByGroup = [];
        foreach ($modifiers as $mod) {
            $modifiersByGroup[$mod['group_id']][] = [
                'id' => (int) $mod['id'],
                'name' => $mod['name'],
                'price_delta' => (int) $mod['price_cents_delta'],
            ];
        }

        // Build modifier groups lookup
        $groupsByItem = [];
        foreach ($modifierGroups as $group) {
            $groupsByItem[$group['item_id']][] = [
                'id' => (int) $group['id'],
                'name' => $group['name'],
                'description' => $group['description'],
                'required' => (bool) $group['required'],
                'min_select' => (int) $group['min_select'],
                'max_select' => (int) $group['max_select'],
                'modifiers' => $modifiersByGroup[$group['id']] ?? [],
            ];
        }

        // Build items lookup
        $itemsByCategory = [];
        foreach ($items as $item) {
            $itemsByCategory[$item['category_id']][] = [
                'id' => (int) $item['id'],
                'name' => $item['name'],
                'description' => $item['description'],
                'price' => (int) $item['price_cents'],
                'image' => $item['image_path'],
                'prep_minutes' => (int) $item['prep_minutes'],
                'calories' => $item['calories'] ? (int) $item['calories'] : null,
                'allergens' => $item['allergens'],
                'tags' => $item['tags'],
                'featured' => (bool) $item['featured'],
                'modifier_groups' => $groupsByItem[$item['id']] ?? [],
            ];
        }

        // Build final menu structure
        $menu = [
            'categories' => [],
            'specials' => [],
            'generated_at' => date('c'),
        ];

        foreach ($categories as $cat) {
            $menu['categories'][] = [
                'id' => (int) $cat['id'],
                'name' => $cat['name'],
                'description' => $cat['description'],
                'image' => $cat['image_path'],
                'items' => $itemsByCategory[$cat['id']] ?? [],
            ];
        }

        foreach ($specials as $special) {
            $menu['specials'][] = [
                'id' => (int) $special['id'],
                'title' => $special['title'],
                'body' => $special['body'],
                'image' => $special['image_path'],
                'discount_type' => $special['discount_type'],
                'discount_value' => (int) $special['discount_value'],
                'promo_code' => $special['promo_code'],
                'start_at' => $special['start_at'],
                'end_at' => $special['end_at'],
            ];
        }

        return $menu;
    }

    /**
     * Get single item by ID
     */
    public function getItem(int $id): ?array
    {
        $item = $this->db->queryOne(
            "SELECT i.*, c.name as category_name
             FROM items i
             INNER JOIN categories c ON i.category_id = c.id
             WHERE i.id = ? AND i.active = 1",
            [$id]
        );

        if (!$item) {
            return null;
        }

        // Get modifier groups
        $groups = $this->db->query(
            "SELECT id, name, description, required, min_select, max_select
             FROM modifier_groups
             WHERE item_id = ? AND active = 1
             ORDER BY sort_order ASC",
            [$id]
        );

        foreach ($groups as &$group) {
            $group['modifiers'] = $this->db->query(
                "SELECT id, name, price_cents_delta
                 FROM modifiers
                 WHERE group_id = ? AND active = 1
                 ORDER BY sort_order ASC",
                [$group['id']]
            );
        }

        $item['modifier_groups'] = $groups;

        return $item;
    }

    /**
     * Clear menu cache
     */
    public function clearCache(): void
    {
        if (file_exists($this->cachePath)) {
            unlink($this->cachePath);
        }
    }

    /**
     * Check if cache is valid
     */
    private function isCacheValid(): bool
    {
        if (!file_exists($this->cachePath)) {
            return false;
        }

        $cacheAge = time() - filemtime($this->cachePath);
        return $cacheAge < $this->cacheTtl;
    }

    /**
     * Cache menu data
     */
    private function cacheMenu(array $menu): void
    {
        $dir = dirname($this->cachePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($this->cachePath, json_encode($menu), LOCK_EX);
    }

    // ========================================
    // Admin methods
    // ========================================

    /**
     * Get all categories (admin)
     */
    public function getAllCategories(): array
    {
        return $this->db->query(
            "SELECT * FROM categories ORDER BY sort_order ASC, name ASC"
        );
    }

    /**
     * Create category
     */
    public function createCategory(array $data): int
    {
        $id = $this->db->insert('categories', [
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'image_path' => $data['image_path'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'active' => $data['active'] ?? 1,
        ]);

        $this->audit->log('menu.category.created', 'category', $id, null, $data);
        $this->clearCache();

        return $id;
    }

    /**
     * Update category
     */
    public function updateCategory(int $id, array $data): bool
    {
        $before = $this->db->queryOne("SELECT * FROM categories WHERE id = ?", [$id]);

        $updated = $this->db->update('categories', $data, 'id = ?', [$id]);

        if ($updated) {
            $this->audit->log('menu.category.updated', 'category', $id, $before, $data);
            $this->clearCache();
        }

        return $updated > 0;
    }

    /**
     * Delete category
     */
    public function deleteCategory(int $id): bool
    {
        $before = $this->db->queryOne("SELECT * FROM categories WHERE id = ?", [$id]);

        // Check if category has items
        $itemCount = $this->db->queryValue(
            "SELECT COUNT(*) FROM items WHERE category_id = ?",
            [$id]
        );

        if ($itemCount > 0) {
            throw new Exception("Cannot delete category with {$itemCount} items");
        }

        $deleted = $this->db->delete('categories', 'id = ?', [$id]);

        if ($deleted) {
            $this->audit->log('menu.category.deleted', 'category', $id, $before, null);
            $this->clearCache();
        }

        return $deleted > 0;
    }

    /**
     * Get all items (admin)
     */
    public function getAllItems(?int $categoryId = null): array
    {
        $sql = "SELECT i.*, c.name as category_name
                FROM items i
                INNER JOIN categories c ON i.category_id = c.id";
        $params = [];

        if ($categoryId) {
            $sql .= " WHERE i.category_id = ?";
            $params[] = $categoryId;
        }

        $sql .= " ORDER BY c.sort_order ASC, i.sort_order ASC, i.name ASC";

        return $this->db->query($sql, $params);
    }

    /**
     * Create item
     */
    public function createItem(array $data): int
    {
        $id = $this->db->insert('items', [
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_cents' => $data['price_cents'],
            'image_path' => $data['image_path'] ?? null,
            'active' => $data['active'] ?? 1,
            'featured' => $data['featured'] ?? 0,
            'prep_minutes' => $data['prep_minutes'] ?? 15,
            'calories' => $data['calories'] ?? null,
            'allergens' => $data['allergens'] ?? null,
            'tags' => $data['tags'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        $this->audit->log('menu.item.created', 'item', $id, null, $data);
        $this->clearCache();

        return $id;
    }

    /**
     * Update item
     */
    public function updateItem(int $id, array $data): bool
    {
        $before = $this->db->queryOne("SELECT * FROM items WHERE id = ?", [$id]);

        $updated = $this->db->update('items', $data, 'id = ?', [$id]);

        if ($updated) {
            $this->audit->log('menu.item.updated', 'item', $id, $before, $data);
            $this->clearCache();
        }

        return $updated > 0;
    }

    /**
     * Toggle item active status
     */
    public function toggleItem(int $id): bool
    {
        $item = $this->db->queryOne("SELECT active FROM items WHERE id = ?", [$id]);
        if (!$item) {
            return false;
        }

        $newStatus = $item['active'] ? 0 : 1;

        $updated = $this->db->update('items', ['active' => $newStatus], 'id = ?', [$id]);

        if ($updated) {
            $this->audit->log(
                'menu.item.toggled',
                'item',
                $id,
                ['active' => $item['active']],
                ['active' => $newStatus]
            );
            $this->clearCache();
        }

        return $updated > 0;
    }

    /**
     * Delete item
     */
    public function deleteItem(int $id): bool
    {
        $before = $this->db->queryOne("SELECT * FROM items WHERE id = ?", [$id]);

        $deleted = $this->db->delete('items', 'id = ?', [$id]);

        if ($deleted) {
            $this->audit->log('menu.item.deleted', 'item', $id, $before, null);
            $this->clearCache();
        }

        return $deleted > 0;
    }

    /**
     * Create modifier group
     */
    public function createModifierGroup(int $itemId, array $data): int
    {
        $id = $this->db->insert('modifier_groups', [
            'item_id' => $itemId,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'required' => $data['required'] ?? 0,
            'min_select' => $data['min_select'] ?? 0,
            'max_select' => $data['max_select'] ?? 1,
            'sort_order' => $data['sort_order'] ?? 0,
            'active' => 1,
        ]);

        $this->audit->log('menu.modifier_group.created', 'modifier_group', $id, null, $data);
        $this->clearCache();

        return $id;
    }

    /**
     * Create modifier
     */
    public function createModifier(int $groupId, array $data): int
    {
        $id = $this->db->insert('modifiers', [
            'group_id' => $groupId,
            'name' => $data['name'],
            'price_cents_delta' => $data['price_cents_delta'] ?? 0,
            'sort_order' => $data['sort_order'] ?? 0,
            'active' => 1,
        ]);

        $this->audit->log('menu.modifier.created', 'modifier', $id, null, $data);
        $this->clearCache();

        return $id;
    }

    /**
     * Handle image upload
     */
    public function uploadImage(array $file, string $type = 'menu'): ?string
    {
        $config = $GLOBALS['config']['uploads'];

        // Validate file
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload error: ' . $file['error']);
        }

        if ($file['size'] > $config['max_size']) {
            throw new Exception('File too large. Maximum size is 5MB.');
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $config['allowed_types'])) {
            throw new Exception('Invalid file type. Allowed: JPG, PNG, WebP');
        }

        // Generate safe filename
        $filename = Crypto::safeFilename($file['name']);
        $uploadPath = $config['menu_path'] . $filename;

        // Move file
        if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to save file');
        }

        // Return relative path for storage
        return '/uploads/menu/' . $filename;
    }
}
