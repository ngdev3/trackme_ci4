<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Dashboard performance: the Super-Admin (all-firms) dashboard aggregates over
 * the whole `transactions` table (1M+ rows). Without these indexes those
 * aggregates full-scan the table and the page took ~19s to build on a cache miss.
 *
 * Each index LEADS with the column the query actually filters/sorts on so the
 * optimizer picks it (a leading `deleted_at`, NULL for ~all rows, is not
 * selective and gets mis-chosen — the model FORCE INDEXes the two that still do):
 *   - idx_dash_date   (txn_date, deleted_at, type, amount) — covering: date-range
 *     SUM-by-type (period/today/daily-trend) + whole-ledger cash-in-hand sum.
 *   - idx_dash_recent (deleted_at, txn_date)               — recent-entries feed
 *     (ORDER BY txn_date DESC, id DESC) with no filesort.
 *   - idx_dash_status (status, deleted_at)                 — pending/overdue count.
 *   - idx_dash_party  (deleted_at, name, type, amount)     — covering: top-parties
 *     GROUP BY name without a temp table.
 *
 * Guarded via information_schema (MySQL has no CREATE INDEX IF NOT EXISTS).
 */
class AddDashboardIndexesToTransactions extends Migration
{
    private array $indexes = [
        'idx_dash_date'   => '(txn_date, deleted_at, type, amount)',
        'idx_dash_recent' => '(deleted_at, txn_date)',
        'idx_dash_status' => '(status, deleted_at)',
        'idx_dash_party'  => '(deleted_at, name, type, amount)',
        // DISTINCT party_type for the report filters — leading party_type lets
        // MySQL do a loose "index for group-by" scan (22s → ~5ms).
        'idx_dash_ptype'  => '(party_type, deleted_at)',
    ];

    public function up(): void
    {
        if (! $this->db->tableExists('transactions')) {
            return;
        }
        $schema = $this->db->getDatabase();
        foreach ($this->indexes as $name => $cols) {
            $exists = $this->db->query(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$schema, 'transactions', $name]
            )->getResultArray();
            if (! $exists) {
                $this->db->query("ALTER TABLE `transactions` ADD INDEX `{$name}` {$cols}");
            }
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('transactions')) {
            return;
        }
        $schema = $this->db->getDatabase();
        foreach (array_keys($this->indexes) as $name) {
            $exists = $this->db->query(
                'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
                [$schema, 'transactions', $name]
            )->getResultArray();
            if ($exists) {
                $this->db->query("ALTER TABLE `transactions` DROP INDEX `{$name}`");
            }
        }
    }
}
