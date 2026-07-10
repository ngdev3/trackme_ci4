<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * QA hardening (F-4, F-5) for the Jama/Naam ledger.
 *
 *  - F-4: every tenant query filters on company_id, but the table had no index
 *    covering it (only (user_id, txn_date)). Add (company_id, txn_date) so the
 *    per-company ledger, reports and dashboard live-poll stop full-scanning.
 *  - F-5: txn_no was unique only because application code enforced it. Add a
 *    real UNIQUE (company_id, txn_no) so the database rejects duplicate voucher
 *    numbers within a company even under a race or a manual insert.
 *
 * Note: company_id is intentionally left NULLABLE — a NULL scope is the Super
 * Admin's global book (see TransactionModel). MySQL treats each NULL as
 * distinct in a UNIQUE index, so the Super Admin's rows never collide with a
 * company's, and the FK added in the next migration still guards non-null ids.
 *
 * Idempotent: each index is only created if absent, so a partial/repeat run is
 * safe.
 */
class AddTransactionIndexes extends Migration
{
    private const TABLE = 'transactions';

    /** @var array<string, string> index name => column list for the ADD statement */
    private array $indexes = [
        'idx_txn_company_date' => 'INDEX `idx_txn_company_date` (`company_id`, `txn_date`)',
        'uq_txn_company_no'    => 'UNIQUE `uq_txn_company_no` (`company_id`, `txn_no`)',
    ];

    public function up()
    {
        foreach ($this->indexes as $name => $definition) {
            if (! $this->indexExists($name)) {
                $this->db->query('ALTER TABLE `' . self::TABLE . '` ADD ' . $definition);
            }
        }
    }

    public function down()
    {
        foreach (array_keys($this->indexes) as $name) {
            if ($this->indexExists($name)) {
                $this->db->query('ALTER TABLE `' . self::TABLE . '` DROP INDEX `' . $name . '`');
            }
        }
    }

    private function indexExists(string $name): bool
    {
        $db = $this->db->getDatabase();

        return (bool) $this->db->query(
            'SELECT 1 FROM information_schema.statistics
             WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$db, self::TABLE, $name]
        )->getRow();
    }
}
