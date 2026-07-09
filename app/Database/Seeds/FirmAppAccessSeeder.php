<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Grants the base 'viewer' role (assigned to every customer and firm user) the
 * app-level permissions needed to reach the personal / shared modules that use
 * app RBAC — dashboard, notes and reminders. Firm-specific modules (accounting,
 * etc.) are governed separately by firm roles (firm_can / firmperm). Idempotent.
 */
class FirmAppAccessSeeder extends Seeder
{
    public function run()
    {
        $role = $this->db->table('roles')->where('code', 'viewer')->get()->getRowArray();
        if (! $role) {
            return;
        }
        $roleId = (int) $role['id'];

        $perms = [];
        foreach ($this->db->table('permissions')->select('id, code')->get()->getResultArray() as $p) {
            $perms[$p['code']] = (int) $p['id'];
        }

        $grants = [
            'dashboard' => ['view'],
            'notes'     => ['view', 'add', 'edit', 'delete'],
            'reminders' => ['view', 'add', 'edit', 'delete'],
        ];

        foreach ($grants as $moduleCode => $actions) {
            $module = $this->db->table('modules')->where('code', $moduleCode)->get()->getRowArray();
            if (! $module) {
                continue;
            }
            $moduleId = (int) $module['id'];
            $rows = [];
            foreach ($actions as $a) {
                if (! isset($perms[$a])) {
                    continue;
                }
                $has = $this->db->table('role_permissions')
                    ->where('role_id', $roleId)->where('module_id', $moduleId)->where('permission_id', $perms[$a])
                    ->countAllResults();
                if ($has === 0) {
                    $rows[] = ['role_id' => $roleId, 'module_id' => $moduleId, 'permission_id' => $perms[$a]];
                }
            }
            if ($rows !== []) {
                $this->db->table('role_permissions')->insertBatch($rows);
            }
        }
    }
}
