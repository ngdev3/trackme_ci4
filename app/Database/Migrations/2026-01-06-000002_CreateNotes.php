<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * User notes. Soft-deleted rows act as the recycle bin until permanently
 * purged. Notes may be attached to another project module (customers,
 * invoices, sales, purchase, tasks, employees) via attach_module + attach_ref.
 */
class CreateNotes extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'category_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'title'         => ['type' => 'VARCHAR', 'constraint' => 191],
            'content'       => ['type' => 'TEXT', 'null' => true],
            'tags'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'color'         => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'is_pinned'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'is_important'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'attach_module' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'attach_ref'    => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('category_id');
        $this->forge->addKey('is_pinned');
        $this->forge->createTable('notes', true);
    }

    public function down()
    {
        $this->forge->dropTable('notes', true);
    }
}
