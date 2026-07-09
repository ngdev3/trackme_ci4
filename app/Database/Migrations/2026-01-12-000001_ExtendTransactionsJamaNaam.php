<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Evolve the simple income/expense ledger into a full Jama (money received) /
 * Naam (money paid) transaction register:
 *   - txn_no        auto-generated human-friendly number (e.g. TXN-000123)
 *   - payment_mode  cash | bank | upi | cheque | card | other
 *   - type          migrated income -> jama, expense -> naam
 *   - status        now also allows 'draft' (varchar already permits it)
 *
 * Existing rows are converted in place. Idempotent-ish: guarded by column
 * existence so re-running on a partially-migrated DB won't error.
 */
class ExtendTransactionsJamaNaam extends Migration
{
    public function up()
    {
        $fields = [];

        if (! $this->db->fieldExists('txn_no', 'transactions')) {
            $fields['txn_no'] = ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true, 'after' => 'company_id'];
        }
        if (! $this->db->fieldExists('payment_mode', 'transactions')) {
            $fields['payment_mode'] = ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'cash', 'after' => 'amount'];
        }
        if ($fields !== []) {
            $this->forge->addColumn('transactions', $fields);
        }

        // Migrate the type vocabulary: income -> jama, expense -> naam.
        $this->db->query("UPDATE transactions SET type = 'jama' WHERE type = 'income'");
        $this->db->query("UPDATE transactions SET type = 'naam' WHERE type = 'expense'");

        // Change the column default to the new vocabulary.
        $this->forge->modifyColumn('transactions', [
            'type' => ['name' => 'type', 'type' => 'VARCHAR', 'constraint' => 10, 'default' => 'jama'],
        ]);

        // Backfill txn numbers for pre-existing rows (per user, ordered by id).
        $this->backfillTxnNumbers();

        // Helpful index for number lookups.
        if (! $this->indexExists('transactions', 'transactions_txn_no')) {
            $this->db->query('CREATE INDEX transactions_txn_no ON transactions (txn_no)');
        }
    }

    public function down()
    {
        // Reverse the vocabulary; leave the extra columns (non-destructive).
        $this->db->query("UPDATE transactions SET type = 'income' WHERE type = 'jama'");
        $this->db->query("UPDATE transactions SET type = 'expense' WHERE type = 'naam'");
    }

    private function backfillTxnNumbers(): void
    {
        $rows = $this->db->table('transactions')
            ->select('id, user_id')
            ->where('txn_no', null)
            ->orderBy('user_id', 'ASC')->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $counters = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            if (! isset($counters[$uid])) {
                // Continue after the highest existing number for this user, if any.
                $counters[$uid] = 0;
            }
            $counters[$uid]++;
            $no = 'TXN-' . str_pad((string) $counters[$uid], 6, '0', STR_PAD_LEFT);
            $this->db->table('transactions')->where('id', $r['id'])->update(['txn_no' => $no]);
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        $db = $this->db->getDatabase();
        $sql = 'SELECT COUNT(*) AS c FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?';
        $row = $this->db->query($sql, [$db, $table, $index])->getRowArray();
        return (int) ($row['c'] ?? 0) > 0;
    }
}
