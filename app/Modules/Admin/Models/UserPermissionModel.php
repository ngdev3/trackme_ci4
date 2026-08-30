<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * UserPermissionModel — CI4 port of admin/models/User_permission_mod.
 * Per-user module overrides (erp_user_module_permissions) that supersede the
 * role defaults, plus per-user admin actions (status, template, force-password,
 * mobile app_access, password). Super admins are never modified here. Shares the
 * can_view/add/edit/delete columns with the role table so the gate logic is common.
 */
class UserPermissionModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Lazily create the per-user override table + the users.app_access column. */
    public function ensureTable(): void
    {
        $this->db()->query("CREATE TABLE IF NOT EXISTS `erp_user_module_permissions` (
            `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            `user_id` INT(11) UNSIGNED NOT NULL,
            `module_key` VARCHAR(100) NOT NULL,
            `module_name` VARCHAR(150) NULL,
            `can_view` TINYINT(1) NOT NULL DEFAULT 0,
            `can_add` TINYINT(1) NOT NULL DEFAULT 0,
            `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
            `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
            `created_at` DATETIME NULL, `updated_at` DATETIME NULL,
            PRIMARY KEY (`id`), KEY `k_uid` (`user_id`),
            UNIQUE KEY `erp_user_module_unique` (`user_id`,`module_key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
        $this->ensureAppAccessColumn();
    }

    public function ensureAppAccessColumn(): void
    {
        if (! $this->db()->fieldExists('app_access', 'users')) {
            $this->db()->query("ALTER TABLE `users` ADD `app_access` TINYINT(1) NOT NULL DEFAULT 1");
        }
    }

    public function setAppAccess(int $userId, $flag): int
    {
        $this->ensureAppAccessColumn();
        $flag = $flag ? 1 : 0;
        $this->db()->table('users')->where('id', $userId)->update(['app_access' => $flag, 'updated_date' => date('Y-m-d')]);
        return $flag;
    }

    public function modules(): array
    {
        return function_exists('erp_module_registry') ? erp_module_registry() : [];
    }

    /** Users a super admin can configure (everyone except super admins + deleted). */
    public function manageableUsers(): array
    {
        return $this->db()->table('users')
            ->select('id, first_name, last_name, email, mobile, user_type, isSuperAdmin, status')
            ->where('status !=', 'Delete')
            ->where('(isSuperAdmin IS NULL OR isSuperAdmin != 1)', null, false)
            ->orderBy('first_name', 'asc')
            ->get()->getResult();
    }

    public function getUser(int $userId)
    {
        return $this->db()->table('users')->where('id', $userId)->get()->getRow();
    }

    public function isSuperAdminUser($user): bool
    {
        return $user ? ((int) ($user->isSuperAdmin ?? 0) === 1) : false;
    }

    /** Per-user override rows keyed by module_key. */
    public function permissionsForUser(int $userId): array
    {
        $map = [];
        foreach ($this->db()->table('erp_user_module_permissions')->where('user_id', $userId)->get()->getResult() as $row) {
            $map[$row->module_key] = $row;
        }
        return $map;
    }

    public function hasConfig(int $userId): bool
    {
        return $this->db()->table('erp_user_module_permissions')->where('user_id', $userId)->countAllResults() > 0;
    }

    /** Save module visibility for a user (checkbox array module_key=>1). Enabling grants full CRUD. */
    public function saveForUser(int $userId, $modulesInput): void
    {
        if (! $userId) { return; }
        $modulesInput = is_array($modulesInput) ? $modulesInput : [];
        $now = date('Y-m-d H:i:s');

        foreach ($this->modules() as $key => $name) {
            $enabled = ! empty($modulesInput[$key]) ? 1 : 0;
            $data = [
                'module_name' => $name,
                'can_view' => $enabled, 'can_add' => $enabled, 'can_edit' => $enabled, 'can_delete' => $enabled,
                'updated_at' => $now,
            ];
            $exists = $this->db()->table('erp_user_module_permissions')->where('user_id', $userId)->where('module_key', $key)->get()->getRow();
            if ($exists) {
                $this->db()->table('erp_user_module_permissions')->where('user_id', $userId)->where('module_key', $key)->update($data);
            } else {
                $data['user_id'] = $userId; $data['module_key'] = $key; $data['created_at'] = $now;
                $this->db()->table('erp_user_module_permissions')->insert($data);
            }
        }
    }

    /** Drop all overrides for a user (revert to role defaults). */
    public function resetUser(int $userId): void
    {
        $this->db()->table('erp_user_module_permissions')->where('user_id', $userId)->delete();
    }

    /** Active templates (firm/FY) to assign as default_firm. */
    public function templates(): array
    {
        return $this->db()->table('aa_template atp')
            ->select('atp.template_id, atp.template_name, atp.FY, atp.track_name, atp.product_type, frn.name as firm_name')
            ->join('firm_name frn', 'frn.id = atp.firm_name_id', 'left')
            ->where('atp.status', 'Active')
            ->orderBy('frn.name', 'asc')
            ->get()->getResult();
    }

    public function userTemplate($user)
    {
        if (! $user || empty($user->default_firm)) { return null; }
        return $this->db()->table('aa_template atp')
            ->select('atp.template_id, atp.template_name, atp.FY, atp.track_name, frn.name as firm_name')
            ->join('firm_name frn', 'frn.id = atp.firm_name_id', 'left')
            ->where('atp.template_id', (int) $user->default_firm)
            ->get()->getRow();
    }

    public function setStatus(int $userId, $status): string
    {
        $status = $status === 'Active' ? 'Active' : 'Inactive';
        $this->db()->table('users')->where('id', $userId)
            ->where('(isSuperAdmin IS NULL OR isSuperAdmin != 1)', null, false)
            ->update(['status' => $status, 'updated_date' => date('Y-m-d')]);
        return $status;
    }

    public function setTemplate(int $userId, $templateId): bool
    {
        if (! $this->isValidTemplate($templateId)) { return false; }
        $this->db()->table('users')->where('id', $userId)->update(['default_firm' => (int) $templateId, 'updated_date' => date('Y-m-d')]);
        return true;
    }

    public function validUserTypes(): array
    {
        $map = [];
        foreach ($this->db()->table('erp_user_type_roles')->where('status', 'Active')->get()->getResult() as $r) {
            $map[(int) $r->user_type] = $r;
        }
        return $map;
    }

    public function isValidTemplate($templateId): bool
    {
        return $this->db()->table('aa_template')->where('template_id', (int) $templateId)->where('status', 'Active')->countAllResults() > 0;
    }

    public function emailExists(string $email, int $excludeId): bool
    {
        return $this->db()->table('users')->where('email', $email)->where('id !=', $excludeId)->where('status !=', 'Delete')->countAllResults() > 0;
    }

    /** Whitelisted profile update; never touches super admins, password, or isSuperAdmin. */
    public function updateProfile(int $userId, array $data): void
    {
        $allowed = ['first_name', 'last_name', 'email', 'mobile', 'user_type', 'status', 'default_firm', 'remark'];
        $clean   = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $data)) { $clean[$f] = $data[$f]; }
        }
        if (empty($clean)) { return; }
        $clean['updated_date'] = date('Y-m-d');
        $this->db()->table('users')->where('id', $userId)
            ->where('(isSuperAdmin IS NULL OR isSuperAdmin != 1)', null, false)
            ->update($clean);
    }

    public function setForcePasswordChange(int $userId, $flag): int
    {
        $flag = $flag ? 1 : 0;
        $this->db()->table('users')->where('id', $userId)
            ->where('(isSuperAdmin IS NULL OR isSuperAdmin != 1)', null, false)
            ->update(['is_reuired_to_change_password' => $flag, 'updated_date' => date('Y-m-d')]);
        return $flag;
    }

    /** New password: password=md5(plain), remark=plain (matches the app convention). */
    public function setPassword(int $userId, string $plain): bool
    {
        $this->db()->table('users')->where('id', $userId)
            ->where('(isSuperAdmin IS NULL OR isSuperAdmin != 1)', null, false)
            ->update(['password' => md5($plain), 'remark' => $plain, 'updated_date' => date('Y-m-d')]);
        return true;
    }
}
