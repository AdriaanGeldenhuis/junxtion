-- ============================================================
-- Migration 006: Payments (Yoco)
-- Junxtion Restaurant App
-- ============================================================

-- Payments table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `provider` ENUM('yoco', 'cash', 'card_manual', 'other') NOT NULL DEFAULT 'yoco',
    `checkout_id` VARCHAR(100) NULL DEFAULT NULL,
    `payment_id` VARCHAR(100) NULL DEFAULT NULL,
    `status` ENUM('pending', 'processing', 'succeeded', 'failed', 'cancelled', 'refunded', 'partial_refund') NOT NULL DEFAULT 'pending',
    `amount_cents` INT UNSIGNED NOT NULL,
    `currency` VARCHAR(3) NOT NULL DEFAULT 'ZAR',
    `redirect_url` VARCHAR(500) NULL DEFAULT NULL,
    `failure_reason` VARCHAR(255) NULL DEFAULT NULL,
    `metadata` JSON NULL DEFAULT NULL,
    `raw_response` JSON NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payments_order_id` (`order_id`),
    KEY `idx_payments_checkout_id` (`checkout_id`),
    KEY `idx_payments_payment_id` (`payment_id`),
    KEY `idx_payments_status` (`status`),
    KEY `idx_payments_provider` (`provider`),
    KEY `idx_payments_created_at` (`created_at`),
    CONSTRAINT `fk_payments_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payment events (webhook log for audit/debugging)
CREATE TABLE IF NOT EXISTS `payment_events` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `provider` VARCHAR(50) NOT NULL,
    `checkout_id` VARCHAR(100) NULL DEFAULT NULL,
    `payment_id` VARCHAR(100) NULL DEFAULT NULL,
    `event_type` VARCHAR(100) NOT NULL,
    `event_id` VARCHAR(100) NULL DEFAULT NULL,
    `event_json` JSON NOT NULL,
    `signature_valid` TINYINT(1) NOT NULL DEFAULT 1,
    `processed` TINYINT(1) NOT NULL DEFAULT 0,
    `process_error` VARCHAR(255) NULL DEFAULT NULL,
    `received_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `processed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `idx_payment_events_checkout_id` (`checkout_id`),
    KEY `idx_payment_events_payment_id` (`payment_id`),
    KEY `idx_payment_events_event_type` (`event_type`),
    KEY `idx_payment_events_event_id` (`event_id`),
    KEY `idx_payment_events_received_at` (`received_at`),
    KEY `idx_payment_events_processed` (`processed`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Refunds table
CREATE TABLE IF NOT EXISTS `refunds` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `payment_id` INT UNSIGNED NOT NULL,
    `order_id` INT UNSIGNED NOT NULL,
    `refund_id` VARCHAR(100) NULL DEFAULT NULL,
    `idempotency_key` VARCHAR(64) NOT NULL,
    `amount_cents` INT UNSIGNED NOT NULL,
    `reason` VARCHAR(255) NULL DEFAULT NULL,
    `status` ENUM('pending', 'processing', 'succeeded', 'failed') NOT NULL DEFAULT 'pending',
    `failure_reason` VARCHAR(255) NULL DEFAULT NULL,
    `initiated_by_staff_id` INT UNSIGNED NULL DEFAULT NULL,
    `raw_response` JSON NULL DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_refunds_idempotency_key` (`idempotency_key`),
    KEY `idx_refunds_payment_id` (`payment_id`),
    KEY `idx_refunds_order_id` (`order_id`),
    KEY `idx_refunds_refund_id` (`refund_id`),
    KEY `idx_refunds_status` (`status`),
    CONSTRAINT `fk_refunds_payment` FOREIGN KEY (`payment_id`) REFERENCES `payments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_refunds_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_refunds_staff` FOREIGN KEY (`initiated_by_staff_id`) REFERENCES `staff_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
