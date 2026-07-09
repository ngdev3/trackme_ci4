<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-user note categories / tags palette. Each user owns their own set so one
 * user never sees another's categories.
 */
class CreateNoteCategories extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 60],
            'color'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => '#6c757d'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->createTable('note_categories', true);
    }

    public function down()
    {
        $this->forge->dropTable('note_categories', true);
    }
}
