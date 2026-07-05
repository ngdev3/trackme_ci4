<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotifications extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'type'        => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'info'],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 180],
            'message'     => ['type' => 'TEXT', 'null' => true],
            'module'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'user_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'role_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'priority'    => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'normal'],
            'action_url'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_read'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'read_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'is_read']);
        $this->forge->addKey(['role_id', 'is_read']);
        $this->forge->addKey(['module', 'is_read']);
        $this->forge->createTable('notifications', true);

        $this->ensureNotificationModule();
    }

    public function down()
    {
        $this->forge->dropTable('notifications', true);
    }

    private function ensureNotificationModule(): void
    {
        $now = date('Y-m-d H:i:s');
        $modules = $this->db->table('modules');
        $logs = $modules->where('code', 'logs')->get()->getRowArray();

        if (! $modules->where('code', 'notifications')->get()->getRowArray()) {
            $modules->insert([
                'name'       => 'Notifications',
                'code'       => 'notifications',
                'url'        => 'notifications',
                'icon'       => 'bi bi-bell',
                'parent_id'  => $logs['id'] ?? null,
                'sort_order' => 3,
                'is_menu'    => 1,
                'status'     => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $module = $modules->where('code', 'notifications')->get()->getRowArray();
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
            foreach (['view', 'delete'] as $action) {
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
}
