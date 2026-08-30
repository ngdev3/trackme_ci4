<?php

namespace App\Modules\Master\Models;

use Config\Database;

/**
 * TaxModel — CI4 port of master/Tax. GST-rate lookup: aa_tax
 * (tax_id, cgst, sgst, gst percentages, status). Soft-deleted.
 */
class TaxModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function listRows(): array
    {
        return $this->db()->table('aa_tax')
            ->where("COALESCE(status,'') != 'Delete'", null, false)
            ->orderBy('tax_id', 'desc')->get()->getResult();
    }

    public function countActive(): int
    {
        return $this->db()->table('aa_tax')->where("COALESCE(status,'') != 'Delete'", null, false)->countAllResults();
    }

    public function find(int $id)
    {
        return $this->db()->table('aa_tax')->where('tax_id', $id)->get()->getRow();
    }

    public function saveRow(array $data, int $id = 0): int
    {
        $now = date('Y-m-d H:i:s');
        if ($id > 0) {
            $this->db()->table('aa_tax')->where('tax_id', $id)->update([
                'cgst' => $data['cgst'], 'sgst' => $data['sgst'], 'gst' => $data['gst'],
                'status' => $data['status'], 'updated_date' => $now,
            ]);
            return $id;
        }
        $this->db()->table('aa_tax')->insert([
            'cgst' => $data['cgst'], 'sgst' => $data['sgst'], 'gst' => $data['gst'],
            'status' => $data['status'], 'added_date' => $now, 'updated_date' => $now,
        ]);
        return (int) $this->db()->insertID();
    }

    public function softDelete(int $id): bool
    {
        $this->db()->table('aa_tax')->where('tax_id', $id)->update(['status' => 'Delete']);
        return $this->db()->affectedRows() > 0;
    }
}
