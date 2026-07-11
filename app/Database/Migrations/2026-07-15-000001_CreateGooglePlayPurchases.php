<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Google Play Billing purchases. One row per subscription purchase token (Play
 * re-issues a new token on each renewal/resubscribe, chaining old→new via
 * linked_purchase_token). The row is the server's record of truth for a Play
 * subscription: it drives activation of the matching customer subscription and
 * is updated by Real-time Developer Notifications (RTDN).
 *
 * Every purchase is verified server-side against the Play Developer API before a
 * row is trusted — the Android client is never trusted on its own.
 */
class CreateGooglePlayPurchases extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'customer_id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // who initiated
            'plan_id'               => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'product_id'            => ['type' => 'VARCHAR', 'constraint' => 128],                 // Play subscription product id
            'base_plan_id'          => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true], // Play base plan id
            'purchase_token'        => ['type' => 'VARCHAR', 'constraint' => 512],                 // authoritative key
            'order_id'              => ['type' => 'VARCHAR', 'constraint' => 128, 'null' => true], // GPA.xxxx order id
            'linked_purchase_token' => ['type' => 'VARCHAR', 'constraint' => 512, 'null' => true], // prior token on upgrade/renew
            'purchase_time'         => ['type' => 'DATETIME', 'null' => true],
            'expiry_time'           => ['type' => 'DATETIME', 'null' => true],
            // active|cancelled|in_grace|on_hold|paused|expired|revoked|refunded|pending
            'status'                => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'auto_renewing'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'acknowledged'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'activated'             => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0], // subscription activated?
            'last_notification_type'=> ['type' => 'INT', 'constraint' => 11, 'null' => true],    // last RTDN type
            'raw'                   => ['type' => 'TEXT', 'null' => true],                         // last verification payload
            'created_at'            => ['type' => 'DATETIME', 'null' => true],
            'updated_at'            => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('purchase_token');
        $this->forge->addKey('customer_id');
        $this->forge->addKey('status');
        $this->forge->addKey('order_id');
        $this->forge->createTable('google_play_purchases', true);
    }

    public function down()
    {
        $this->forge->dropTable('google_play_purchases', true);
    }
}
