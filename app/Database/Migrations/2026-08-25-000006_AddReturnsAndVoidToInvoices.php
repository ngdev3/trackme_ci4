<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Phase 2: sale/purchase returns + invoice void.
 *
 *   - widen `invoices.type` so it can hold 'sale_return' / 'purchase_return'
 *     (11 chars) alongside 'sale' / 'purchase';
 *   - `ref_invoice_id` links a return (or a void marker) back to its original
 *     bill so both sides reconcile.
 *
 * Returns and voids reuse the existing invoices/invoice_items/stock_movements
 * tables — no new document tables (audit rule 3).
 */
class AddReturnsAndVoidToInvoices extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('invoices')) {
            return;
        }
        // Widen the type column (was VARCHAR(8)).
        $this->forge->modifyColumn('invoices', [
            'type' => ['type' => 'VARCHAR', 'constraint' => 16, 'null' => false],
        ]);
        if (! $this->db->fieldExists('ref_invoice_id', 'invoices')) {
            $this->forge->addColumn('invoices', [
                'ref_invoice_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true, 'after' => 'type'],
            ]);
        }
    }

    public function down()
    {
        if (! $this->db->tableExists('invoices')) {
            return;
        }
        if ($this->db->fieldExists('ref_invoice_id', 'invoices')) {
            $this->forge->dropColumn('invoices', 'ref_invoice_id');
        }
        $this->forge->modifyColumn('invoices', [
            'type' => ['type' => 'VARCHAR', 'constraint' => 8, 'null' => false],
        ]);
    }
}
