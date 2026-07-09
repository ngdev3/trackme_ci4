<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A simple transactions ledger (TrackMe-style): dated income/expense entries
 * with a party name, amount, status and a computed running balance. Per-user
 * so each account keeps its own ledger.
 */
class CreateTransactions extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'txn_date'   => ['type' => 'DATE'],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 191],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'income'],  // income|expense
            'amount'     => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 15, 'default' => 'paid'],     // paid|pending|overdue|cancelled
            'notes'      => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
            'deleted_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['user_id', 'txn_date']);
        $this->forge->createTable('transactions', true);
    }

    public function down()
    {
        $this->forge->dropTable('transactions', true);
    }
}
