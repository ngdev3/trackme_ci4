<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRoles extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 100],
            'code'         => ['type' => 'VARCHAR', 'constraint' => 50],
            'description'  => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'is_superadmin'=> ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'status'       => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('roles', true);
    }

    public function down()
    {
        $this->forge->dropTable('roles', true);
    }
}
