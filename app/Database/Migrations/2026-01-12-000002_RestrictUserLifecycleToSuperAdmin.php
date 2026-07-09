<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * User lifecycle operations are reserved for Super Admin. Regular roles/users
 * may still receive users:view where appropriate, but not add/edit/delete.
 */
class RestrictUserLifecycleToSuperAdmin extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('modules') || ! $this->db->tableExists('permissions')) {
            return;
        }

        $usersModule = $this->db->table('modules')
            ->select('id')
            ->where('code', 'users')
            ->get()->getRowArray();

        if (! $usersModule) {
            return;
        }

        $permissions = $this->db->table('permissions')
            ->select('id')
            ->whereIn('code', ['add', 'edit', 'delete'])
            ->get()->getResultArray();

        $permissionIds = array_map(static fn ($row) => (int) $row['id'], $permissions);
        if ($permissionIds === []) {
            return;
        }

        if ($this->db->tableExists('role_permissions')) {
            $this->db->table('role_permissions')
                ->where('module_id', (int) $usersModule['id'])
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        if ($this->db->tableExists('user_permissions')) {
            $this->db->table('user_permissions')
                ->where('module_id', (int) $usersModule['id'])
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }
    }

    public function down()
    {
        // Restoring user lifecycle access should be an explicit Super Admin decision.
    }
}
