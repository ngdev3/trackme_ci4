<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * User reminders. Repeat settings live inline (repeat_type/interval/until) since
 * they are 1:1 with a reminder. `snoozed_until` overrides `remind_at` for the
 * effective due time. Overdue is derived (pending + effective time in the past).
 */
class CreateReminders extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'user_id'         => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'           => ['type' => 'VARCHAR', 'constraint' => 191],
            'description'     => ['type' => 'TEXT', 'null' => true],
            'remind_at'       => ['type' => 'DATETIME'],
            'priority'        => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'medium'],  // low|medium|high
            'status'          => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'pending'], // pending|completed
            'repeat_type'     => ['type' => 'VARCHAR', 'constraint' => 10, 'default' => 'none'],    // none|daily|weekly|monthly|yearly|custom
            'repeat_interval' => ['type' => 'INT', 'constraint' => 11, 'null' => true],             // custom: every N days
            'repeat_until'    => ['type' => 'DATE', 'null' => true],
            'snoozed_until'   => ['type' => 'DATETIME', 'null' => true],
            'completed_at'    => ['type' => 'DATETIME', 'null' => true],
            'notified'        => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'last_notified_at'=> ['type' => 'DATETIME', 'null' => true],
            'attach_module'   => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true],
            'attach_ref'      => ['type' => 'VARCHAR', 'constraint' => 191, 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
            'deleted_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('remind_at');
        $this->forge->addKey('status');
        $this->forge->createTable('reminders', true);
    }

    public function down()
    {
        $this->forge->dropTable('reminders', true);
    }
}
