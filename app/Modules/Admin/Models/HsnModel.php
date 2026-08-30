<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * HsnModel — CI4 port of the HSN Code Master write/read core (admin/Hsn).
 * hsn_codes is a GLOBAL master (no template scope); rows are soft-deleted
 * (status='Delete'). Feeds the invoice/stock HSN pickers via get_hsn_code(),
 * whose cache is invalidated (cr_forget) on every write.
 */
class HsnModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Active + Inactive rows for the listing (excludes soft-deleted). */
    public function listRows(?string $search = null): array
    {
        $b = $this->db()->table('hsn_codes')
            ->where('status !=', 'Delete')
            ->orderBy('id', 'desc');
        if ($search) {
            $b->groupStart()->like('hsn_code', $search)->orLike('product_name', $search)->groupEnd();
        }
        return $b->get()->getResult();
    }

    public function countActive(): int
    {
        return $this->db()->table('hsn_codes')->where('status !=', 'Delete')->countAllResults();
    }

    public function find(int $id)
    {
        return $this->db()->table('hsn_codes')->where('id', $id)->get()->getRow();
    }

    /** Is this hsn_code already used by another non-deleted row? */
    public function isDuplicate(string $hsnCode, int $exceptId = 0): bool
    {
        $b = $this->db()->table('hsn_codes')->where('hsn_code', $hsnCode)->where('status !=', 'Delete');
        if ($exceptId > 0) {
            $b->where('id !=', $exceptId);
        }
        return (bool) $b->get()->getRow();
    }

    /** Insert or update. Returns the row id. Invalidates the picker cache. */
    public function saveRow(array $data, int $id = 0): int
    {
        helper('cr_cache');
        $now = date('Y-m-d H:i:s');

        if ($id > 0) {
            $this->db()->table('hsn_codes')->where('id', $id)->update([
                'hsn_code'     => $data['hsn_code'],
                'product_name' => $data['product_name'],
                'map_account'  => $data['map_account'],
                'status'       => $data['status'],
                'updated_date' => $now,
            ]);
            cr_forget('mst_hsn_active');
            return $id;
        }

        $this->db()->table('hsn_codes')->insert([
            'hsn_code'     => $data['hsn_code'],
            'product_name' => $data['product_name'],
            'map_account'  => $data['map_account'],
            'icon'         => null,
            'status'       => $data['status'],
            'added_by'     => (int) (currentuserinfo()->id ?? 0),
            'added_date'   => $now,
            'updated_date' => $now,
            'account_id'   => fy()->template_id,
        ]);
        cr_forget('mst_hsn_active');
        return (int) $this->db()->insertID();
    }

    /** Soft-delete (status='Delete') + audit. Invalidates the picker cache. */
    public function softDelete(int $id, string $reason = ''): bool
    {
        helper('cr_cache');
        $this->db()->table('hsn_codes')->where('id', $id)->update([
            'status'        => 'Delete',
            'delete_reason' => $reason,
            'deleted_by'    => (int) (currentuserinfo()->id ?? 0),
            'deleted_date'  => date('Y-m-d H:i:s'),
        ]);
        cr_forget('mst_hsn_active');
        return $this->db()->affectedRows() > 0;
    }
}
