<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Registers the "Mobile API Monitor" as a sidebar module. It is SUPER ADMIN
 * ONLY: no role_permissions are granted, and menu_helper's $superOnly map hides
 * the code 'api_monitor' from every non-super sidebar. The route group is also
 * gated by the `superadmin` filter, so access is blocked three ways. Idempotent.
 *
 *   php spark db:seed ApiMonitorSeeder
 */
class ApiMonitorSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        $row = [
            'name'       => 'Mobile API Monitor',
            'code'       => 'api_monitor',
            'url'        => 'api-monitor',
            'icon'       => 'bi bi-phone-vibrate',
            'parent_id'  => null,
            'sort_order' => 95,
            'is_menu'    => 1,
            'status'     => 1,
            'updated_at' => $now,
        ];

        $existing = $this->db->table('modules')->where('code', 'api_monitor')->get()->getRowArray();
        if ($existing) {
            $this->db->table('modules')->where('id', $existing['id'])->update($row);
        } else {
            $this->db->table('modules')->insert($row + ['created_at' => $now]);
        }
    }
}
