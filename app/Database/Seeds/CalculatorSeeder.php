<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Registers the Calculator sidebar module and grants the Admin role access.
 * Idempotent — safe to run repeatedly.
 *
 *   php spark db:seed CalculatorSeeder
 */
class CalculatorSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Sidebar module (auto-appears in the permission matrix).
        $module = $this->db->table('modules')->where('code', 'calculator')->get()->getRowArray();
        if (! $module) {
            $this->db->table('modules')->insert([
                'name' => 'Calculator', 'code' => 'calculator', 'url' => 'calculator',
                'icon' => 'bi bi-calculator', 'parent_id' => null, 'sort_order' => 80,
                'is_menu' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $moduleId = (int) $this->db->insertID();
        } else {
            $moduleId = (int) $module['id'];
        }

        // 2. Grant the Admin role full access (Super Admin bypasses checks).
        $adminRole = $this->db->table('roles')->where('code', 'admin')->get()->getRowArray();
        if ($adminRole) {
            $adminRoleId = (int) $adminRole['id'];
            $permIds = [];
            foreach ($this->db->table('permissions')->select('id, code')->get()->getResultArray() as $p) {
                $permIds[$p['code']] = (int) $p['id'];
            }
            $rows = [];
            foreach (['view', 'add', 'edit', 'delete'] as $code) {
                if (! isset($permIds[$code])) {
                    continue;
                }
                $has = $this->db->table('role_permissions')
                    ->where('role_id', $adminRoleId)->where('module_id', $moduleId)
                    ->where('permission_id', $permIds[$code])->countAllResults();
                if ($has === 0) {
                    $rows[] = ['role_id' => $adminRoleId, 'module_id' => $moduleId, 'permission_id' => $permIds[$code]];
                }
            }
            if ($rows !== []) {
                $this->db->table('role_permissions')->insertBatch($rows);
            }
        }
    }
}
