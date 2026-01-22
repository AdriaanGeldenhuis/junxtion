-- ============================================================
-- Migration 005: Orders
-- Junxtion Restaurant App
-- ============================================================

-- Orders table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_number` VARCHAR(20) NOT NULL,
    `user_id` INT UNSIGNED NULL DEFAULT NULL,
    `order_type` ENUM('delivery', 'pickup', 'dinein') NOT NULL,
    `table_code` VARCHAR(20) NULL DEFAULT NULL,
    `status` ENUM(
        'PENDING_PAYMENT',
        'PLACED',
        'ACCEPTED',
        'IN_PREP',
        'READY',
        'OUT_FOR_DELIVERY',
        'COMPLETED',
        'CANCELLED'
    ) NOT NULL DEFAULT 'PENDING_PAYMENT',
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded', 'partial_refund') NOT NULL DEFAULT 'pending',

    -- Price breakdown (all in cents for precision)
    `subtotal_cents` INT UNSIGNED NOT NULL DEFAULT 0,
    `discount_cents` INT UNSIGNED NOT NULL DEFAULT 0,
    `delivery_fee_cents` INT UNSIGNED NOT NULL DEFAULT 0,
    `service_fee_cents` INT UNSIGNED NOT NULL DEFAULT 0,
    `tip_cents` INT UNSIGNED NOT NULL DEFAULT 0,
    `total_cents` INT UNSIGNED NOT NULL DEFAULT 0,

    -- Snapshot data (preserved even if customer changes profile)
    `customer_phone_snapshot` VARCHAR(20) NULL DEFAULT NULL,
    `customer_name_snapshot` VARCHAR(100) NULL DEFAULT NULL,
    `customer_email_snapshot` VARCHAR(255) NULL DEFAULT NULL,
    `delivery_address_snapshot` JSON NULL DEFAULT NULL,

    -- Additional info
    `special_instructions` TEXT NULL DEFAULT NULL,
    `promo_code_used` VARCHAR(50) NULL DEFAULT NULL,
    `estimated_ready_at` TIMESTAMP NULL DEFAULT NULL,
    `actual_ready_at` TIMESTAMP NULL DEFAULT NULL,
    `delivered_at` TIMESTAMP NULL DEFAULT NULL,
    `cancelled_at` TIMESTAMP NULL DEFAULT NULL,
    `cancel_reason` VARCHAR(255) NULL DEFAULT NULL,
    `cancelled_by` ENUM('customer', 'staff', 'system') NULL DEFAULT NULL,

    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_orders_order_number` (`order_number`),
    KEY `idx_orders_user_id` (`user_id`),
    KEY `idx_orders_status` (`status`),
    KEY `idx_orders_payment_status` (`payment_status`),
    KEY `idx_orders_order_type` (`order_type`),
    KEY `idx_orders_created_at` (`created_at`),
    KEY `idx_orders_table_code` (`table_code`),
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order items (with price snapshots)
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `item_id` INT UNSIGNED NULL DEFAULT NULL,
    `name_snapshot` VARCHAR(150) NOT NULL,
    `description_snapshot` VARCHAR(255) NULL DEFAULT NULL,
    `price_cents_snapshot` INT UNSIGNED NOT NULL,
    `qty` INT UNSIGNED NOT NULL DEFAULT 1,
    `subtotal_cents` INT UNSIGNED NOT NULL,
    `notes` TEXT NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_items_order_id` (`order_id`),
    KEY `idx_order_items_item_id` (`item_id`),
    CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_items_item` FOREIGN KEY (`item_id`) REFERENCES `items` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order item modifiers (with price snapshots)
CREATE TABLE IF NOT EXISTS `order_item_modifiers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_item_id` INT UNSIGNED NOT NULL,
    `modifier_id` INT UNSIGNED NULL DEFAULT NULL,
    `name_snapshot` VARCHAR(100) NOT NULL,
    `price_cents_snapshot` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_item_modifiers_order_item_id` (`order_item_id`),
    KEY `idx_order_item_modifiers_modifier_id` (`modifier_id`),
    CONSTRAINT `fk_order_item_modifiers_order_item` FOREIGN KEY (`order_item_id`) REFERENCES `order_items` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_item_modifiers_modifier` FOREIGN KEY (`modifier_id`) REFERENCES `modifiers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order status events (audit trail)
CREATE TABLE IF NOT EXISTS `order_status_events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `previous_status` VARCHAR(30) NULL DEFAULT NULL,
    `new_status` VARCHAR(30) NOT NULL,
    `by_staff_id` INT UNSIGNED NULL DEFAULT NULL,
    `by_system` TINYINT(1) NOT NULL DEFAULT 0,
    `notes` VARCHAR(255) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_order_status_events_order_id` (`order_id`),
    KEY `idx_order_status_events_created_at` (`created_at`),
    CONSTRAINT `fk_order_status_events_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_order_status_events_staff` FOREIGN KEY (`by_staff_id`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Delivery assignments
CREATE TABLE IF NOT EXISTS `delivery_assignments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `driver_id` INT UNSIGNED NOT NULL,
    `status` ENUM('assigned', 'picked_up', 'delivered', 'failed') NOT NULL DEFAULT 'assigned',
    `assigned_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `picked_up_at` TIMESTAMP NULL DEFAULT NULL,
    `delivered_at` TIMESTAMP NULL DEFAULT NULL,
    `notes` TEXT NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_delivery_assignments_order_id` (`order_id`),
    KEY `idx_delivery_assignments_driver_id` (`driver_id`),
    KEY `idx_delivery_assignments_status` (`status`),
    CONSTRAINT `fk_delivery_assignments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_delivery_assignments_driver` FOREIGN KEY (`driver_id`) REFERENCES `staff_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Function to generate order number
DELIMITER //
CREATE FUNCTION IF NOT EXISTS generate_order_number()
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    DECLARE new_number VARCHAR(20);
    SET new_number = CONCAT('JNX', DATE_FORMAT(NOW(), '%y%m%d'), LPAD(FLOOR(RAND() * 10000), 4, '0'));
    RETURN new_number;
END//
DELIMITER ;
