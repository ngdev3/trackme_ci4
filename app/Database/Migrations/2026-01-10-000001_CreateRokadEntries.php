<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Rokad Parcha (Cash Book) entries — a plain daily cash register: each row is
 * either a Jama (money in) or a Naam (money out) on a date. Running / opening /
 * closing balances are ALWAYS computed from these rows (never stored), so any
 * edit or delete recalculates everything automatically. Firm-scoped.
 */
class CreateRokadEntries extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'entry_date' => ['type' => 'DATE'],
            'particular' => ['type' => 'VARCHAR', 'constraint' => 191],
            'jama'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0], // money in
            'naam'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0], // money out
            'remarks'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'entry_date']);
        $this->forge->createTable('rokad_entries', true);
    }

    public function down()
    {
        $this->forge->dropTable('rokad_entries', true);
    }
}
