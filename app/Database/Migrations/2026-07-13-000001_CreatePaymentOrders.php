<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Cashfree payment orders. One row per checkout attempt; on a verified PAID
 * status the matching customer subscription is activated. Keeps the gateway
 * order id, our reference, the amount actually charged, and the resolved status
 * so activation is idempotent and auditable.
 */
class CreatePaymentOrders extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                 => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'order_id'           => ['type' => 'VARCHAR', 'constraint' => 64],           // our unique reference
            'cf_order_id'        => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'customer_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'plan_id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'amount'             => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'currency'           => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'INR'],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'created'], // created|paid|failed|expired
            'payment_session_id' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'cf_payment_id'      => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true],
            'activated'          => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0], // subscription activated?
            'created_at'         => ['type' => 'DATETIME', 'null' => true],
            'updated_at'         => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('order_id');
        $this->forge->addKey('customer_id');
        $this->forge->addKey('status');
        $this->forge->createTable('payment_orders', true);
    }

    public function down()
    {
        $this->forge->dropTable('payment_orders', true);
    }
}
