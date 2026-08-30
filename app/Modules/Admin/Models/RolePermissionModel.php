<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * RolePermissionModel — CI4 port of admin/models/Role_permission_mod.
 * Backs the RBAC role/module matrix editor: 4 seeded roles (erp_user_type_roles)
 * × the module registry (erp_module_registry) with can_view/add/edit/delete rows
 * in erp_role_module_permissions. Tables are lazily created + defaults synced
 * (idempotent — only inserts missing rows). The backend gate reads the same
 * tables via the ported permission_helper.
 */
class RolePermissionModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function roleTypes(): array
    {
        return [
            1 => ['role_name' => 'Administrator', 'job_title' => 'System Owner', 'job_description' => 'Full ERP control, settings, users, roles, and all operational modules.'],
            2 => ['role_name' => 'Manager', 'job_title' => 'Operations Manager', 'job_description' => 'Reviews reports, supervises entries, and manages assigned operational work.'],
            3 => ['role_name' => 'Accountant', 'job_title' => 'Accountant', 'job_description' => 'Manages assigned billing, purchase, account, stock, receipt, and reporting work.'],
            4 => ['role_name' => 'Viewer', 'job_title' => 'Read Only User', 'job_description' => 'Can view assigned ERP information and reports where permitted.'],
        ];
    }

    public function modules(): array
    {
        if (function_exists('erp_module_registry')) {
            return erp_module_registry();
        }
        return [];
    }

    /** Lazily create the RBAC tables (guarded) and sync default rows. */
    public function ensureTables(): void
    {
        $db = $this->db();
        $db->query("CREATE TABLE IF NOT EXISTS `erp_user_type_roles` (
            `user_type` INT(11) UNSIGNED NOT NULL,
            `role_name` VARCHAR(100) NOT NULL,
            `job_title` VARCHAR(150) NULL,
            `job_description` TEXT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'Active',
            `created_at` DATETIME NULL, `updated_at` DATETIME NULL,
            PRIMARY KEY (`user_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $db->query("CREATE TABLE IF NOT EXISTS `erp_role_module_permissions` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_type` INT(11) UNSIGNED NOT NULL,
            `module_key` VARCHAR(100) NOT NULL,
            `module_name` VARCHAR(150) NULL,
            `can_view` TINYINT(1) NOT NULL DEFAULT 0,
            `can_add` TINYINT(1) NOT NULL DEFAULT 0,
            `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
            `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NULL, `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`), KEY `k_ut` (`user_type`), KEY `k_mk` (`module_key`),
            UNIQUE KEY `erp_role_module_unique` (`user_type`,`module_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->syncDefaults();
    }

    /** Insert any missing role rows + role×module permission rows (idempotent). */
    public function syncDefaults(): void
    {
        $db  = $this->db();
        $now = date('Y-m-d H:i:s');

        foreach ($this->roleTypes() as $ut => $role) {
            if (! $db->table('erp_user_type_roles')->where('user_type', $ut)->get()->getRow()) {
                $db->table('erp_user_type_roles')->insert(array_merge($role, [
                    'user_type' => $ut, 'status' => 'Active', 'created_at' => $now, 'updated_at' => $now,
                ]));
            }
        }
        foreach ($this->roleTypes() as $ut => $role) {
            foreach ($this->modules() as $key => $name) {
                if (! $db->table('erp_role_module_permissions')->where('user_type', $ut)->where('module_key', $key)->get()->getRow()) {
                    $perm = $this->defaultPermission($ut, $key);
                    $db->table('erp_role_module_permissions')->insert(array_merge($perm, [
                        'user_type' => $ut, 'module_key' => $key, 'module_name' => $name,
                        'created_at' => $now, 'updated_at' => $now,
                    ]));
                }
            }
        }
    }

    public function defaultPermission($userType, $moduleKey): array
    {
        if ((int) $userType === 1) {
            return ['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 1];
        }
        $accountant = ['dashboard', 'invoice', 'uninvoice', 'taxinvoice', 'purchase_module', 'account', 'cd_note', 'delivery_challan', 'payment_receipt', 'stock', 'chat', 'notification', 'report'];
        $manager    = array_merge($accountant, ['document', 'account_name', 'attendance', 'driver_module', 'truck_module', 'cold_lot_system']);
        $viewer     = ['dashboard', 'chat', 'report'];

        if ((int) $userType === 2 && in_array($moduleKey, $manager, true)) {
            return ['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 0];
        }
        if ((int) $userType === 3 && in_array($moduleKey, $accountant, true)) {
            return ['can_view' => 1, 'can_add' => 1, 'can_edit' => 1, 'can_delete' => 0];
        }
        if ((int) $userType === 4 && in_array($moduleKey, $viewer, true)) {
            return ['can_view' => 1, 'can_add' => 0, 'can_edit' => 0, 'can_delete' => 0];
        }
        return ['can_view' => 0, 'can_add' => 0, 'can_edit' => 0, 'can_delete' => 0];
    }

    public function roles(): array
    {
        return $this->db()->table('erp_user_type_roles')->orderBy('user_type', 'asc')->get()->getResult();
    }

    /** matrix[user_type][module_key] = permission row. */
    public function permissionsMatrix(): array
    {
        $rows   = $this->db()->table('erp_role_module_permissions')->orderBy('user_type', 'asc')->orderBy('module_name', 'asc')->get()->getResult();
        $matrix = [];
        foreach ($rows as $row) {
            $matrix[(int) $row->user_type][$row->module_key] = $row;
        }
        return $matrix;
    }

    public function saveRoles($roles): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ($this->roleTypes() as $ut => $default) {
            $in = $roles[$ut] ?? [];
            $this->db()->table('erp_user_type_roles')->where('user_type', $ut)->update([
                'role_name'       => trim($in['role_name'] ?? $default['role_name']),
                'job_title'       => trim($in['job_title'] ?? $default['job_title']),
                'job_description' => trim($in['job_description'] ?? $default['job_description']),
                'status'          => (isset($in['status']) && $in['status'] === 'Inactive') ? 'Inactive' : 'Active',
                'updated_at'      => $now,
            ]);
        }
    }

    public function savePermissions($permissions): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ($this->roleTypes() as $ut => $role) {
            foreach ($this->modules() as $key => $name) {
                $in = $permissions[$ut][$key] ?? [];
                $this->db()->table('erp_role_module_permissions')->where('user_type', $ut)->where('module_key', $key)->update([
                    'module_name' => $name,
                    'can_view'    => isset($in['can_view']) ? 1 : 0,
                    'can_add'     => isset($in['can_add']) ? 1 : 0,
                    'can_edit'    => isset($in['can_edit']) ? 1 : 0,
                    'can_delete'  => isset($in['can_delete']) ? 1 : 0,
                    'updated_at'  => $now,
                ]);
            }
        }
    }
}
