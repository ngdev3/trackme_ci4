<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add a party "role" (Customer / Supplier / Both) to the party master — who you
 * sell to vs buy from — separate from the descriptive party_type. Used for the
 * balance sheet (debtors vs creditors) and filtering.
 */
class AddPartyRole extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('parties') && ! $this->db->fieldExists('party_role', 'parties')) {
            $this->forge->addColumn('parties', [
                // '', customer, supplier, both
                'party_role' => ['type' => 'VARCHAR', 'constraint' => 12, 'null' => true, 'after' => 'party_type'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('party_role', 'parties')) {
            $this->forge->dropColumn('parties', 'party_role');
        }
    }
}
