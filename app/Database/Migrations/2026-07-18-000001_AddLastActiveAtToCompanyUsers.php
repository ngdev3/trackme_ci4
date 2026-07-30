<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tracks when a user last opened/used a given company (i.e. when it was last the
 * active company for them). Recorded per membership so the mobile app can show a
 * "Last active" time on each company and sort by most-recently-used. Backfilled
 * to the membership's created_at so existing rows have a sensible value.
 */
class AddLastActiveAtToCompanyUsers extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('last_active_at', 'company_users')) {
            $this->forge->addColumn('company_users', [
                'last_active_at' => ['type' => 'DATETIME', 'null' => true, 'after' => 'status'],
            ]);
            // Seed from created_at so existing memberships aren't shown as "never".
            $this->db->query('UPDATE company_users SET last_active_at = created_at WHERE last_active_at IS NULL');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('last_active_at', 'company_users')) {
            $this->forge->dropColumn('company_users', 'last_active_at');
        }
    }
}
