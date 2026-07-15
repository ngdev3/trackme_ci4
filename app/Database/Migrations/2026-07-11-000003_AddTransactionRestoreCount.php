<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Counts how many times an entry has been deleted-and-restored, so the register
 * can show "Restored 2×". Backfilled to 1 for rows that already carry a
 * restored_at stamp from the earlier migration.
 */
class AddTransactionRestoreCount extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('restore_count', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'restore_count' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'default' => 0, 'after' => 'restored_at'],
            ]);
            // Rows already restored once (before this counter existed) → count 1.
            $this->db->table('transactions')->where('restored_at IS NOT NULL')->update(['restore_count' => 1]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('restore_count', 'transactions')) {
            $this->forge->dropColumn('transactions', 'restore_count');
        }
    }
}
