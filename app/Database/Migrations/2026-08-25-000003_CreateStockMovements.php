<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Stock In / Out ledger for the inventory Product Master. Each row is one stock
 * movement (a purchase/receipt = 'in', a sale/issue = 'out') that adjusts the
 * product's `current_stock`. Company-scoped; immutable audit trail.
 */
class CreateStockMovements extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('stock_movements')) {
            return;
        }
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'product_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'type'       => ['type' => 'VARCHAR', 'constraint' => 3], // 'in' | 'out'
            'qty'        => ['type' => 'DECIMAL', 'constraint' => '12,3', 'default' => 0],
            'rate'       => ['type' => 'DECIMAL', 'constraint' => '12,2', 'default' => 0],
            'note'       => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'product_id']);
        $this->forge->addKey('created_at');
        $this->forge->createTable('stock_movements', true);
    }

    public function down()
    {
        $this->forge->dropTable('stock_movements', true);
    }
}
