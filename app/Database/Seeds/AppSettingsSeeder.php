<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Idempotent seeder for the "web app settings" feature set. Safe to run
 * repeatedly — it only inserts what is missing and never overwrites values an
 * administrator has already customised.
 *
 *   php spark db:seed AppSettingsSeeder
 *
 * Seeds:
 *   - default global settings (theme, colours, weather, widgets, alert colours)
 *   - the "Settings" sidebar module (auto-added to the permission matrix)
 *   - full grants on Settings for the Admin role (Super Admin bypasses anyway)
 *   - a demo control hierarchy on the seeded users (parent_id)
 */
class AppSettingsSeeder extends Seeder
{
    public function run()
    {
        $now = date('Y-m-d H:i:s');

        // ---------------------------------------------------------------
        // 1. Default global settings (only inserts missing keys)
        // ---------------------------------------------------------------
        $defaults = [
            // Theme & colours
            'theme_mode'                => 'light',      // light | dark
            'sidebar_theme'             => 'dark',       // dark | light
            'primary_color'             => '#0d6efd',
            'accent_color'              => '#6610f2',
            // Weather widget
            'weather_city'              => 'New Delhi',
            'weather_units'             => 'metric',     // metric | imperial
            // Dashboard widgets (JSON map of section => enabled)
            'dashboard_widgets'         => json_encode([
                'stats'         => true,
                'weather'       => true,
                'recent_logins' => true,
                'activity'      => true,
                'notifications' => true,
            ]),
            // Alert / toast / dialog colours
            'toast_success_color'       => '#198754',
            'toast_error_color'         => '#dc3545',
            'toast_warning_color'       => '#ffc107',
            'toast_info_color'          => '#0dcaf0',
            'alert_color'               => '#0d6efd',
            'prompt_color'              => '#6610f2',
            'confirm_color'             => '#fd7e14',
            'sweetalert_confirm_color'  => '#198754',
            'sweetalert_cancel_color'   => '#dc3545',
            // Web push
            'web_push_enabled'          => '1',
            'vapid_public_key'          => '',
            'vapid_private_key'         => '',
            'vapid_subject'             => 'mailto:admin@erp.test',
        ];

        $existing = [];
        foreach ($this->db->table('settings')->select('setting_key')->where('user_id', 0)->get()->getResultArray() as $r) {
            $existing[$r['setting_key']] = true;
        }
        $rows = [];
        foreach ($defaults as $k => $v) {
            if (! isset($existing[$k])) {
                $rows[] = ['user_id' => 0, 'setting_key' => $k, 'setting_value' => $v, 'created_at' => $now, 'updated_at' => $now];
            }
        }
        if ($rows !== []) {
            $this->db->table('settings')->insertBatch($rows);
        }

        // ---------------------------------------------------------------
        // 2. Settings sidebar module (auto-appears in the permission matrix)
        // ---------------------------------------------------------------
        $settingsModule = $this->db->table('modules')->where('code', 'settings')->get()->getRowArray();
        if (! $settingsModule) {
            $this->db->table('modules')->insert([
                'name' => 'Settings', 'code' => 'settings', 'url' => 'settings',
                'icon' => 'bi bi-sliders', 'parent_id' => null, 'sort_order' => 90,
                'is_menu' => 1, 'status' => 1, 'created_at' => $now, 'updated_at' => $now,
            ]);
            $settingsModuleId = (int) $this->db->insertID();
        } else {
            $settingsModuleId = (int) $settingsModule['id'];
        }

        // ---------------------------------------------------------------
        // 3. Grant the Admin role full access to Settings (idempotent)
        // ---------------------------------------------------------------
        $adminRole = $this->db->table('roles')->where('code', 'admin')->get()->getRowArray();
        if ($adminRole) {
            $adminRoleId = (int) $adminRole['id'];
            $permIds = [];
            foreach ($this->db->table('permissions')->select('id, code')->get()->getResultArray() as $p) {
                $permIds[$p['code']] = (int) $p['id'];
            }
            $grantRows = [];
            foreach (['view', 'add', 'edit', 'delete'] as $code) {
                if (! isset($permIds[$code])) {
                    continue;
                }
                $has = $this->db->table('role_permissions')
                    ->where('role_id', $adminRoleId)->where('module_id', $settingsModuleId)
                    ->where('permission_id', $permIds[$code])->countAllResults();
                if ($has === 0) {
                    $grantRows[] = ['role_id' => $adminRoleId, 'module_id' => $settingsModuleId, 'permission_id' => $permIds[$code]];
                }
            }
            if ($grantRows !== []) {
                $this->db->table('role_permissions')->insertBatch($grantRows);
            }
        }

        // ---------------------------------------------------------------
        // 4. Demo control hierarchy on seeded users
        //    superadmin -> admin -> {manager, staff, viewer}
        // ---------------------------------------------------------------
        $byUsername = [];
        foreach ($this->db->table('users')->select('id, username')->get()->getResultArray() as $u) {
            $byUsername[$u['username']] = (int) $u['id'];
        }
        $link = function (string $child, ?string $parent) use ($byUsername) {
            if (isset($byUsername[$child])) {
                $this->db->table('users')->where('id', $byUsername[$child])->update([
                    'parent_id' => $parent !== null && isset($byUsername[$parent]) ? $byUsername[$parent] : null,
                ]);
            }
        };
        $link('admin', 'superadmin');
        $link('manager', 'admin');
        $link('staff', 'admin');
        $link('viewer', 'admin');
    }
}
