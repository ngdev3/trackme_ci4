<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rate-limit store for login-attempt protection (throttling by username/IP).
 */
class CreateLoginAttempts extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'identifier' => ['type' => 'VARCHAR', 'constraint' => 191], // username|ip key
            'attempts'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'locked_until' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('identifier');
        $this->forge->createTable('login_attempts', true);
    }

    public function down()
    {
        $this->forge->dropTable('login_attempts', true);
    }
}
