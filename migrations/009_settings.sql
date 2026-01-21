-- ============================================================
-- Migration 009: Settings
-- Junxtion Restaurant App
-- ============================================================

-- Application settings (key-value store)
CREATE TABLE IF NOT EXISTS `settings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(100) NOT NULL,
    `value_json` JSON NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `category` VARCHAR(50) NOT NULL DEFAULT 'general',
    `is_public` TINYINT(1) NOT NULL DEFAULT 0,
    `updated_by_staff_id` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_settings_key` (`key`),
    KEY `idx_settings_category` (`category`),
    KEY `idx_settings_is_public` (`is_public`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `settings` (`key`, `value_json`, `description`, `category`, `is_public`) VALUES

-- Business settings
('business_name', '"Junxtion"', 'Restaurant name', 'business', 1),
('business_phone', '"+27000000000"', 'Contact phone number', 'business', 1),
('business_email', '"info@junxtionapp.co.za"', 'Contact email', 'business', 1),
('business_address', '{"line1": "", "line2": "", "city": "", "postal_code": ""}', 'Physical address', 'business', 1),

-- Operating hours
('business_hours', '{
    "monday": {"open": "09:00", "close": "21:00", "closed": false},
    "tuesday": {"open": "09:00", "close": "21:00", "closed": false},
    "wednesday": {"open": "09:00", "close": "21:00", "closed": false},
    "thursday": {"open": "09:00", "close": "21:00", "closed": false},
    "friday": {"open": "09:00", "close": "22:00", "closed": false},
    "saturday": {"open": "09:00", "close": "22:00", "closed": false},
    "sunday": {"open": "10:00", "close": "20:00", "closed": false}
}', 'Operating hours per day', 'business', 1),

-- Order settings
('ordering_enabled', 'true', 'Master switch for online ordering', 'orders', 1),
('ordering_paused', 'false', 'Temporarily pause ordering (kitchen overload)', 'orders', 1),
('ordering_paused_message', '"We are temporarily not accepting orders. Please try again later."', 'Message shown when ordering is paused', 'orders', 1),

('delivery_enabled', 'true', 'Enable delivery orders', 'orders', 1),
('pickup_enabled', 'true', 'Enable pickup orders', 'orders', 1),
('dinein_enabled', 'true', 'Enable dine-in orders', 'orders', 1),

('min_order_cents', '5000', 'Minimum order amount in cents (R50)', 'orders', 1),
('delivery_fee_cents', '2500', 'Delivery fee in cents (R25)', 'orders', 1),
('free_delivery_threshold_cents', '15000', 'Free delivery above this amount (R150)', 'orders', 1),
('service_fee_percent', '0', 'Service fee percentage (0-100)', 'orders', 1),

('delivery_radius_km', '10', 'Maximum delivery radius in kilometers', 'orders', 1),
('estimated_prep_minutes', '20', 'Default preparation time in minutes', 'orders', 1),
('estimated_delivery_minutes', '30', 'Default delivery time in minutes', 'orders', 1),

-- Payment settings
('yoco_test_mode', 'true', 'Use Yoco test mode', 'payments', 0),
('yoco_public_key', '""', 'Yoco public key', 'payments', 0),
('cash_on_delivery_enabled', 'false', 'Allow cash on delivery', 'payments', 1),
('tips_enabled', 'true', 'Allow customers to add tips', 'orders', 1),
('tip_suggestions', '[10, 15, 20]', 'Suggested tip percentages', 'orders', 1),

-- Notification settings
('new_order_sound_enabled', 'true', 'Play sound for new orders in admin', 'notifications', 0),
('auto_accept_orders', 'false', 'Automatically accept new paid orders', 'orders', 0),
('order_notification_email', '""', 'Email for order notifications', 'notifications', 0),

-- App settings
('app_maintenance_mode', 'false', 'Put app in maintenance mode', 'app', 0),
('app_maintenance_message', '"We are updating our systems. Please check back soon."', 'Maintenance mode message', 'app', 1),
('app_version_minimum', '"1.0.0"', 'Minimum supported app version', 'app', 1),
('app_update_url', '"https://junxtionapp.co.za"', 'URL for app updates', 'app', 1),

-- Tax settings
('tax_enabled', 'true', 'Prices include VAT', 'tax', 1),
('tax_rate_percent', '15', 'VAT rate (South Africa)', 'tax', 1),
('tax_number', '""', 'VAT registration number', 'tax', 0),

-- Receipt/Invoice settings
('receipt_footer_text', '"Thank you for your order!"', 'Text at bottom of receipts', 'receipts', 0),
('invoice_prefix', '"INV"', 'Prefix for invoice numbers', 'receipts', 0);

-- Table codes for dine-in (QR code mapping)
CREATE TABLE IF NOT EXISTS `table_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(20) NOT NULL,
    `table_name` VARCHAR(50) NOT NULL,
    `section` VARCHAR(50) NULL DEFAULT NULL,
    `capacity` INT UNSIGNED NOT NULL DEFAULT 4,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `qr_generated_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_table_codes_code` (`code`),
    KEY `idx_table_codes_active` (`active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample table codes
INSERT INTO `table_codes` (`code`, `table_name`, `section`, `capacity`) VALUES
('T01', 'Table 1', 'Main', 4),
('T02', 'Table 2', 'Main', 4),
('T03', 'Table 3', 'Main', 6),
('T04', 'Table 4', 'Main', 2),
('T05', 'Table 5', 'Patio', 4),
('T06', 'Table 6', 'Patio', 4),
('BAR1', 'Bar Seat 1', 'Bar', 1),
('BAR2', 'Bar Seat 2', 'Bar', 1),
('VIP1', 'VIP Booth 1', 'VIP', 8);

-- Promo codes table
CREATE TABLE IF NOT EXISTS `promo_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `code` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `discount_type` ENUM('percentage', 'fixed') NOT NULL,
    `discount_value` INT UNSIGNED NOT NULL,
    `min_order_cents` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_discount_cents` INT UNSIGNED NULL DEFAULT NULL,
    `usage_limit` INT UNSIGNED NULL DEFAULT NULL,
    `usage_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `per_user_limit` INT UNSIGNED NOT NULL DEFAULT 1,
    `applies_to` ENUM('all', 'delivery', 'pickup', 'dinein') NOT NULL DEFAULT 'all',
    `first_order_only` TINYINT(1) NOT NULL DEFAULT 0,
    `start_at` TIMESTAMP NULL DEFAULT NULL,
    `end_at` TIMESTAMP NULL DEFAULT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_by_staff_id` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_promo_codes_code` (`code`),
    KEY `idx_promo_codes_active` (`active`),
    KEY `idx_promo_codes_dates` (`start_at`, `end_at`),
    CONSTRAINT `fk_promo_codes_staff` FOREIGN KEY (`created_by_staff_id`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Promo code usage tracking
CREATE TABLE IF NOT EXISTS `promo_code_usage` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `promo_code_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `discount_cents` INT UNSIGNED NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_promo_code_usage_code_id` (`promo_code_id`),
    KEY `idx_promo_code_usage_user_id` (`user_id`),
    KEY `idx_promo_code_usage_order_id` (`order_id`),
    CONSTRAINT `fk_promo_code_usage_code` FOREIGN KEY (`promo_code_id`) REFERENCES `promo_codes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_promo_code_usage_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_promo_code_usage_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
