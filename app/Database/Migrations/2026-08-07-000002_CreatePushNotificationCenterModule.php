<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePushNotificationCenterModule extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $modules = $this->db->table('modules');
        $existing = $modules->where('code', 'push_notifications')->get()->getRowArray();
        $data = [
            'name'       => 'Push Notification Center',
            'code'       => 'push_notifications',
            'url'        => 'push-notifications',
            'icon'       => 'bi bi-send',
            'parent_id'  => null,
            'sort_order' => 8,
            'is_menu'    => 1,
            'status'     => 1,
            'updated_at' => $now,
            'deleted_at' => null,
        ];

        if ($existing) {
            $modules->where('id', (int) $existing['id'])->update($data);
        } else {
            $modules->insert($data + ['created_at' => $now]);
        }

        $module = $modules->where('code', 'push_notifications')->get()->getRowArray();
        if (! $module) {
            return;
        }

        $permRows = $this->db->table('permissions')->select('id, code')->get()->getResultArray();
        $perms = [];
        foreach ($permRows as $row) {
            $perms[$row['code']] = (int) $row['id'];
        }

        $roles = $this->db->table('roles')
            ->select('id, code, is_superadmin')
            ->groupStart()
                ->where('is_superadmin', 1)
                ->orWhere('code', 'admin')
            ->groupEnd()
            ->get()->getResultArray();

        $grants = [];
        foreach ($roles as $role) {
            foreach (['view', 'add'] as $action) {
                if (! isset($perms[$action])) {
                    continue;
                }
                $exists = $this->db->table('role_permissions')
                    ->where('role_id', (int) $role['id'])
                    ->where('module_id', (int) $module['id'])
                    ->where('permission_id', $perms[$action])
                    ->countAllResults();
                if (! $exists) {
                    $grants[] = [
                        'role_id'       => (int) $role['id'],
                        'module_id'     => (int) $module['id'],
                        'permission_id' => $perms[$action],
                    ];
                }
            }
        }

        if ($grants !== []) {
            $this->db->table('role_permissions')->insertBatch($grants);
        }
    }

    public function down()
    {
        $module = $this->db->table('modules')->where('code', 'push_notifications')->get()->getRowArray();
        if (! $module) {
            return;
        }

        $this->db->table('role_permissions')->where('module_id', (int) $module['id'])->delete();
        $this->db->table('modules')->where('id', (int) $module['id'])->delete();
    }
}
