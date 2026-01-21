-- ============================================================
-- Migration 004: Menu (Categories, Items, Modifiers, Specials)
-- Junxtion Restaurant App
-- ============================================================

-- Menu categories
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `image_path` VARCHAR(255) NULL DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_categories_sort_order` (`sort_order`),
    KEY `idx_categories_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Menu items
CREATE TABLE IF NOT EXISTS `items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `category_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `description` TEXT NULL DEFAULT NULL,
    `price_cents` INT UNSIGNED NOT NULL,
    `image_path` VARCHAR(255) NULL DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `featured` TINYINT(1) NOT NULL DEFAULT 0,
    `prep_minutes` INT UNSIGNED NOT NULL DEFAULT 15,
    `calories` INT UNSIGNED NULL DEFAULT NULL,
    `allergens` VARCHAR(255) NULL DEFAULT NULL,
    `tags` VARCHAR(255) NULL DEFAULT NULL,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_items_category_id` (`category_id`),
    KEY `idx_items_active` (`active`),
    KEY `idx_items_featured` (`featured`),
    KEY `idx_items_sort_order` (`sort_order`),
    KEY `idx_items_price` (`price_cents`),
    CONSTRAINT `fk_items_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modifier groups (e.g., "Choose your size", "Add extras")
CREATE TABLE IF NOT EXISTS `modifier_groups` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `item_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `required` TINYINT(1) NOT NULL DEFAULT 0,
    `min_select` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_select` INT UNSIGNED NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_modifier_groups_item_id` (`item_id`),
    KEY `idx_modifier_groups_sort_order` (`sort_order`),
    CONSTRAINT `fk_modifier_groups_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modifiers (individual options within a group)
CREATE TABLE IF NOT EXISTS `modifiers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `group_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `price_cents_delta` INT NOT NULL DEFAULT 0,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_modifiers_group_id` (`group_id`),
    KEY `idx_modifiers_sort_order` (`sort_order`),
    KEY `idx_modifiers_active` (`active`),
    CONSTRAINT `fk_modifiers_group` FOREIGN KEY (`group_id`) REFERENCES `modifier_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Specials / Promotions
CREATE TABLE IF NOT EXISTS `specials` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `title` VARCHAR(150) NOT NULL,
    `body` TEXT NULL DEFAULT NULL,
    `image_path` VARCHAR(255) NULL DEFAULT NULL,
    `discount_type` ENUM('none', 'percentage', 'fixed') NOT NULL DEFAULT 'none',
    `discount_value` INT UNSIGNED NOT NULL DEFAULT 0,
    `applies_to` ENUM('all', 'category', 'item') NOT NULL DEFAULT 'all',
    `applies_to_id` INT UNSIGNED NULL DEFAULT NULL,
    `promo_code` VARCHAR(50) NULL DEFAULT NULL,
    `start_at` TIMESTAMP NULL DEFAULT NULL,
    `end_at` TIMESTAMP NULL DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `sort_order` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_specials_active` (`active`),
    KEY `idx_specials_dates` (`start_at`, `end_at`),
    KEY `idx_specials_promo_code` (`promo_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample categories
INSERT INTO `categories` (`name`, `description`, `sort_order`, `active`) VALUES
('Starters', 'Delicious appetizers to start your meal', 1, 1),
('Mains', 'Hearty main courses', 2, 1),
('Burgers', 'Gourmet burgers made fresh', 3, 1),
('Pizzas', 'Wood-fired pizzas', 4, 1),
('Sides', 'Perfect accompaniments', 5, 1),
('Desserts', 'Sweet endings', 6, 1),
('Drinks', 'Refreshing beverages', 7, 1),
('Specials', 'Chef\'s special dishes', 8, 1);
