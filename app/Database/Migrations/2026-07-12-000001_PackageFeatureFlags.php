<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Package-based feature control. Adds a per-feature boolean to every plan so the
 * seven gated modules (calculator, password manager, calendar, reminder, trash,
 * notes, inventory) are switched on/off purely from the database — never
 * hardcoded. `max_firms` doubles as the firm limit (NULL = unlimited).
 *
 * Feature matrix (spec):
 *   Basic    (₹299) — none of the gated features; 2-firm limit.
 *   Standard (₹399) — + calculator, password manager; unlimited firms.
 *   Plus     (₹499) — + calendar, reminder, trash.
 *   Premium  (₹599) — everything (adds notes, inventory).
 *
 * Also records the payment reference on the customer subscription.
 */
class PackageFeatureFlags extends Migration
{
    /** Gated feature → column. Any feature not listed is available to all plans. */
    private const FEATURES = [
        'calculator', 'password_manager', 'calendar', 'reminder', 'trash', 'notes', 'inventory',
    ];

    /** Which gated features each plan code enables. Missing = off. */
    private const MATRIX = [
        'free'        => [],                                                                 // pre-/post-subscription floor
        'yearly_299'  => [],                                                                 // Basic
        'yearly_399'  => ['calculator', 'password_manager'],                                 // Standard
        'yearly_499'  => ['calculator', 'password_manager', 'calendar', 'reminder', 'trash'],// Plus
        'yearly_599'  => ['calculator', 'password_manager', 'calendar', 'reminder', 'trash', 'notes', 'inventory'], // Premium
    ];

    /** Firm cap per plan code (NULL = unlimited). Only Basic is capped. */
    private const FIRM_LIMITS = [
        'yearly_299' => 2,
        'yearly_399' => null,
        'yearly_499' => null,
        'yearly_599' => null,
    ];

    public function up()
    {
        // 1. Feature columns on the plan table.
        foreach (self::FEATURES as $feature) {
            $col = 'feat_' . $feature;
            if (! $this->db->fieldExists($col, 'subscription_plans')) {
                $this->forge->addColumn('subscription_plans', [
                    $col => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'null' => false],
                ]);
            }
        }

        // 2. Payment reference on the customer subscription.
        if (! $this->db->fieldExists('payment_id', 'subscriptions')) {
            $this->forge->addColumn('subscriptions', [
                'payment_id' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'after' => 'payment_status'],
            ]);
        }

        // 3. Seed the matrix (idempotent, keyed by plan code).
        $t   = $this->db->table('subscription_plans');
        $now = date('Y-m-d H:i:s');
        foreach (self::MATRIX as $code => $enabled) {
            if ($t->where('code', $code)->countAllResults() === 0) {
                continue;
            }
            $data = ['updated_at' => $now];
            foreach (self::FEATURES as $feature) {
                $data['feat_' . $feature] = in_array($feature, $enabled, true) ? 1 : 0;
            }
            if (array_key_exists($code, self::FIRM_LIMITS)) {
                $data['max_firms'] = self::FIRM_LIMITS[$code];
            }
            $t->where('code', $code)->update($data);
        }
    }

    public function down()
    {
        foreach (self::FEATURES as $feature) {
            $col = 'feat_' . $feature;
            if ($this->db->fieldExists($col, 'subscription_plans')) {
                $this->forge->dropColumn('subscription_plans', $col);
            }
        }
        if ($this->db->fieldExists('payment_id', 'subscriptions')) {
            $this->forge->dropColumn('subscriptions', 'payment_id');
        }
    }
}
