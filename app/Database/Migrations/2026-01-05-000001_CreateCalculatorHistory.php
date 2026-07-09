<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Saved calculator history. Each row belongs to the user who saved it and may
 * carry a user-supplied title; when left blank the controller assigns an
 * automatic "Calculation #<id>" label.
 */
class CreateCalculatorHistory extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'      => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'expression' => ['type' => 'VARCHAR', 'constraint' => 255],
            'result'     => ['type' => 'VARCHAR', 'constraint' => 100],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('calculator_history', true);
    }

    public function down()
    {
        $this->forge->dropTable('calculator_history', true);
    }
}
