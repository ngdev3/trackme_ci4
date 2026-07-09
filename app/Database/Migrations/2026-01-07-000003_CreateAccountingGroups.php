<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-company chart-of-accounts groups. Seeded with the standard Tally-style
 * primary groups when a company is created. Scoped by company_id for isolation.
 */
class CreateAccountingGroups extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'nature'     => ['type' => 'VARCHAR', 'constraint' => 20], // Assets|Liabilities|Income|Expenses
            'parent'     => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'is_default' => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 1],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('company_id');
        $this->forge->createTable('accounting_groups', true);
    }

    public function down()
    {
        $this->forge->dropTable('accounting_groups', true);
    }
}
