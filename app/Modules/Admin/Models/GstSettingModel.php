<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * GstSettingModel — CI4 port of the gst_setting helper. Single-row (id=1)
 * default CGST/SGST/IGST used to pre-fill new Tax Invoice / E-Invoice forms.
 * Self-healing table (aa_gst_settings).
 */
class GstSettingModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function ensureTable(): void
    {
        $db = $this->db();
        $db->query("CREATE TABLE IF NOT EXISTS `aa_gst_settings` (
            `id` INT NOT NULL PRIMARY KEY,
            `cgst` DECIMAL(6,3) NOT NULL DEFAULT 2.500,
            `sgst` DECIMAL(6,3) NOT NULL DEFAULT 2.500,
            `igst` DECIMAL(6,3) NOT NULL DEFAULT 0.000,
            `updated_by` INT NULL, `updated_date` DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=latin1");
        if ($db->table('aa_gst_settings')->countAllResults() === 0) {
            $db->table('aa_gst_settings')->insert(['id' => 1, 'cgst' => 2.5, 'sgst' => 2.5, 'igst' => 0, 'updated_date' => date('Y-m-d H:i:s')]);
        }
    }

    public function defaults(): array
    {
        $this->ensureTable();
        $r = $this->db()->table('aa_gst_settings')->where('id', 1)->get()->getRow();
        if (! $r) { return ['cgst' => '2.5', 'sgst' => '2.5', 'igst' => '0']; }
        return [
            'cgst' => rtrim(rtrim((string) $r->cgst, '0'), '.'),
            'sgst' => rtrim(rtrim((string) $r->sgst, '0'), '.'),
            'igst' => rtrim(rtrim((string) $r->igst, '0'), '.'),
        ];
    }

    public function save($cgst, $sgst, $igst, ?int $uid = null): bool
    {
        $this->ensureTable();
        $this->db()->table('aa_gst_settings')->where('id', 1)->update([
            'cgst' => (float) $cgst, 'sgst' => (float) $sgst, 'igst' => (float) $igst,
            'updated_by' => $uid, 'updated_date' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }
}
