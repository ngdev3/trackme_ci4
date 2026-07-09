<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Per-company key/value settings — holds the default dashboard, notes and
 * reminder preferences created when a company is provisioned, and any future
 * company-level configuration. Grouped by `scope` (general|dashboard|notes|
 * reminders) so a whole area can be read at once. Isolated by company_id.
 */
class CreateCompanySettings extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'company_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'scope'      => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'general'],
            'key'        => ['type' => 'VARCHAR', 'constraint' => 100],
            'value'      => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['company_id', 'scope', 'key']);
        $this->forge->createTable('company_settings', true);
    }

    public function down()
    {
        $this->forge->dropTable('company_settings', true);
    }
}
