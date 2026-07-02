<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserRoles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['user_id', 'role_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('role_id', 'roles', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('user_roles', true);
    }

    public function down()
    {
        $this->forge->dropTable('user_roles', true);
    }
}
