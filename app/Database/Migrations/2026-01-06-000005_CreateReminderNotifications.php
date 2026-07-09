<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Log of in-app notifications fired for reminders that became due. Also acts as
 * the idempotency guard so a due reminder is only announced once per occurrence.
 * `notification_id` links to the shared `notifications` table when available.
 */
class CreateReminderNotifications extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'reminder_id'     => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'notification_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => true],
            'fired_for'       => ['type' => 'DATETIME'], // the reminder occurrence time this row covers
            'is_read'         => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('reminder_id');
        $this->forge->addKey('user_id');
        $this->forge->createTable('reminder_notifications', true);
    }

    public function down()
    {
        $this->forge->dropTable('reminder_notifications', true);
    }
}
