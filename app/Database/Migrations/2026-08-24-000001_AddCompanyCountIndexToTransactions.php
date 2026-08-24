<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Firm-list performance: GET /api/v1/companies (mobile Firm Management) shows a
 * per-firm entry-count badge, computed as
 *   SELECT company_id, COUNT(*) ... WHERE deleted_at IS NULL
 *     AND company_id IN (<the user's firms>) GROUP BY company_id
 *
 * With a multi-value IN() list the optimizer mis-picks the deleted_at-leading
 * `idx_dash_recent` index (deleted_at is NULL for ~all rows → not selective) and
 * scans ~half the 1M-row table — the endpoint took ~58s for an account with many
 * firms. This company-leading covering index makes it an index-only company scan;
 * TransactionModel::countsByCompany() FORCE INDEXes it (~0.6s).
 *
 * Guarded via information_schema (MySQL has no CREATE INDEX IF NOT EXISTS).
 */
class AddCompanyCountIndexToTransactions extends Migration
{
    private string $name = 'idx_txn_company_deleted';
    private string $cols = '(company_id, deleted_at)';

    public function up(): void
    {
        if (! $this->db->tableExists('transactions')) {
            return;
        }
        $schema = $this->db->getDatabase();
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$schema, 'transactions', $this->name]
        )->getResultArray();
        if (! $exists) {
            $this->db->query("ALTER TABLE `transactions` ADD INDEX `{$this->name}` {$this->cols}");
        }
    }

    public function down(): void
    {
        if (! $this->db->tableExists('transactions')) {
            return;
        }
        $schema = $this->db->getDatabase();
        $exists = $this->db->query(
            'SELECT 1 FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ? LIMIT 1',
            [$schema, 'transactions', $this->name]
        )->getResultArray();
        if ($exists) {
            $this->db->query("ALTER TABLE `transactions` DROP INDEX `{$this->name}`");
        }
    }
}
