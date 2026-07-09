<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A customer's subscription to a plan, with payment status the Super Admin can
 * manage. One active subscription per customer (the account owner).
 */
class CreateSubscriptions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'customer_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'plan_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'status'         => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'trial'],  // trial|active|expired|cancelled
            'payment_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'trial'],  // trial|paid|unpaid
            'started_at'     => ['type' => 'DATETIME', 'null' => true],
            'expires_at'     => ['type' => 'DATETIME', 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('customer_id');
        $this->forge->createTable('subscriptions', true);
    }

    public function down()
    {
        $this->forge->dropTable('subscriptions', true);
    }
}
