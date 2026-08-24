<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Saved UPI QR payees — a per-company directory of payment targets (a UPI ID,
 * or a bank Account + IFSC) that the mobile app turns into scannable UPI QR
 * codes. Stored server-side so they sync across devices / reinstalls.
 */
class CreateUpiQrPayees extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('upi_qr_payees')) {
            return;
        }
        $this->forge->addField([
            'id'             => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'company_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'        => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'label'          => ['type' => 'VARCHAR', 'constraint' => 80],
            'method'         => ['type' => 'VARCHAR', 'constraint' => 8, 'default' => 'upi'], // upi | bank
            'payee_name'     => ['type' => 'VARCHAR', 'constraint' => 80],
            'upi_id'         => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'bank_name'      => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'branch'         => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'city'           => ['type' => 'VARCHAR', 'constraint' => 80, 'null' => true],
            'account_number' => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'ifsc'           => ['type' => 'VARCHAR', 'constraint' => 15, 'null' => true],
            'amount'         => ['type' => 'DECIMAL', 'constraint' => '15,2', 'null' => true],
            'note'           => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->createTable('upi_qr_payees', true);
    }

    public function down()
    {
        $this->forge->dropTable('upi_qr_payees', true);
    }
}
