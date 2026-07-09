-- =============================================================================
-- Mandi Inventory — core schema (Task 1). Applied by migration
--   2026-01-14-000001_CreateInventoryCore.php
-- Reproduced here for reference / manual upgrade. All tables company-scoped.
-- =============================================================================

CREATE TABLE `inv_products` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `unit` VARCHAR(20) DEFAULT 'bag',
    `avg_weight` DECIMAL(10,2) DEFAULT 0,   -- kg per bag
    `sku` VARCHAR(60) DEFAULT NULL,         -- QR / barcode
    `low_stock` INT DEFAULT 0,              -- low-stock threshold (bags)
    `status` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`), KEY `company_id_name` (`company_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `inv_warehouses` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `location` VARCHAR(191) DEFAULT NULL,
    `capacity` INT DEFAULT 0,               -- bags (0 = unlimited)
    `status` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`), KEY `company_id_name` (`company_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `inv_parties` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `type` VARCHAR(12) DEFAULT 'both',      -- supplier|customer|both
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`), KEY `company_id_name` (`company_id`,`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `inv_lots` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `lot_no` VARCHAR(40) NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `party_id` INT UNSIGNED DEFAULT NULL,
    `rack` VARCHAR(40) DEFAULT NULL,
    `opening_bags` DECIMAL(12,2) DEFAULT 0,
    `opening_weight` DECIMAL(14,2) DEFAULT 0,
    `remaining_bags` DECIMAL(12,2) DEFAULT 0,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`), KEY `company_id_lot_no` (`company_id`,`lot_no`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- THE inventory ledger — one row per inward / outward / adjustment.
CREATE TABLE `inv_movements` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `entry_no` VARCHAR(40) NOT NULL,
    `movement_type` VARCHAR(12) NOT NULL,   -- inward|outward|adjustment
    `direction` TINYINT(2) DEFAULT 1,       -- +1 in, -1 out
    `product_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `party_id` INT UNSIGNED DEFAULT NULL,
    `lot_id` INT UNSIGNED DEFAULT NULL,
    `bags` DECIMAL(12,2) DEFAULT 0,
    `weight` DECIMAL(14,2) DEFAULT 0,
    `rack` VARCHAR(40) DEFAULT NULL,
    `vehicle_no` VARCHAR(30) DEFAULT NULL,
    `reason` VARCHAR(40) DEFAULT NULL,
    `notes` VARCHAR(255) DEFAULT NULL,
    `photo` VARCHAR(191) DEFAULT NULL,
    `source` VARCHAR(12) DEFAULT 'web',     -- web|mobile|voice
    `day_closed` TINYINT(1) DEFAULT 0,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NULL, `updated_at` DATETIME NULL, `deleted_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `company_type` (`company_id`,`movement_type`),
    KEY `company_prod_wh` (`company_id`,`product_id`,`warehouse_id`),
    KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fast running balance per (company, product, warehouse).
CREATE TABLE `inv_stock` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `product_id` INT UNSIGNED NOT NULL,
    `warehouse_id` INT UNSIGNED NOT NULL,
    `bags` DECIMAL(14,2) DEFAULT 0,
    `weight` DECIMAL(16,2) DEFAULT 0,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_stock` (`company_id`,`product_id`,`warehouse_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Proof files (photo / video / voice / pdf) linked to a movement.
CREATE TABLE `inv_attachments` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id` INT UNSIGNED NOT NULL,
    `movement_id` INT UNSIGNED DEFAULT NULL,
    `product_id` INT UNSIGNED DEFAULT NULL,
    `lot_id` INT UNSIGNED DEFAULT NULL,
    `party_id` INT UNSIGNED DEFAULT NULL,
    `kind` VARCHAR(12) DEFAULT 'image',     -- image|video|audio|pdf|doc
    `original_name` VARCHAR(191) NOT NULL,
    `stored_name` VARCHAR(191) NOT NULL,
    `mime` VARCHAR(100) DEFAULT NULL,
    `size` INT DEFAULT 0,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME NULL,
    PRIMARY KEY (`id`), KEY `company_movement` (`company_id`,`movement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Register the module + grant Admin/Viewer roles (idempotent guards omitted).
INSERT INTO `modules` (`name`,`code`,`url`,`icon`,`parent_id`,`sort_order`,`is_menu`,`status`,`created_at`,`updated_at`)
SELECT 'Inventory','inventory','inventory','bi bi-box-seam',NULL,(SELECT COALESCE(MAX(m.sort_order),0)+1 FROM `modules` m),1,1,NOW(),NOW()
WHERE NOT EXISTS (SELECT 1 FROM `modules` WHERE `code`='inventory');

INSERT INTO `role_permissions` (`role_id`,`module_id`,`permission_id`)
SELECT r.id, m.id, p.id
FROM `roles` r CROSS JOIN `modules` m
JOIN `permissions` p ON p.code IN ('view','add','edit','delete','approve','export','print')
WHERE m.code='inventory' AND r.code IN ('admin','viewer')
  AND NOT EXISTS (SELECT 1 FROM `role_permissions` rp WHERE rp.role_id=r.id AND rp.module_id=m.id AND rp.permission_id=p.id);
