<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * A firm's invoice numbers must be unique per document type. Without this, a
 * race between two concurrent bill saves (both derive the same next number) or a
 * numbering bug could silently persist two invoices sharing a number. The unique
 * key makes the DB reject the collision so the invoice controller's surrounding
 * transaction rolls the whole bill back instead. Guarded + additive; safe to
 * re-run. Verified there are no existing duplicates (incl. soft-deleted) before
 * adding.
 */
class AddUniqueInvoiceNo extends Migration
{
    public function up()
    {
        if (! $this->db->tableExists('invoices')) {
            return;
        }
        $exists = $this->db->query(
            "SELECT COUNT(*) AS n FROM information_schema.statistics
             WHERE table_schema = DATABASE() AND table_name = 'invoices' AND index_name = 'uq_invoice_no'",
        )->getRowArray();
        if ((int) ($exists['n'] ?? 0) === 0) {
            // Spans soft-deleted rows too — numbering counts them, so a restored
            // bill keeps a number no active bill reused.
            $this->db->query('ALTER TABLE `invoices` ADD UNIQUE KEY `uq_invoice_no` (`company_id`, `type`, `invoice_no`)');
        }
    }

    public function down()
    {
        if ($this->db->tableExists('invoices')) {
            $exists = $this->db->query(
                "SELECT COUNT(*) AS n FROM information_schema.statistics
                 WHERE table_schema = DATABASE() AND table_name = 'invoices' AND index_name = 'uq_invoice_no'",
            )->getRowArray();
            if ((int) ($exists['n'] ?? 0) > 0) {
                $this->db->query('ALTER TABLE `invoices` DROP INDEX `uq_invoice_no`');
            }
        }
    }
}
