-- =============================================================================
-- Mandi Inventory — Daily Closing (Task 7) + product rate (Task 8). Applied by
-- migrations 2026-01-14-000003_CreateInventoryDailyClosings and
-- 2026-01-14-000004_AddRateToInvProducts. Reproduced here for manual upgrade.
-- =============================================================================

-- One closing row per company per day. A day is LOCKED while a row exists for it
-- with status = 'closed'; reopening flips status to 'reopened'.
CREATE TABLE `inv_daily_closings` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `closing_date` DATE NOT NULL,
    `opening_bags` DECIMAL(16,2) DEFAULT 0,
    `received_bags` DECIMAL(16,2) DEFAULT 0,
    `dispatched_bags` DECIMAL(16,2) DEFAULT 0,
    `adjustment_bags` DECIMAL(16,2) DEFAULT 0,   -- net signed
    `closing_bags` DECIMAL(16,2) DEFAULT 0,
    `received_weight` DECIMAL(18,2) DEFAULT 0,
    `dispatched_weight` DECIMAL(18,2) DEFAULT 0,
    `difference_bags` DECIMAL(16,2) DEFAULT 0,   -- closing − expected
    `pending_corrections` INT DEFAULT 0,
    `entry_count` INT DEFAULT 0,
    `status` VARCHAR(10) DEFAULT 'closed',       -- closed|reopened
    `notes` VARCHAR(255) DEFAULT NULL,
    `closed_by` INT UNSIGNED DEFAULT NULL,
    `closed_at` DATETIME NULL,
    `reopened_by` INT UNSIGNED DEFAULT NULL,
    `reopened_at` DATETIME NULL,
    `created_at` DATETIME NULL, `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_company_date` (`company_id`,`closing_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Per-bag rate so the owner dashboard can value the inventory (bags × rate).
ALTER TABLE `inv_products`
    ADD COLUMN `rate` DECIMAL(12,2) DEFAULT 0 AFTER `avg_weight`;
