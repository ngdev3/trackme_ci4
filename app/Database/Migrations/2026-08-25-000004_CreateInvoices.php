<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Sales / Purchase invoices (bills) for the inventory module. An invoice is a
 * header (party, dates, totals, link to the cash-book transaction it created)
 * plus one row per line item in `invoice_items`. A sale bill posts a Jama
 * (money-in) entry and issues stock; a purchase bill posts a Naam (money-out)
 * entry and receives stock. Company-scoped, soft-deletable.
 */
class CreateInvoices extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('invoices')) {
            $this->forge->addField([
                'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'company_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'created_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'type'         => ['type' => 'VARCHAR', 'constraint' => 8],  // 'sale' | 'purchase'
                'invoice_no'   => ['type' => 'VARCHAR', 'constraint' => 32],
                'party_name'   => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
                'party_type'   => ['type' => 'VARCHAR', 'constraint' => 32, 'null' => true],
                'invoice_date' => ['type' => 'DATE', 'null' => true],
                'subtotal'     => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'tax_total'    => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'discount'     => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'total'        => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'payment_mode' => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'cash'],
                'status'       => ['type' => 'VARCHAR', 'constraint' => 12, 'default' => 'paid'],
                'txn_id'       => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'notes'        => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
                'created_at'   => ['type' => 'DATETIME', 'null' => true],
                'updated_at'   => ['type' => 'DATETIME', 'null' => true],
                'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['company_id', 'type']);
            $this->forge->addKey('invoice_date');
            $this->forge->createTable('invoices', true);
        }

        if (! $this->db->tableExists('invoice_items')) {
            $this->forge->addField([
                'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
                'invoice_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
                'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
                'name'       => ['type' => 'VARCHAR', 'constraint' => 191],
                'qty'        => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
                'rate'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
                'tax_rate'   => ['type' => 'DECIMAL', 'constraint' => '5,2', 'default' => 0],
                'amount'     => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
                'created_at' => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey('invoice_id');
            $this->forge->createTable('invoice_items', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('invoice_items', true);
        $this->forge->dropTable('invoices', true);
    }
}
