<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tags where a transaction was entered — 'web' (admin panel) or 'app' (mobile).
 * Drives the Web/App badge on the Rokad Parcha daily view.
 */
class AddTransactionSource extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('source', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'source' => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'web', 'after' => 'payment_mode'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('source', 'transactions')) {
            $this->forge->dropColumn('transactions', 'source');
        }
    }
}
