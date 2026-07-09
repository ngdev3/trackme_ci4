<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Brand the product as "HissabKitaab" and register the Help & Support module.
 *
 * - Sets the global app_name / app_url / tagline and the support contact details
 *   (email + WhatsApp) used by the header, footer and the Help page. Stored as
 *   global settings (user_id = 0) so they can be changed later from Settings.
 * - Registers the `help` module so it appears in the Super-Admin sidebar. Help
 *   itself is open to every signed-in user (its routes use the `auth` filter,
 *   not a module permission), so no role grants are needed.
 *
 * Idempotent.
 */
class BrandingAndHelpModule extends Migration
{
    public function up()
    {
        // ---- 1. Branding + support settings (global scope) ----
        $settings = [
            'app_name'                 => 'HissabKitaab',
            'app_url'                  => 'HissabKitaab.com',
            'app_tagline'              => 'plan smarter, grow faster',
            'support_email'            => 'admin@HissabKitaab.com',
            'support_whatsapp'         => '916393505070',
            'support_whatsapp_display' => '+91 63935 05070',
        ];
        foreach ($settings as $key => $value) {
            $existing = $this->db->table('settings')->where('user_id', 0)->where('setting_key', $key)->get()->getRowArray();
            if ($existing) {
                $this->db->table('settings')->where('id', $existing['id'])->update(['setting_value' => $value]);
            } else {
                $this->db->table('settings')->insert([
                    'user_id' => 0, 'setting_key' => $key, 'setting_value' => $value,
                    'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
        }

        // ---- 2. Register the Help & Support module ----
        if ($this->db->tableExists('modules')
            && $this->db->table('modules')->where('code', 'help')->countAllResults() === 0) {
            $maxSort = (int) ($this->db->table('modules')->selectMax('sort_order')->get()->getRowArray()['sort_order'] ?? 0);
            $this->db->table('modules')->insert([
                'name'       => 'Help & Support',
                'code'       => 'help',
                'url'        => 'help',
                'icon'       => 'bi bi-life-preserver',
                'parent_id'  => null,
                'sort_order' => $maxSort + 1,
                'is_menu'    => 1,
                'status'     => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    public function down()
    {
        $this->db->table('modules')->where('code', 'help')->delete();
        $this->db->table('settings')->where('user_id', 0)
            ->whereIn('setting_key', ['app_url', 'app_tagline', 'support_email', 'support_whatsapp', 'support_whatsapp_display'])
            ->delete();
        // Leave app_name in place (reverting it to a code default is not meaningful).
    }
}
