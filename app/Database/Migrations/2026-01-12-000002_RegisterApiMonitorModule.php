<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Registers the "Mobile API Monitor" sidebar module (code `api_monitor`) as a
 * MIGRATION rather than only a seeder.
 *
 * Why a migration: on production (Hostinger), the AutoSetup filter auto-runs
 * pending migrations on the first request, but only runs DatabaseSeeder on a
 * fresh/empty DB. An existing production DB therefore never picks up seeder-only
 * rows — so the sidebar link stayed invisible online. Doing the registration
 * here makes it apply automatically on the next request after deploy.
 *
 * Idempotent: inserts the row only if `code = 'api_monitor'` is absent, else
 * refreshes its display fields. Mirrors App\Database\Seeds\ApiMonitorSeeder.
 * SUPER-ADMIN-ONLY visibility is enforced elsewhere (superadmin route filter +
 * menu_helper $superOnly), so NO role_permissions are granted here.
 */
class RegisterApiMonitorModule extends Migration
{
    public function up()
    {
        // The `modules` table is part of the baseline schema; guard anyway so a
        // partially-built DB never fatals the AutoSetup request.
        if (! $this->db->tableExists('modules')) {
            return;
        }

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

    public function down()
    {
        if ($this->db->tableExists('modules')) {
            $this->db->table('modules')->where('code', 'api_monitor')->delete();
        }
    }
}
