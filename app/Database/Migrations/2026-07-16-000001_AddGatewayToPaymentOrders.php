<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tag every payment_orders row with the gateway that produced it. Cashfree rows
 * predate Google Play Billing, so the column defaults to 'cashfree' and existing
 * rows keep that value; Play Billing transactions are stored with 'googleplay'.
 * This lets the Super Admin ledger tell the two revenue streams apart.
 */
class AddGatewayToPaymentOrders extends Migration
{
    public function up()
    {
        $this->forge->addColumn('payment_orders', [
            'gateway' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'default'    => 'cashfree',
                'after'      => 'currency',
            ],
        ]);
        $this->db->table('payment_orders')->update(['gateway' => 'cashfree'], ['gateway' => null]);
    }

    public function down()
    {
        $this->forge->dropColumn('payment_orders', 'gateway');
    }
}
