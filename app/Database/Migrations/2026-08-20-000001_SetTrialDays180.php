<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Set the free-trial length to 180 days. This is the Super Admin setting
 * `subscription_trial_days` that sub_trial_days() reads; new trials use it and
 * the marketing/landing/subscription pages display it. Idempotent upsert at the
 * global scope (user_id = 0). Existing trials already in progress are unaffected.
 */
class SetTrialDays180 extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $row = $this->db->table('settings')->where('user_id', 0)->where('setting_key', 'subscription_trial_days')->get()->getRowArray();
        if ($row) {
            $this->db->table('settings')->where('id', $row['id'])->update(['setting_value' => '180', 'updated_at' => $now]);
        } else {
            $this->db->table('settings')->insert([
                'user_id' => 0, 'setting_key' => 'subscription_trial_days', 'setting_value' => '180',
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        $this->db->table('settings')->where('user_id', 0)->where('setting_key', 'subscription_trial_days')
            ->update(['setting_value' => '30']);
    }
}
