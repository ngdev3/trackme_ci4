<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Reports & Logs are operator-only. Super Admin bypasses ACL checks via the
 * superadmin filter; all role/user grants for the log modules are removed so
 * they do not appear in non-superadmin menus.
 */
class RestrictLogsToSuperAdmin extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('modules')) {
            return;
        }

        $modules = $this->db->table('modules')
            ->select('id')
            ->whereIn('code', ['activity_logs', 'login_logs'])
            ->get()->getResultArray();

        $moduleIds = array_map(static fn ($row) => (int) $row['id'], $modules);
        if ($moduleIds === []) {
            return;
        }

        if ($this->db->tableExists('role_permissions')) {
            $this->db->table('role_permissions')->whereIn('module_id', $moduleIds)->delete();
        }
        if ($this->db->tableExists('user_permissions')) {
            $this->db->table('user_permissions')->whereIn('module_id', $moduleIds)->delete();
        }
    }

    public function down()
    {
        // Restoring broad log access should be an explicit permissions decision.
    }
}
