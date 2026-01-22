-- ============================================================
-- Junxtion Restaurant App - Master Migration
-- ============================================================
--
-- Run this file to execute all migrations in order.
-- Alternatively, run each file individually via phpMyAdmin.
--
-- Order:
-- 1. 001_users.sql        - Customer accounts
-- 2. 002_staff_roles.sql  - Staff and roles
-- 3. 003_sessions_otp.sql - Auth sessions and OTP
-- 4. 004_menu.sql         - Menu structure
-- 5. 005_orders.sql       - Orders system
-- 6. 006_payments.sql     - Yoco payments
-- 7. 007_notifications.sql- FCM notifications
-- 8. 008_audit.sql        - Audit logging
-- 9. 009_settings.sql     - App settings
--
-- ============================================================

-- Set SQL mode for compatibility
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+02:00";

-- ============================================================
-- NOTE: On Xneelo/cPanel with phpMyAdmin limitations,
-- you may need to run each migration file separately.
--
-- To run via command line (if SSH available):
-- mysql -u username -p database_name < 001_users.sql
-- mysql -u username -p database_name < 002_staff_roles.sql
-- ... etc
--
-- Or import each file via phpMyAdmin one at a time.
-- ============================================================

COMMIT;

-- Migration tracking table
CREATE TABLE IF NOT EXISTS `migrations` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `migration` VARCHAR(100) NOT NULL,
    `batch` INT UNSIGNED NOT NULL,
    `executed_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_migrations_migration` (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Record migrations as they run
-- INSERT INTO migrations (migration, batch) VALUES ('001_users', 1);
-- INSERT INTO migrations (migration, batch) VALUES ('002_staff_roles', 1);
-- etc...
