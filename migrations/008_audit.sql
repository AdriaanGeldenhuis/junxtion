-- ============================================================
-- Migration 008: Audit Logs
-- Junxtion Restaurant App
-- ============================================================

-- Audit logs table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `staff_id` INT UNSIGNED NULL DEFAULT NULL,
    `user_id` INT UNSIGNED NULL DEFAULT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` INT UNSIGNED NULL DEFAULT NULL,
    `before_json` JSON NULL DEFAULT NULL,
    `after_json` JSON NULL DEFAULT NULL,
    `changes_json` JSON NULL DEFAULT NULL,
    `ip` VARCHAR(45) NULL DEFAULT NULL,
    `user_agent` VARCHAR(500) NULL DEFAULT NULL,
    `request_id` VARCHAR(64) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_logs_staff_id` (`staff_id`),
    KEY `idx_audit_logs_user_id` (`user_id`),
    KEY `idx_audit_logs_action` (`action`),
    KEY `idx_audit_logs_entity` (`entity_type`, `entity_id`),
    KEY `idx_audit_logs_created_at` (`created_at`),
    KEY `idx_audit_logs_request_id` (`request_id`),
    CONSTRAINT `fk_audit_logs_staff` FOREIGN KEY (`staff_id`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_audit_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Activity logs (lighter weight, for tracking general activity)
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_type` ENUM('customer', 'staff', 'system') NOT NULL,
    `user_id` INT UNSIGNED NULL DEFAULT NULL,
    `activity` VARCHAR(100) NOT NULL,
    `description` VARCHAR(255) NULL DEFAULT NULL,
    `metadata` JSON NULL DEFAULT NULL,
    `ip` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_activity_logs_user` (`user_type`, `user_id`),
    KEY `idx_activity_logs_activity` (`activity`),
    KEY `idx_activity_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Error logs (for debugging)
CREATE TABLE IF NOT EXISTS `error_logs` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `level` ENUM('debug', 'info', 'warning', 'error', 'critical') NOT NULL DEFAULT 'error',
    `message` TEXT NOT NULL,
    `context` JSON NULL DEFAULT NULL,
    `file` VARCHAR(255) NULL DEFAULT NULL,
    `line` INT UNSIGNED NULL DEFAULT NULL,
    `trace` TEXT NULL DEFAULT NULL,
    `request_uri` VARCHAR(500) NULL DEFAULT NULL,
    `request_method` VARCHAR(10) NULL DEFAULT NULL,
    `ip` VARCHAR(45) NULL DEFAULT NULL,
    `user_type` VARCHAR(20) NULL DEFAULT NULL,
    `user_id` INT UNSIGNED NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_error_logs_level` (`level`),
    KEY `idx_error_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Common audit actions reference (for documentation)
-- Actions include:
-- - auth.login, auth.logout, auth.failed_login
-- - user.created, user.updated, user.deleted
-- - staff.created, staff.updated, staff.deleted, staff.role_changed
-- - menu.category.created, menu.category.updated, menu.category.deleted
-- - menu.item.created, menu.item.updated, menu.item.deleted, menu.item.toggled
-- - menu.modifier.created, menu.modifier.updated, menu.modifier.deleted
-- - order.created, order.status_changed, order.cancelled, order.refunded
-- - payment.initiated, payment.succeeded, payment.failed, payment.refunded
-- - settings.updated
-- - notification.sent, notification.scheduled
