<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Add a per-bag rate to products so the owner dashboard can value the inventory
 * (Task 8). Optional — defaults to 0 (value shown only once rates are set).
 */
class AddRateToInvProducts extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('rate', 'inv_products')) {
            $this->forge->addColumn('inv_products', [
                'rate' => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0, 'after' => 'avg_weight'],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('rate', 'inv_products')) {
            $this->forge->dropColumn('inv_products', 'rate');
        }
    }
}
