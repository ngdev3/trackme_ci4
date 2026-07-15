<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Public "inquiry / contact us" submissions from the marketing site. Captured by
 * the landing-page inquiry form (server-validated) so the team can follow up.
 */
class CreateInquiries extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('inquiries')) {
            return;
        }
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'name'       => ['type' => 'VARCHAR', 'constraint' => 120],
            'email'      => ['type' => 'VARCHAR', 'constraint' => 190],
            'phone'      => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'company'    => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'subject'    => ['type' => 'VARCHAR', 'constraint' => 40, 'null' => true],
            'message'    => ['type' => 'TEXT'],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'new'],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('status');
        $this->forge->createTable('inquiries', true);
    }

    public function down()
    {
        $this->forge->dropTable('inquiries', true);
    }
}
