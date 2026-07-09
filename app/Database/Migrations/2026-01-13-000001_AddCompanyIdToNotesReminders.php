<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Makes Notes & Reminders company-scoped (shared per company) instead of
 * per-user. Adds a nullable `company_id` to every notes/reminders table, keeps
 * the existing `user_id` as the author, and backfills existing rows from each
 * author's first company membership so no data disappears after the upgrade.
 */
class AddCompanyIdToNotesReminders extends Migration
{
    /** Tables that gain a company_id, all keyed off their `user_id`. */
    private const TABLES = [
        'notes',
        'note_categories',
        'note_history',
        'reminders',
        'reminder_notifications',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            if (! $this->db->fieldExists('company_id', $table)) {
                $this->forge->addColumn($table, [
                    'company_id' => [
                        'type'       => 'INT',
                        'constraint' => 11,
                        'unsigned'   => true,
                        'null'       => true,
                        'after'      => 'user_id',
                    ],
                ]);
                $this->db->query("ALTER TABLE `{$table}` ADD INDEX `idx_{$table}_company` (`company_id`)");
            }

            // Backfill: attribute each existing row to its author's first company.
            $this->db->query(
                "UPDATE `{$table}` t
                 SET t.company_id = (
                     SELECT MIN(cu.company_id) FROM `company_users` cu WHERE cu.user_id = t.user_id
                 )
                 WHERE t.company_id IS NULL"
            );
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            if ($this->db->fieldExists('company_id', $table)) {
                $this->forge->dropColumn($table, 'company_id');
            }
        }
    }
}
