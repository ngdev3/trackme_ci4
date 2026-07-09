<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Registers the Transactions module as a sidebar group with two children —
 * Rokad Parcha (daily register, /transactions) and Rokad Vahi (ledger,
 * /transactions/list) — and grants the Admin + Viewer roles access. Super
 * Admin bypasses. Idempotent.
 *
 *   php spark db:seed TransactionsSeeder
 */
class TransactionsSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---- Parent group: "Transactions" (no URL of its own; it's a header) ----
        $parent = $this->db->table('modules')->where('code', 'transactions')->get()->getRowArray();
        if (! $parent) {
            $this->db->table('modules')->insert([
                'name' => 'Hisaab Kitaab Vahi', 'code' => 'transactions', 'url' => null,
                'icon' => 'bi bi-cash-stack', 'parent_id' => null, 'sort_order' => 75,
                'is_menu' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $parentId = (int) $this->db->insertID();
        } else {
            $parentId = (int) $parent['id'];
            // Convert the old single link into a group header.
            $this->db->table('modules')->where('id', $parentId)->update([
                'name' => 'Hisaab Kitaab Vahi', 'url' => null, 'icon' => 'bi bi-cash-stack', 'updated_at' => $now,
            ]);
        }

        // ---- Children: Rokad Parcha + Rokad Vahi ----
        $children = [
            ['name' => 'Rokadh Parcha',    'code' => 'rokad_parcha',  'url' => 'transactions',         'icon' => 'bi bi-journal-text', 'sort_order' => 1],
            ['name' => 'Rokad Vahi',       'code' => 'rokad_vahi',    'url' => 'transactions/list',    'icon' => 'bi bi-table',        'sort_order' => 2],
            ['name' => 'Account Statement','code' => 'account_statement', 'url' => 'transactions/statement', 'icon' => 'bi bi-person-vcard', 'sort_order' => 3],
            ['name' => 'Shri Rokad Nagad', 'code' => 'rokad_opening', 'url' => 'transactions/opening', 'icon' => 'bi bi-cash-stack',   'sort_order' => 4],
        ];
        $childIds = [];
        foreach ($children as $c) {
            $existing = $this->db->table('modules')->where('code', $c['code'])->get()->getRowArray();
            if (! $existing) {
                $this->db->table('modules')->insert($c + [
                    'parent_id' => $parentId, 'is_menu' => 1, 'status' => 1,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
                $childIds[$c['code']] = (int) $this->db->insertID();
            } else {
                $childIds[$c['code']] = (int) $existing['id'];
                $this->db->table('modules')->where('id', $childIds[$c['code']])->update([
                    'name' => $c['name'], 'url' => $c['url'], 'icon' => $c['icon'],
                    'parent_id' => $parentId, 'sort_order' => $c['sort_order'], 'is_menu' => 1, 'status' => 1,
                    'updated_at' => $now,
                ]);
            }
        }

        // ---- Permission ids by code ----
        $permIds = [];
        foreach ($this->db->table('permissions')->select('id, code')->get()->getResultArray() as $p) {
            $permIds[$p['code']] = (int) $p['id'];
        }

        // Functional permissions live on the parent (routes gate on `transactions`);
        // the children get `view` so they appear in the menu for those roles.
        $grants = [
            $parentId                    => ['view', 'add', 'edit', 'delete', 'print', 'export'],
            $childIds['rokad_parcha']      => ['view'],
            $childIds['rokad_vahi']        => ['view'],
            $childIds['account_statement'] => ['view'],
            $childIds['rokad_opening']     => ['view'],
        ];

        foreach (['admin', 'viewer'] as $roleCode) {
            $role = $this->db->table('roles')->where('code', $roleCode)->get()->getRowArray();
            if (! $role) {
                continue;
            }
            $roleId = (int) $role['id'];

            foreach ($grants as $moduleId => $actions) {
                $rows = [];
                foreach ($actions as $a) {
                    if (! isset($permIds[$a])) {
                        continue;
                    }
                    $has = $this->db->table('role_permissions')
                        ->where('role_id', $roleId)->where('module_id', $moduleId)->where('permission_id', $permIds[$a])
                        ->countAllResults();
                    if ($has === 0) {
                        $rows[] = ['role_id' => $roleId, 'module_id' => $moduleId, 'permission_id' => $permIds[$a]];
                    }
                }
                if ($rows !== []) {
                    $this->db->table('role_permissions')->insertBatch($rows);
                }
            }
        }
    }
}
