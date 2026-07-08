-- =============================================================================
-- Password Manager module — database changes
-- =============================================================================
-- These statements are applied automatically by the migration
--   app/Database/Migrations/2026-01-13-000004_CreatePasswords.php
-- and are reproduced here for reference / manual application. All statements are
-- idempotent-friendly (guard before running on an existing install).
--
-- NOTE: the stored password lives in `password_enc` as AES ciphertext
-- (base64-wrapped) produced by App\Libraries\PasswordVault — never plaintext.
-- A valid `encryption.key` must be set in .env (php spark key:generate).
-- =============================================================================

-- 1) Vault table -------------------------------------------------------------
CREATE TABLE `passwords` (
    `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `company_id`   INT UNSIGNED DEFAULT NULL,          -- active company (tenancy)
    `title`        VARCHAR(191) NOT NULL,              -- title / name
    `website`      VARCHAR(191) DEFAULT NULL,          -- website / app name
    `username`     VARCHAR(191) DEFAULT NULL,          -- username / email
    `password_enc` TEXT NOT NULL,                      -- ENCRYPTED password
    `notes`        TEXT DEFAULT NULL,
    `category`     VARCHAR(60) DEFAULT NULL,
    `created_by`   INT UNSIGNED DEFAULT NULL,          -- author (user id)
    `created_at`   DATETIME DEFAULT NULL,
    `updated_at`   DATETIME DEFAULT NULL,
    `deleted_at`   DATETIME DEFAULT NULL,              -- soft delete
    PRIMARY KEY (`id`),
    KEY `company_id_category` (`company_id`, `category`),
    KEY `title` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 2) Register the module (needed by the permission filter + super-admin menu) --
INSERT INTO `modules` (`name`, `code`, `url`, `icon`, `parent_id`, `sort_order`, `is_menu`, `status`, `created_at`, `updated_at`)
SELECT 'Password Manager', 'passwords', 'passwords', 'bi bi-shield-lock', NULL,
       (SELECT COALESCE(MAX(m.sort_order), 0) + 1 FROM `modules` m), 1, 1, NOW(), NOW()
WHERE NOT EXISTS (SELECT 1 FROM `modules` WHERE `code` = 'passwords');

-- 3) Grant CRUD to the Admin + Viewer roles (Viewer = the role every customer /
--    firm user gets). Super Admin bypasses permission checks entirely.
INSERT INTO `role_permissions` (`role_id`, `module_id`, `permission_id`)
SELECT r.id, m.id, p.id
FROM `roles` r
CROSS JOIN `modules` m
JOIN `permissions` p ON p.code IN ('view', 'add', 'edit', 'delete')
WHERE m.code = 'passwords'
  AND r.code IN ('admin', 'viewer')
  AND NOT EXISTS (
      SELECT 1 FROM `role_permissions` rp
      WHERE rp.role_id = r.id AND rp.module_id = m.id AND rp.permission_id = p.id
  );

-- =============================================================================
-- Rollback (migration down())
-- =============================================================================
-- DELETE rp FROM `role_permissions` rp JOIN `modules` m ON m.id = rp.module_id WHERE m.code = 'passwords';
-- DELETE FROM `modules` WHERE `code` = 'passwords';
-- DROP TABLE IF EXISTS `passwords`;
