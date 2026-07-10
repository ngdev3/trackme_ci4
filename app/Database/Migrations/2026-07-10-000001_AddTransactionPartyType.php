<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Classifies an entry by what the counterparty is (Farmer, Firm, Trader…).
 *
 * Stored on the transaction rather than on the account, because the ledger has no
 * accounts table (a party is just a name) and the same name may trade in more than
 * one capacity.
 */
class AddTransactionPartyType extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('party_type', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'party_type' => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true, 'after' => 'name'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('party_type', 'transactions')) {
            $this->forge->dropColumn('transactions', 'party_type');
        }
    }
}
