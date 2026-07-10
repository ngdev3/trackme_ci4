<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Set the paid packages to four one-year tiers: ₹299 / ₹399 / ₹499 / ₹599.
 * Retires the earlier monthly sample plans (they stay in the table but hidden).
 * Idempotent — keyed by plan code. The Super Admin can re-price / rename later.
 */
class YearlyPlanTiers extends Migration
{
    private array $tiers = [
        ['code' => 'yearly_299', 'name' => 'Basic',    'price' => 299.00],
        ['code' => 'yearly_399', 'name' => 'Standard', 'price' => 399.00],
        ['code' => 'yearly_499', 'name' => 'Plus',     'price' => 499.00],
        ['code' => 'yearly_599', 'name' => 'Premium',  'price' => 599.00],
    ];

    public function up()
    {
        $now = date('Y-m-d H:i:s');
        $t   = $this->db->table('subscription_plans');

        // Hide the old monthly sample plans so the customer list is clean.
        $t->whereIn('code', ['starter', 'business', 'enterprise'])->update(['status' => 0, 'updated_at' => $now]);

        foreach ($this->tiers as $tier) {
            $data = [
                'name'          => $tier['name'],
                'price'         => $tier['price'],
                'billing_cycle' => 'yearly',
                'status'        => 1,
                'updated_at'    => $now,
            ];
            if ($t->where('code', $tier['code'])->countAllResults() > 0) {
                $t->where('code', $tier['code'])->update($data);
            } else {
                $t->insert($data + [
                    'code'      => $tier['code'],
                    'max_firms' => null,
                    'max_users' => null,
                    'features'  => 'Full access: PDF/print, reports, attachments, statement download, opening balance.',
                    'created_at' => $now,
                ]);
            }
        }
    }

    public function down()
    {
        // Re-enable the old monthly plans; leave the yearly tiers in place.
        $this->db->table('subscription_plans')
            ->whereIn('code', ['starter', 'business', 'enterprise'])
            ->update(['status' => 1]);
    }
}
