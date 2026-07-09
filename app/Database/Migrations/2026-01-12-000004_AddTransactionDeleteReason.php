<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Records the reason captured when an entry is (soft) deleted.
 */
class AddTransactionDeleteReason extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('delete_reason', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'delete_reason' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'notes'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('delete_reason', 'transactions')) {
            $this->forge->dropColumn('transactions', 'delete_reason');
        }
    }
}
