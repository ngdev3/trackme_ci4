<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Daily Closing (Task 7). One row per company per day snapshots the day's stock
 * movement and locks it: once a day is closed, workers can no longer add or edit
 * entries for it — only an owner/admin may reopen. Company-scoped, one closing
 * per date.
 */
class CreateInventoryDailyClosings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'closing_date'        => ['type' => 'DATE'],
            'opening_bags'        => ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0],
            'received_bags'       => ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0],
            'dispatched_bags'     => ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0],
            'adjustment_bags'     => ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0], // net signed
            'closing_bags'        => ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0],
            'received_weight'     => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'dispatched_weight'   => ['type' => 'DECIMAL', 'constraint' => '18,2', 'default' => 0],
            'difference_bags'     => ['type' => 'DECIMAL', 'constraint' => '16,2', 'default' => 0], // closing − expected
            'pending_corrections' => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'entry_count'         => ['type' => 'INT', 'constraint' => 11, 'default' => 0],
            'status'              => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'closed'], // closed|reopened
            'notes'               => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'closed_by'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'closed_at'           => ['type' => 'DATETIME', 'null' => true],
            'reopened_by'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'reopened_at'         => ['type' => 'DATETIME', 'null' => true],
            'created_at'          => ['type' => 'DATETIME', 'null' => true],
            'updated_at'          => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'closing_date']);
        $this->forge->createTable('inv_daily_closings', true);
    }

    public function down()
    {
        $this->forge->dropTable('inv_daily_closings', true);
    }
}
