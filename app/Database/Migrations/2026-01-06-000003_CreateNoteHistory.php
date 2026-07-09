<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Edit history for notes — a snapshot of the previous title/content/tags is
 * written every time a note is updated, giving a simple audit trail.
 */
class CreateNoteHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'note_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'content'    => ['type' => 'TEXT', 'null' => true],
            'tags'       => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('note_id');
        $this->forge->createTable('note_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('note_history', true);
    }
}
