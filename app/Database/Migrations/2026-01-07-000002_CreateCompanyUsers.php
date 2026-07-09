<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Company ↔ user membership with a per-company role (owner/admin/staff). This
 * is the basis for company-wise access control: a user only sees companies they
 * are a member of, and their role governs what they may do inside one. Built to
 * support inviting future users to a company.
 */
class CreateCompanyUsers extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'role'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'staff'], // owner|admin|staff
            'status'     => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'user_id']);
        $this->forge->addKey('user_id');
        $this->forge->createTable('company_users', true);
    }

    public function down()
    {
        $this->forge->dropTable('company_users', true);
    }
}
