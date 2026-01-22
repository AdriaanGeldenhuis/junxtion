-- ============================================================
-- Migration 003: Sessions & OTP
-- Junxtion Restaurant App
-- ============================================================

-- Sessions table (for both customers and staff)
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(64) NOT NULL,
    `user_type` ENUM('customer', 'staff') NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `refresh_token_hash` VARCHAR(64) NOT NULL,
    `device_id` VARCHAR(64) NULL DEFAULT NULL,
    `ip` VARCHAR(45) NOT NULL,
    `user_agent` VARCHAR(500) NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `last_used_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `revoked_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_sessions_user` (`user_type`, `user_id`),
    KEY `idx_sessions_refresh_token` (`refresh_token_hash`),
    KEY `idx_sessions_device_id` (`device_id`),
    KEY `idx_sessions_expires_at` (`expires_at`),
    KEY `idx_sessions_revoked_at` (`revoked_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- OTP codes table
CREATE TABLE IF NOT EXISTS `otp_codes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `phone` VARCHAR(20) NOT NULL,
    `code_hash` VARCHAR(64) NOT NULL,
    `purpose` ENUM('login', 'register', 'verify', 'reset') NOT NULL DEFAULT 'login',
    `attempts` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_attempts` INT UNSIGNED NOT NULL DEFAULT 5,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    `verified_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_otp_phone` (`phone`),
    KEY `idx_otp_expires_at` (`expires_at`),
    KEY `idx_otp_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add 'register' to existing otp_codes purpose ENUM (for existing databases)
-- Run this if upgrading: ALTER TABLE `otp_codes` MODIFY `purpose` ENUM('login', 'register', 'verify', 'reset') NOT NULL DEFAULT 'login';

-- Rate limiting table (optional - can also use file-based)
CREATE TABLE IF NOT EXISTS `rate_limits` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `key` VARCHAR(128) NOT NULL,
    `hits` INT UNSIGNED NOT NULL DEFAULT 1,
    `window_start` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `expires_at` TIMESTAMP NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_rate_limits_key` (`key`),
    KEY `idx_rate_limits_expires` (`expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
