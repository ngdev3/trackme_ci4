<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Out-of-box subscription setup: a permanent Free plan and the ₹299 / year paid
 * package, plus the default free-trial length (30 days). Idempotent — the Super
 * Admin can rename, re-price, add or remove plans afterwards from the Plans page.
 */
class SeedSubscriptionDefaults extends Migration
{
    public function up()
    {
        $now = date('Y-m-d H:i:s');

        $plans = [
            ['name' => 'Free', 'code' => 'free', 'price' => 0.00, 'billing_cycle' => 'lifetime',
                'max_firms' => 1, 'max_users' => 3, 'features' => 'Basic cash book. No PDF/print, reports, attachments or opening balance.', 'status' => 1],
            ['name' => 'Yearly', 'code' => 'yearly_299', 'price' => 299.00, 'billing_cycle' => 'yearly',
                'max_firms' => null, 'max_users' => null, 'features' => 'Everything: Rokadh Parcha PDF/print, reports, attachments, statement download, opening balance.', 'status' => 1],
        ];

        foreach ($plans as $p) {
            $exists = $this->db->table('subscription_plans')->where('code', $p['code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('subscription_plans')->insert($p + ['created_at' => $now, 'updated_at' => $now]);
            }
        }

        // Default trial length (days) — a global setting the Super Admin can change.
        if ($this->db->tableExists('settings')) {
            $has = $this->db->table('settings')
                ->where('setting_key', 'subscription_trial_days')->where('user_id', 0)->countAllResults();
            if ($has === 0) {
                $this->db->table('settings')->insert([
                    'setting_key' => 'subscription_trial_days', 'setting_value' => '30', 'user_id' => 0,
                    'created_at' => $now, 'updated_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        $this->db->table('subscription_plans')->whereIn('code', ['free', 'yearly_299'])->delete();
        if ($this->db->tableExists('settings')) {
            $this->db->table('settings')->where('setting_key', 'subscription_trial_days')->where('user_id', 0)->delete();
        }
    }
}
