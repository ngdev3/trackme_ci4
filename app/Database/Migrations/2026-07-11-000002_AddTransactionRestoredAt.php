<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Marks when a soft-deleted transaction was brought back, so the entry detail can
 * show "Restored on …" versus a fresh entry. Null = never deleted/restored.
 */
class AddTransactionRestoredAt extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('restored_at', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'restored_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'deleted_at'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('restored_at', 'transactions')) {
            $this->forge->dropColumn('transactions', 'restored_at');
        }
    }
}
