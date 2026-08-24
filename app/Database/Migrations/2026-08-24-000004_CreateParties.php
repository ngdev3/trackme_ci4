<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Party master — per-company balance-sheet details for each party account
 * (contact info, GST, and an opening balance). Parties still live as names on
 * transactions; this table just holds the extra editable fields a ledger /
 * balance sheet needs. Linked by (company_id, name); renaming a party keeps the
 * two in sync.
 */
class CreateParties extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('parties')) {
            return;
        }
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'            => ['type' => 'VARCHAR', 'constraint' => 191],
            'party_type'      => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
            'mobile'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'email'           => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'address'         => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'gst_number'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'opening_balance' => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'opening_type'    => ['type' => 'VARCHAR', 'constraint' => 4, 'default' => 'dr'], // dr = to receive, cr = to pay
            'notes'           => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'name']);
        $this->forge->createTable('parties', true);
    }

    public function down()
    {
        $this->forge->dropTable('parties', true);
    }
}
