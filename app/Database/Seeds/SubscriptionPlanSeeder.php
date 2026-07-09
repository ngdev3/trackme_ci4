<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Seeds default SaaS subscription plans. Idempotent.
 *   php spark db:seed SubscriptionPlanSeeder
 */
class SubscriptionPlanSeeder extends Seeder
{
    public function run()
    {
        $now   = date('Y-m-d H:i:s');
        $plans = [
            ['name' => 'Free',       'code' => 'free',       'price' => 0,    'billing_cycle' => 'lifetime', 'max_firms' => 1,    'max_users' => 3,    'features' => 'Single firm, up to 3 users'],
            ['name' => 'Starter',    'code' => 'starter',    'price' => 499,  'billing_cycle' => 'monthly',  'max_firms' => 3,    'max_users' => 10,   'features' => 'Up to 3 firms, 10 users'],
            ['name' => 'Business',   'code' => 'business',   'price' => 1499, 'billing_cycle' => 'monthly',  'max_firms' => 10,   'max_users' => 50,   'features' => 'Up to 10 firms, 50 users'],
            ['name' => 'Enterprise', 'code' => 'enterprise', 'price' => 4999, 'billing_cycle' => 'monthly',  'max_firms' => null, 'max_users' => null, 'features' => 'Unlimited firms and users'],
        ];

        foreach ($plans as $p) {
            $exists = $this->db->table('subscription_plans')->where('code', $p['code'])->countAllResults();
            if ($exists === 0) {
                $this->db->table('subscription_plans')->insert($p + ['status' => 1, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }
}
