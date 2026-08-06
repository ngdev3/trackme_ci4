<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Offline-first idempotency key. The mobile app generates a stable `client_uuid`
 * for every transaction it creates and re-sends it on every push retry. A unique
 * (company_id, client_uuid) index lets the sync endpoint de-duplicate a re-sent
 * CREATE (app killed / response lost mid-push) into the existing row instead of
 * inserting a second copy. Existing rows and web-created rows keep a NULL uuid
 * (NULLs are not constrained by the unique index).
 */
class AddClientUuidToTransactions extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('client_uuid', 'transactions')) {
            $this->forge->addColumn('transactions', [
                'client_uuid' => ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'company_id'],
            ]);
        }
        // Unique per company so the same uuid can't create two rows in one firm,
        // while different firms (and NULLs) are unaffected. (MySQL has no
        // CREATE INDEX IF NOT EXISTS, so guard on information_schema.)
        if (! $this->indexExists('uq_txn_company_uuid')) {
            $this->db->query('CREATE UNIQUE INDEX uq_txn_company_uuid ON transactions (company_id, client_uuid)');
        }
    }

    public function down()
    {
        if ($this->indexExists('uq_txn_company_uuid')) {
            $this->db->query('DROP INDEX uq_txn_company_uuid ON transactions');
        }
        if ($this->db->fieldExists('client_uuid', 'transactions')) {
            $this->forge->dropColumn('transactions', 'client_uuid');
        }
    }

    private function indexExists(string $name): bool
    {
        $row = $this->db->query(
            'SELECT COUNT(*) AS n FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ?',
            ['transactions', $name],
        )->getRowArray();

        return (int) ($row['n'] ?? 0) > 0;
    }
}
