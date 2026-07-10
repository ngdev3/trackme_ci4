<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tax-receipt fields on payment orders: an invoice number + date assigned when
 * the payment is activated, and a refunded flag the Super Admin can set.
 */
class AddInvoiceToPaymentOrders extends Migration
{
    public function up()
    {
        $fields = [];
        if (! $this->db->fieldExists('invoice_no', 'payment_orders')) {
            $fields['invoice_no'] = ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true, 'after' => 'cf_payment_id'];
        }
        if (! $this->db->fieldExists('invoice_date', 'payment_orders')) {
            $fields['invoice_date'] = ['type' => 'DATETIME', 'null' => true, 'after' => 'invoice_no'];
        }
        if (! $this->db->fieldExists('refunded', 'payment_orders')) {
            $fields['refunded'] = ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0, 'after' => 'activated'];
        }
        if ($fields !== []) {
            $this->forge->addColumn('payment_orders', $fields);
        }
    }

    public function down()
    {
        foreach (['invoice_no', 'invoice_date', 'refunded'] as $col) {
            if ($this->db->fieldExists($col, 'payment_orders')) {
                $this->forge->dropColumn('payment_orders', $col);
            }
        }
    }
}
