<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-firm ledger accounts, each filed under an accounting group. Firm-scoped
 * by company_id so ledgers never cross firms.
 */
class CreateLedgers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'group_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 150],
            'opening_balance' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'opening_type'    => ['type' => 'VARCHAR', 'constraint' => 2, 'default' => 'Dr'], // Dr|Cr
            'gst_number'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'contact'         => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'notes'           => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addKey('group_id');
        $this->forge->createTable('ledgers', true);
    }

    public function down()
    {
        $this->forge->dropTable('ledgers', true);
    }
}
