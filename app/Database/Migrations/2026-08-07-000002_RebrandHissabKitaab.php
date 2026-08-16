<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rebrand the product to "Hissab-Kitaab" with the tagline
 * "Har Len-Den Ka Sahi Hisaab". Updates the global settings that
 * brand_name()/brand_tagline() read. Domain/email keep the (lowercase,
 * non-hyphenated) "hissabkitaab.com" — only the display NAME is hyphenated.
 *
 * Idempotent: upserts each setting at the global scope (user_id = 0).
 */
class RebrandHissabKitaab extends Migration
{
    public function up()
    {
        $settings = [
            'app_name'      => 'Hissab-Kitaab',
            'app_tagline'   => 'Har Len-Den Ka Sahi Hisaab',
            'app_url'       => 'hissabkitaab.com',           // normalize casing; no hyphen
            'support_email' => 'admin@hissabkitaab.com',     // normalize casing; no hyphen
        ];
        $now = date('Y-m-d H:i:s');
        foreach ($settings as $key => $value) {
            $existing = $this->db->table('settings')->where('user_id', 0)->where('setting_key', $key)->get()->getRowArray();
            if ($existing) {
                $this->db->table('settings')->where('id', $existing['id'])->update(['setting_value' => $value, 'updated_at' => $now]);
            } else {
                $this->db->table('settings')->insert([
                    'user_id' => 0, 'setting_key' => $key, 'setting_value' => $value,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        // Restore the previous branding values.
        $revert = [
            'app_name'      => 'HissabKitaab',
            'app_tagline'   => 'plan smarter, grow faster',
            'app_url'       => 'HissabKitaab.com',
            'support_email' => 'admin@HissabKitaab.com',
        ];
        foreach ($revert as $key => $value) {
            $this->db->table('settings')->where('user_id', 0)->where('setting_key', $key)
                ->update(['setting_value' => $value]);
        }
    }
}
