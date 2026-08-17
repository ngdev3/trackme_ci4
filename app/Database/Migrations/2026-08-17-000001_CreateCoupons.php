<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Coupon codes for subscriptions. Two kinds:
 *   - 'discount' : reduces a paid order's amount at checkout (Cashfree / web).
 *                  Percentage or fixed ₹, optionally capped and plan-scoped.
 *   - 'redeem'   : grants free plan time directly (a gift / redeem code). Works
 *                  everywhere INCLUDING the mobile app, where Google Play controls
 *                  pricing and a discount cannot be applied to a Play purchase.
 *
 * NOTE: distinct from the accounting `vouchers` table (double-entry journal
 * vouchers) — this is the marketing/billing discount system.
 */
class CreateCoupons extends Migration
{
    public function up()
    {
        // ---- coupons -----------------------------------------------------
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'code'            => ['type' => 'VARCHAR', 'constraint' => 40],
            'description'     => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            // 'discount' | 'redeem'
            'kind'            => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'discount'],
            // discount only: 'percent' | 'fixed'
            'discount_type'   => ['type' => 'VARCHAR', 'constraint' => 10, 'null' => true],
            // percent (0-100) or fixed rupees; for redeem codes this is unused
            'discount_value'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            // optional cap on a percentage discount (₹)
            'max_discount'    => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            // redeem only: number of days of plan access the code grants
            'free_days'       => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            // discount: restrict to this plan (null = any); redeem: the plan to grant
            'plan_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            // discount: minimum order amount for the code to apply
            'min_amount'      => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            // global redemption cap (null = unlimited) + per-customer cap
            'max_redemptions' => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'per_user_limit'  => ['type' => 'INT', 'constraint' => 11, 'default' => 1],
            'redeemed_count'  => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'starts_at'       => ['type' => 'DATETIME', 'null' => true],
            'expires_at'      => ['type' => 'DATETIME', 'null' => true],
            'status'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_by'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('status');
        $this->forge->createTable('coupons', true);

        // ---- coupon_redemptions -----------------------------------------
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'coupon_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'customer_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'kind'              => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'discount'],
            // discount path: the payment order this coupon was applied to
            'order_id'          => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'plan_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'amount_discounted' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'days_granted'      => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('coupon_id');
        $this->forge->addKey('customer_id');
        $this->forge->addKey('order_id');
        $this->forge->createTable('coupon_redemptions', true);

        // ---- payment_orders: remember which coupon reduced the charge ----
        if (! $this->db->fieldExists('coupon_id', 'payment_orders')) {
            $this->forge->addColumn('payment_orders', [
                'coupon_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'plan_id'],
                'discount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0, 'after' => 'amount'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('coupon_id', 'payment_orders')) {
            $this->forge->dropColumn('payment_orders', ['coupon_id', 'discount']);
        }
        $this->forge->dropTable('coupon_redemptions', true);
        $this->forge->dropTable('coupons', true);
    }
}
