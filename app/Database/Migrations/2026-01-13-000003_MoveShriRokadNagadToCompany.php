<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The Shri Rokad Nagad opening-cash values (and their custom label) used to live
 * in the per-user `settings` table. Now that the Jama/Naam ledger is per company,
 * they move to `company_settings` (scope 'transactions') so each company keeps
 * its own opening cash. Existing values are copied to each owner's first company
 * so no opening balance is lost.
 *
 * Rows whose owner has no company membership (e.g. the Super Admin's own test
 * values) are left where they are — the Super Admin's merged view has no single
 * company opening cash anyway.
 */
class MoveShriRokadNagadToCompany extends Migration
{
    public function up()
    {
        // Copy each shri_rokad* setting to the owner's first company (scope 'transactions').
        $this->db->query(
            "INSERT INTO `company_settings` (`company_id`, `scope`, `key`, `value`, `created_at`, `updated_at`)
             SELECT c.company_id, 'transactions', s.setting_key, s.setting_value, NOW(), NOW()
             FROM `settings` s
             JOIN (
                 SELECT user_id, MIN(company_id) AS company_id
                 FROM `company_users`
                 WHERE status = 1
                 GROUP BY user_id
             ) c ON c.user_id = s.user_id
             WHERE s.setting_key LIKE 'shri_rokad%'
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`), `updated_at` = NOW()"
        );

        // Remove the migrated rows from the per-user store so there is one source of truth.
        $this->db->query(
            "DELETE s FROM `settings` s
             JOIN `company_users` cu ON cu.user_id = s.user_id AND cu.status = 1
             WHERE s.setting_key LIKE 'shri_rokad%'"
        );
    }

    public function down()
    {
        // Best-effort reverse: push company-scoped opening cash back to each owner.
        $this->db->query(
            "INSERT INTO `settings` (`user_id`, `setting_key`, `setting_value`, `created_at`, `updated_at`)
             SELECT cu.user_id, cs.`key`, cs.`value`, NOW(), NOW()
             FROM `company_settings` cs
             JOIN `company_users` cu ON cu.company_id = cs.company_id AND cu.role = 'owner'
             WHERE cs.scope = 'transactions' AND cs.`key` LIKE 'shri_rokad%'"
        );
        $this->db->query("DELETE FROM `company_settings` WHERE scope = 'transactions' AND `key` LIKE 'shri_rokad%'");
    }
}
