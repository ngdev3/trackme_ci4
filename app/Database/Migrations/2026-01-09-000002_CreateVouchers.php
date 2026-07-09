<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Accounting vouchers (transactions) and their double-entry lines. A voucher's
 * entries always balance (sum of debits = sum of credits). Firm-scoped.
 */
class CreateVouchers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'voucher_type' => ['type' => 'VARCHAR', 'constraint' => 20], // payment|receipt|contra|journal|sales|purchase
            'voucher_no'   => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'date'         => ['type' => 'DATE'],
            'narration'    => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'amount'       => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'created_by'   => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->addKey('date');
        $this->forge->createTable('vouchers', true);

        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'voucher_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'ledger_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'dr_amount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'cr_amount'  => ['type' => 'DECIMAL', 'constraint' => '15,2', 'default' => 0],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('voucher_id');
        $this->forge->addKey('ledger_id');
        $this->forge->createTable('voucher_entries', true);
    }

    public function down()
    {
        $this->forge->dropTable('voucher_entries', true);
        $this->forge->dropTable('vouchers', true);
    }
}
