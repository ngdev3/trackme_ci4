<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Master list of permission actions (view, add, edit, delete, print, export, approve).
 */
class CreatePermissions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('permissions', true);
    }

    public function down()
    {
        $this->forge->dropTable('permissions', true);
    }
}
