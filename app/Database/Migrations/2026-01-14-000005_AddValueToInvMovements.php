<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add per-entry value to the ledger for the simplified Purchase/Sale flow:
 * a unit rate and the total amount (₹). Both optional (default 0). This lets the
 * Stock Position report show purchase and sale value alongside quantity.
 */
class AddValueToInvMovements extends Migration
{
    public function up()
    {
        $add = [];
        if (! $this->db->fieldExists('rate', 'inv_movements')) {
            $add['rate'] = ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0, 'after' => 'weight'];
        }
        if (! $this->db->fieldExists('amount', 'inv_movements')) {
            $add['amount'] = ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0, 'after' => 'rate'];
        }
        if ($add !== []) {
            $this->forge->addColumn('inv_movements', $add);
        }
    }

    public function down()
    {
        foreach (['rate', 'amount'] as $col) {
            if ($this->db->fieldExists($col, 'inv_movements')) {
                $this->forge->dropColumn('inv_movements', $col);
            }
        }
    }
}
