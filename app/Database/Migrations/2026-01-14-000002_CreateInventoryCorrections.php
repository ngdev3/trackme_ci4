<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Physical stock verification → correction requests (Task 4).
 *
 * Workers count physical stock and submit a correction REQUEST when it differs
 * from the system figure — they can never adjust inventory directly. An owner /
 * admin reviews the request; only on approval is an adjustment ledger entry
 * generated automatically and the stock reconciled. Company-scoped.
 */
class CreateInventoryCorrections extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'product_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'warehouse_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'system_bags'   => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'physical_bags' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'difference'    => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0], // physical − system
            'reason'        => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],     // moisture_loss|damaged|...
            'note'          => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'pending'], // pending|approved|rejected
            'movement_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true], // adjustment entry created on approval
            'requested_by'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reviewed_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'review_note'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'reviewed_at'   => ['type' => 'DATETIME', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'    => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['company_id', 'status']);
        $this->forge->createTable('inv_corrections', true);
    }

    public function down()
    {
        $this->forge->dropTable('inv_corrections', true);
    }
}
