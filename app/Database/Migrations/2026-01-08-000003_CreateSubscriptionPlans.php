<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * SaaS subscription plans managed by the Super Admin. Limits (max_firms /
 * max_users) support future enforcement of a customer's quota.
 */
class CreateSubscriptionPlans extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'          => ['type' => 'VARCHAR', 'constraint' => 60],
            'code'          => ['type' => 'VARCHAR', 'constraint' => 40],
            'price'         => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'billing_cycle' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'monthly'], // monthly|yearly|lifetime
            'max_firms'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'max_users'     => ['type' => 'INT', 'constraint' => 11, 'null' => true],
            'features'      => ['type' => 'TEXT', 'null' => true],
            'status'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('subscription_plans', true);
    }

    public function down()
    {
        $this->forge->dropTable('subscription_plans', true);
    }
}
