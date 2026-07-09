-- =============================================================================
-- Inventory — physical verification correction requests (Task 4).
-- Applied by migration 2026-01-14-000002_CreateInventoryCorrections.php
-- =============================================================================
CREATE TABLE `inv_corrections` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `system_bags` DECIMAL(14,2) DEFAULT 0,
    `physical_bags` DECIMAL(14,2) DEFAULT 0,
    `difference` DECIMAL(14,2) DEFAULT 0,          -- physical − system
    `reason` VARCHAR(30) DEFAULT NULL,             -- moisture_loss|damaged|wrong_entry|missing_stock|unknown
    `note` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(10) DEFAULT 'pending',        -- pending|approved|rejected
    `movement_id` INT UNSIGNED DEFAULT NULL,       -- adjustment entry generated on approval
    `requested_by` INT UNSIGNED DEFAULT NULL,
    `reviewed_by` INT UNSIGNED DEFAULT NULL,
    `review_note` VARCHAR(255) DEFAULT NULL,
    `reviewed_at` DATETIME NULL,
    `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`), KEY `company_status` (`company_id`,`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
