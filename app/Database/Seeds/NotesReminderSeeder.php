<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Registers the "Notes & Reminders" sidebar group with two leaf modules (notes,
 * reminders) and grants the Admin role full access. Idempotent.
 *
 *   php spark db:seed NotesReminderSeeder
 */
class NotesReminderSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // 1. Parent menu group (no URL of its own).
        $parentId = $this->ensureModule([
            'name' => 'Notes & Reminders', 'code' => 'notes_reminders', 'url' => null,
            'icon' => 'bi bi-journal-text', 'parent_id' => null, 'sort_order' => 70,
            'is_menu' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // 2. Leaf modules.
        $noteId = $this->ensureModule([
            'name' => 'Notes', 'code' => 'notes', 'url' => 'notes',
            'icon' => 'bi bi-sticky', 'parent_id' => $parentId, 'sort_order' => 1,
            'is_menu' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);
        $remId = $this->ensureModule([
            'name' => 'Reminders', 'code' => 'reminders', 'url' => 'reminders',
            'icon' => 'bi bi-alarm', 'parent_id' => $parentId, 'sort_order' => 2,
            'is_menu' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
        ]);

        // 3. Grant the Admin role full access to both leaves.
        $this->grantAdmin($noteId);
        $this->grantAdmin($remId);
    }

    private function ensureModule(array $data): int
    {
        $existing = $this->db->table('modules')->where('code', $data['code'])->get()->getRowArray();
        if ($existing) {
            return (int) $existing['id'];
        }
        $this->db->table('modules')->insert($data);
        return (int) $this->db->insertID();
    }

    private function grantAdmin(int $moduleId): void
    {
        $adminRole = $this->db->table('roles')->where('code', 'admin')->get()->getRowArray();
        if (! $adminRole) {
            return;
        }
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
