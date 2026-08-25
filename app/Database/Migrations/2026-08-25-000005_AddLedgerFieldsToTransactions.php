<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 1 of the Inventory + Accounts correction.
 *
 * Splits "party ledger" movement from "cash book" movement inside the single
 * `transactions` table without disturbing existing rows:
 *
 *   - `ledger_only` — when 1, the entry affects the PARTY balance
 *     (receivable/payable) but NOT cash-in-hand. A credit sale posts a
 *     `ledger_only` receivable entry so it never inflates the cash book. Every
 *     existing row defaults to 0, so all current cash/report math is unchanged.
 *
 *   - `party_id` — structural link to a party master (nullable; name stays the
 *     join key for now). Lets a later phase move off name-matching (H6).
 *
 * And prepares `invoices` to record the immediately-received amount and its
 * linked cash entry, plus an idempotency key so a double-tapped bill can't post
 * twice (C4).
 *
 * Idempotent: every add is guarded by fieldExists so a partial re-run is safe.
 */
class AddLedgerFieldsToTransactions extends Migration
{
    public function up()
    {
        // --- transactions -------------------------------------------------
        $add = [];
        if (! $this->db->fieldExists('ledger_only', 'transactions')) {
            $add['ledger_only'] = ['type' => 'TINYINT', 'constraint' => 1, 'null' => false, 'default' => 0, 'after' => 'status'];
        }
        if (! $this->db->fieldExists('party_id', 'transactions')) {
            $add['party_id'] = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'name'];
        }
        if ($add !== []) {
            $this->forge->addColumn('transactions', $add);
        }

        // --- invoices -----------------------------------------------------
        if ($this->db->tableExists('invoices')) {
            $inv = [];
            if (! $this->db->fieldExists('received', 'invoices')) {
                $inv['received'] = ['type' => 'DECIMAL', 'constraint' => '14,2', 'null' => false, 'default' => 0, 'after' => 'total'];
            }
            if (! $this->db->fieldExists('pay_txn_id', 'invoices')) {
                $inv['pay_txn_id'] = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'txn_id'];
            }
            if (! $this->db->fieldExists('party_id', 'invoices')) {
                $inv['party_id'] = ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'party_type'];
            }
            if (! $this->db->fieldExists('client_uuid', 'invoices')) {
                $inv['client_uuid'] = ['type' => 'VARCHAR', 'constraint' => 64, 'null' => true, 'after' => 'company_id'];
            }
            if ($inv !== []) {
                $this->forge->addColumn('invoices', $inv);
            }
            // Idempotency: one (company, client_uuid) can exist once.
            $idx = $this->db->query("SHOW INDEX FROM invoices WHERE Key_name = 'uq_inv_company_uuid'")->getResultArray();
            if ($idx === [] && $this->db->fieldExists('client_uuid', 'invoices')) {
                $this->db->query('CREATE UNIQUE INDEX uq_inv_company_uuid ON invoices (company_id, client_uuid)');
            }
        }
    }

    public function down()
    {
        foreach (['ledger_only', 'party_id'] as $col) {
            if ($this->db->fieldExists($col, 'transactions')) {
                $this->forge->dropColumn('transactions', $col);
            }
        }
        if ($this->db->tableExists('invoices')) {
            $idx = $this->db->query("SHOW INDEX FROM invoices WHERE Key_name = 'uq_inv_company_uuid'")->getResultArray();
            if ($idx !== []) {
                $this->db->query('DROP INDEX uq_inv_company_uuid ON invoices');
            }
            foreach (['received', 'pay_txn_id', 'party_id', 'client_uuid'] as $col) {
                if ($this->db->fieldExists($col, 'invoices')) {
                    $this->forge->dropColumn('invoices', $col);
                }
            }
        }
    }
}
