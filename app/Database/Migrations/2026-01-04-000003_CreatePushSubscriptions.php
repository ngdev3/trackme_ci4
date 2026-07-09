<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Browser Web-Push subscriptions (one row per browser/device a user allows).
 * The email is stored alongside so a subscription is always traceable to the
 * account that registered it.
 */
class CreatePushSubscriptions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'email'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'endpoint'      => ['type' => 'TEXT'],
            'endpoint_hash' => ['type' => 'CHAR', 'constraint' => 64],
            'p256dh'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'auth'          => ['type' => 'VARCHAR', 'constraint' => 255],
            'user_agent'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('endpoint_hash');
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('push_subscriptions', true);
    }

    public function down()
    {
        $this->forge->dropTable('push_subscriptions', true);
    }
}
