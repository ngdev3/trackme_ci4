<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateLoginLogs extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'username'   => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'success'], // success|failed
            'message'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('login_logs', true);
    }

    public function down()
    {
        $this->forge->dropTable('login_logs', true);
    }
}
