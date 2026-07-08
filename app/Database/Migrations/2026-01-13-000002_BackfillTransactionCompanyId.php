<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * The Jama/Naam ledger (Hisaab-Kitaab Vahi) is now scoped per company instead of
 * per user, so switching the active company shows that company's book. Existing
 * rows created before company scoping was enforced may have a NULL `company_id`
 * and would otherwise vanish from every non-superadmin view. Backfill them from
 * each author's first company membership so no ledger entry is lost.
 *
 * The `company_id` column already exists on `transactions` (added with the
 * table); this migration only fills the gaps. Rows whose author has no company
 * membership (e.g. the Super Admin's own test rows) are left NULL — the Super
 * Admin sees all rows regardless of scope.
 */
class BackfillTransactionCompanyId extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('company_id', 'transactions')) {
            return; // nothing to backfill if the column isn't there
        }

        $this->db->query(
            "UPDATE `transactions` t
             SET t.company_id = (
                 SELECT MIN(cu.company_id)
                 FROM `company_users` cu
                 WHERE cu.user_id = t.user_id AND cu.status = 1
             )
             WHERE t.company_id IS NULL"
        );
    }

    public function down()
    {
        // Data-only backfill — nothing to reverse (the column predates this migration).
    }
}
