-- ============================================================
-- Migration 002: Staff & Roles
-- Junxtion Restaurant App
-- ============================================================

-- Roles table
CREATE TABLE IF NOT EXISTS `roles` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(50) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `permissions` JSON NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_roles_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Staff users table
CREATE TABLE IF NOT EXISTS `staff_users` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) NULL DEFAULT NULL,
    `password_hash` VARCHAR(255) NOT NULL,
    `role_id` INT UNSIGNED NOT NULL,
    `active` TINYINT(1) NOT NULL DEFAULT 1,
    `login_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    `last_login_at` TIMESTAMP NULL DEFAULT NULL,
    `last_login_ip` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_staff_email` (`email`),
    KEY `idx_staff_phone` (`phone`),
    KEY `idx_staff_role_id` (`role_id`),
    KEY `idx_staff_active` (`active`),
    CONSTRAINT `fk_staff_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default roles
INSERT INTO `roles` (`name`, `description`, `permissions`) VALUES
('super_admin', 'Full system access', '{"all": true}'),
('manager', 'Restaurant manager - can manage menu, orders, staff', '{"menu": true, "orders": true, "staff": true, "reports": true, "settings": true, "refunds": true}'),
('cashier', 'Handle orders and payments', '{"orders": true, "payments": true}'),
('kitchen', 'Kitchen staff - view and update order status', '{"orders": {"view": true, "update_status": true}}'),
('delivery', 'Delivery driver - view assigned deliveries', '{"orders": {"view_delivery": true, "update_delivery": true}}');

-- Insert default super admin (password: ChangeMe123!)
-- IMPORTANT: Change this password immediately after first login
INSERT INTO `staff_users` (`full_name`, `email`, `phone`, `password_hash`, `role_id`) VALUES
('Admin', 'admin@junxtionapp.co.za', NULL, '$argon2id$v=19$m=65536,t=4,p=1$c29tZXNhbHQ$RdescudvJCsgt3ube/N0yJr1y5P3rKvHYCQMpMLxvyA', 1);
