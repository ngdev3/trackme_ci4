<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * App usage events — records which menu / screen a user taps and when, sent by
 * the mobile app (and web). Surfaced only in the Super Admin panel for usage
 * analytics. Kept lightweight and append-only.
 */
class CreateAppEvents extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('app_events')) {
            return;
        }
        $this->forge->addField([
            'id'         => ['type' => 'BIGINT', 'constraint' => 20, 'unsigned' => true, 'auto_increment' => true],
            'user_id'    => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'event'      => ['type' => 'VARCHAR', 'constraint' => 40, 'default' => 'nav'], // nav | tap | action
            'label'      => ['type' => 'VARCHAR', 'constraint' => 120, 'null' => true],    // menu/screen name
            'route'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'platform'   => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'app'], // app | web
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'user_agent' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');
        $this->forge->addKey(['event', 'label']);
        $this->forge->createTable('app_events', true);
    }

    public function down()
    {
        $this->forge->dropTable('app_events', true);
    }
}
