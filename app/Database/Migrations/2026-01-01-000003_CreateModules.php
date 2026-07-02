<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateModules extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'code'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'url'        => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'icon'       => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => 'bi bi-circle'],
            'parent_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'sort_order' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'is_menu'    => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'status'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->addKey('parent_id');
        $this->forge->createTable('modules', true);
    }

    public function down()
    {
        $this->forge->dropTable('modules', true);
    }
}
