<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Master seeder — bootstraps the whole access-control system with demo data.
 *
 * Run:  php spark db:seed DatabaseSeeder
 */
class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---------------------------------------------------------------
        // 1. User Types
        // ---------------------------------------------------------------
        $userTypes = [
            ['name' => 'Super Admin', 'code' => 'super_admin', 'description' => 'Full system owner'],
            ['name' => 'Admin',       'code' => 'admin',       'description' => 'Administrative user'],
            ['name' => 'Manager',     'code' => 'manager',     'description' => 'Department manager'],
            ['name' => 'Staff',       'code' => 'staff',       'description' => 'Operational staff'],
            ['name' => 'Viewer',      'code' => 'viewer',      'description' => 'Read-only user'],
        ];
        foreach ($userTypes as &$t) {
            $t['status'] = 1;
            $t['created_at'] = $now;
            $t['updated_at'] = $now;
        }
        unset($t);
        $this->db->table('user_types')->insertBatch($userTypes);
        $typeIds = $this->keyByCode('user_types');

        // ---------------------------------------------------------------
        // 2. Permission actions
        // ---------------------------------------------------------------
        $actions = ['view', 'add', 'edit', 'delete', 'print', 'export', 'approve'];
        $permRows = [];
        foreach ($actions as $i => $a) {
            $permRows[] = ['name' => ucfirst($a), 'code' => $a, 'sort_order' => $i + 1, 'created_at' => $now, 'updated_at' => $now];
        }
        $this->db->table('permissions')->insertBatch($permRows);
        $permIds = $this->keyByCode('permissions');

        // ---------------------------------------------------------------
        // 3. Roles
        // ---------------------------------------------------------------
        $roles = [
            ['name' => 'Super Admin', 'code' => 'super_admin', 'is_superadmin' => 1, 'description' => 'Unrestricted access'],
            ['name' => 'Admin',       'code' => 'admin',       'is_superadmin' => 0, 'description' => 'Manages most modules'],
            ['name' => 'Manager',     'code' => 'manager',     'is_superadmin' => 0, 'description' => 'Manages users'],
            ['name' => 'Staff',       'code' => 'staff',       'is_superadmin' => 0, 'description' => 'Limited view access'],
            ['name' => 'Viewer',      'code' => 'viewer',      'is_superadmin' => 0, 'description' => 'Read-only'],
        ];
        foreach ($roles as &$r) {
            $r['status'] = 1;
            $r['created_at'] = $now;
            $r['updated_at'] = $now;
        }
        unset($r);
        $this->db->table('roles')->insertBatch($roles);
        $roleIds = $this->keyByCode('roles');

        // ---------------------------------------------------------------
        // 4. Modules (parent-child tree used to build the sidebar)
        // ---------------------------------------------------------------
        $parents = [
            ['name' => 'Dashboard',       'code' => 'dashboard',       'url' => 'dashboard',  'icon' => 'bi bi-speedometer2', 'sort_order' => 1],
            ['name' => 'User Management', 'code' => 'user_management', 'url' => null,          'icon' => 'bi bi-people',       'sort_order' => 2],
            ['name' => 'Access Control',  'code' => 'access_control',  'url' => null,          'icon' => 'bi bi-shield-lock',  'sort_order' => 3],
            ['name' => 'Reports & Logs',  'code' => 'logs',            'url' => null,          'icon' => 'bi bi-clock-history','sort_order' => 4],
        ];
        foreach ($parents as &$p) {
            $p['parent_id'] = null;
            $p['is_menu'] = 1;
            $p['status'] = 1;
            $p['created_at'] = $now;
            $p['updated_at'] = $now;
        }
        unset($p);
        $this->db->table('modules')->insertBatch($parents);
        $modIds = $this->keyByCode('modules');

        $children = [
            ['name' => 'Users',         'code' => 'users',         'url' => 'users',         'icon' => 'bi bi-person',            'parent' => 'user_management', 'sort_order' => 1],
            ['name' => 'User Types',    'code' => 'user_types',    'url' => 'user-types',    'icon' => 'bi bi-person-badge',      'parent' => 'user_management', 'sort_order' => 2],
            ['name' => 'Roles',         'code' => 'roles',         'url' => 'roles',         'icon' => 'bi bi-person-gear',       'parent' => 'user_management', 'sort_order' => 3],
            ['name' => 'Modules',       'code' => 'modules',       'url' => 'modules',       'icon' => 'bi bi-grid-3x3-gap',      'parent' => 'access_control',  'sort_order' => 1],
            ['name' => 'Permissions',   'code' => 'permissions',   'url' => 'permissions',   'icon' => 'bi bi-key',               'parent' => 'access_control',  'sort_order' => 2],
            ['name' => 'Activity Logs', 'code' => 'activity_logs', 'url' => 'activity-logs', 'icon' => 'bi bi-list-check',        'parent' => 'logs',            'sort_order' => 1],
            ['name' => 'Login Logs',    'code' => 'login_logs',    'url' => 'login-logs',    'icon' => 'bi bi-box-arrow-in-right','parent' => 'logs',            'sort_order' => 2],
            ['name' => 'Notifications', 'code' => 'notifications', 'url' => 'notifications', 'icon' => 'bi bi-bell',              'parent' => 'logs',            'sort_order' => 3],
        ];
        $childRows = [];
        foreach ($children as $c) {
            $childRows[] = [
                'name' => $c['name'], 'code' => $c['code'], 'url' => $c['url'], 'icon' => $c['icon'],
                'parent_id' => $modIds[$c['parent']], 'sort_order' => $c['sort_order'],
                'is_menu' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
            ];
        }
        $this->db->table('modules')->insertBatch($childRows);
        $modIds = $this->keyByCode('modules'); // refresh with children

        // ---------------------------------------------------------------
        // 5. Role → Module → Permission grants
        // ---------------------------------------------------------------
        // Helper closures capture $roleIds/$modIds/$permIds/$now.
        $grants = [];
        $grant = static function (string $role, string $module, array $acts) use (&$grants, $roleIds, $modIds, $permIds) {
            foreach ($acts as $a) {
                $grants[] = [
                    'role_id'       => $roleIds[$role],
                    'module_id'     => $modIds[$module],
                    'permission_id' => $permIds[$a],
                ];
            }
        };
        $all = ['view', 'add', 'edit', 'delete', 'print', 'export', 'approve'];

        // Admin — full access to most modules, but NOT the Access Control group.
        foreach (['dashboard', 'users', 'user_types', 'roles', 'activity_logs', 'login_logs', 'notifications'] as $m) {
            $grant('admin', $m, $all);
        }
        // Manager — manage users, view supporting masters.
        $grant('manager', 'dashboard', ['view']);
        $grant('manager', 'users', ['view', 'add', 'edit', 'export', 'print']);
        $grant('manager', 'user_types', ['view']);
        $grant('manager', 'roles', ['view']);
        // Staff — view only on assigned modules.
        $grant('staff', 'dashboard', ['view']);
        $grant('staff', 'users', ['view']);
        // Viewer — read-only on dashboard + users.
        $grant('viewer', 'dashboard', ['view']);
        $grant('viewer', 'users', ['view']);

        if ($grants !== []) {
            $this->db->table('role_permissions')->insertBatch($grants);
        }

        // ---------------------------------------------------------------
        // 6. Demo users
        // ---------------------------------------------------------------
        $users = [
            ['name' => 'Super Administrator', 'email' => 'superadmin@erp.test', 'username' => 'superadmin', 'mobile' => '9000000001', 'type' => 'super_admin', 'role' => 'super_admin', 'pass' => 'Admin@123'],
            ['name' => 'System Admin',        'email' => 'admin@erp.test',      'username' => 'admin',      'mobile' => '9000000002', 'type' => 'admin',       'role' => 'admin',       'pass' => 'Admin@123'],
            ['name' => 'Mike Manager',        'email' => 'manager@erp.test',    'username' => 'manager',    'mobile' => '9000000003', 'type' => 'manager',     'role' => 'manager',     'pass' => 'Admin@123'],
            ['name' => 'Sara Staff',          'email' => 'staff@erp.test',      'username' => 'staff',      'mobile' => '9000000004', 'type' => 'staff',       'role' => 'staff',       'pass' => 'Admin@123'],
            ['name' => 'Victor Viewer',       'email' => 'viewer@erp.test',     'username' => 'viewer',     'mobile' => '9000000005', 'type' => 'viewer',      'role' => 'viewer',      'pass' => 'Admin@123'],
        ];
        foreach ($users as $u) {
            $this->db->table('users')->insert([
                'name'         => $u['name'],
                'email'        => $u['email'],
                'mobile'       => $u['mobile'],
                'username'     => $u['username'],
                'password'     => password_hash($u['pass'], PASSWORD_DEFAULT),
                'user_type_id' => $typeIds[$u['type']],
                'status'       => 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ]);
            $userId = $this->db->insertID();
            $this->db->table('user_roles')->insert([
                'user_id' => $userId,
                'role_id' => $roleIds[$u['role']],
            ]);
        }
    }

    /**
     * Fetch a code=>id map for a table.
     */
    private function keyByCode(string $table): array
    {
        $map = [];
        foreach ($this->db->table($table)->select('id, code')->get()->getResultArray() as $row) {
            $map[$row['code']] = (int) $row['id'];
        }
        return $map;
    }
}
