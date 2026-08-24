<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Self-service "delete my account" requests, raised from the mobile app or the
 * web portal. A super admin reviews each one and either approves (which performs
 * the full permanent purge via AccountPurgeService) or rejects it. No user data
 * is removed until an approval.
 */
class CreateAccountDeletionRequests extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('account_deletion_requests')) {
            return;
        }

        $this->forge->addField([
            'id'           => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'      => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'name'         => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'email'        => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'mobile'       => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'reason'       => ['type' => 'VARCHAR', 'constraint' => 1000, 'null' => true],
            'source'       => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'web'],   // app | web
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'], // pending | approved | rejected | cancelled
            'admin_note'   => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'ip_address'   => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent'   => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'processed_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'processed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at'   => ['type' => 'DATETIME', 'null' => true],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('status');
        $this->forge->createTable('account_deletion_requests', true);
    }

    public function down()
    {
        $this->forge->dropTable('account_deletion_requests', true);
    }
}
