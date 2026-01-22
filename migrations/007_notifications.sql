-- ============================================================
-- Migration 007: Notifications (FCM)
-- Junxtion Restaurant App
-- ============================================================

-- Device tokens (FCM registration)
CREATE TABLE IF NOT EXISTS `devices` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_type` ENUM('customer', 'staff') NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `device_id` VARCHAR(64) NOT NULL,
    `fcm_token` VARCHAR(500) NOT NULL,
    `platform` ENUM('android', 'ios', 'web') NOT NULL DEFAULT 'android',
    `app_version` VARCHAR(20) NULL DEFAULT NULL,
    `device_model` VARCHAR(100) NULL DEFAULT NULL,
    `os_version` VARCHAR(20) NULL DEFAULT NULL,
    `enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `last_seen_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_devices_device_id` (`device_id`),
    KEY `idx_devices_user` (`user_type`, `user_id`),
    KEY `idx_devices_fcm_token` (`fcm_token`(255)),
    KEY `idx_devices_enabled` (`enabled`),
    KEY `idx_devices_platform` (`platform`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification preferences
CREATE TABLE IF NOT EXISTS `notification_preferences` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_type` ENUM('customer', 'staff') NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `orders_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `specials_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `announcements_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `marketing_enabled` TINYINT(1) NOT NULL DEFAULT 0,
    `quiet_start` TIME NULL DEFAULT NULL,
    `quiet_end` TIME NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_notification_preferences_user` (`user_type`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications (for history/inbox)
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `audience_type` ENUM('all_customers', 'all_staff', 'specific_users', 'topic') NOT NULL,
    `audience_json` JSON NULL DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `image_path` VARCHAR(255) NULL DEFAULT NULL,
    `deeplink` VARCHAR(255) NULL DEFAULT NULL,
    `data_json` JSON NULL DEFAULT NULL,
    `priority` ENUM('normal', 'high') NOT NULL DEFAULT 'normal',
    `channel` VARCHAR(50) NULL DEFAULT NULL,
    `created_by_staff_id` INT UNSIGNED NULL DEFAULT NULL,
    `schedule_at` TIMESTAMP NULL DEFAULT NULL,
    `send_status` ENUM('draft', 'scheduled', 'sending', 'sent', 'failed', 'cancelled') NOT NULL DEFAULT 'draft',
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `sent_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `failed_count` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notifications_send_status` (`send_status`),
    KEY `idx_notifications_schedule_at` (`schedule_at`),
    KEY `idx_notifications_created_at` (`created_at`),
    KEY `idx_notifications_audience_type` (`audience_type`),
    CONSTRAINT `fk_notifications_staff` FOREIGN KEY (`created_by_staff_id`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notification deliveries (individual send records)
CREATE TABLE IF NOT EXISTS `notification_deliveries` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `notification_id` INT UNSIGNED NOT NULL,
    `device_id` INT UNSIGNED NOT NULL,
    `user_type` ENUM('customer', 'staff') NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `status` ENUM('pending', 'sent', 'delivered', 'failed', 'clicked') NOT NULL DEFAULT 'pending',
    `fcm_message_id` VARCHAR(255) NULL DEFAULT NULL,
    `provider_response` JSON NULL DEFAULT NULL,
    `error_message` VARCHAR(255) NULL DEFAULT NULL,
    `sent_at` TIMESTAMP NULL DEFAULT NULL,
    `delivered_at` TIMESTAMP NULL DEFAULT NULL,
    `opened_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notification_deliveries_notification_id` (`notification_id`),
    KEY `idx_notification_deliveries_device_id` (`device_id`),
    KEY `idx_notification_deliveries_user` (`user_type`, `user_id`),
    KEY `idx_notification_deliveries_status` (`status`),
    KEY `idx_notification_deliveries_sent_at` (`sent_at`),
    CONSTRAINT `fk_notification_deliveries_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_notification_deliveries_device` FOREIGN KEY (`device_id`) REFERENCES `devices` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User inbox (persistent notification history per user)
CREATE TABLE IF NOT EXISTS `inbox_messages` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_type` ENUM('customer', 'staff') NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `notification_id` INT UNSIGNED NULL DEFAULT NULL,
    `title` VARCHAR(255) NOT NULL,
    `body` TEXT NOT NULL,
    `image_path` VARCHAR(255) NULL DEFAULT NULL,
    `deeplink` VARCHAR(255) NULL DEFAULT NULL,
    `read_at` TIMESTAMP NULL DEFAULT NULL,
    `archived_at` TIMESTAMP NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_inbox_messages_user` (`user_type`, `user_id`),
    KEY `idx_inbox_messages_read_at` (`read_at`),
    KEY `idx_inbox_messages_created_at` (`created_at`),
    CONSTRAINT `fk_inbox_messages_notification` FOREIGN KEY (`notification_id`) REFERENCES `notifications` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
