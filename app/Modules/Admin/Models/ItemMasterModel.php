<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * ItemMasterModel — CI4 port of admin/models/Item_master_mod. Item/product
 * catalog for the Stock module, backed by the global `hsn_codes` table (+ a
 * `unit` column, self-healed). Soft delete: status='Delete'. Every write clears
 * the get_hsn_code() picker cache (cr_forget 'mst_hsn_active').
 */
class ItemMasterModel
{
    private string $table = 'hsn_codes';

    protected function db()
    {
        return Database::connect();
    }

    public function ensureColumns(): void
    {
        if (! $this->db()->fieldExists('unit', $this->table)) {
            $this->db()->query("ALTER TABLE `{$this->table}` ADD COLUMN `unit` VARCHAR(20) NULL DEFAULT 'qtl' AFTER `product_name`");
        }
    }

    public function stats(): array
    {
        $this->ensureColumns();
        $r = $this->db()->query("SELECT
                SUM(status='Active') AS active, SUM(status='Inactive') AS inactive,
                SUM(status='Delete') AS trashed, SUM(status<>'Delete') AS live,
                SUM(status<>'Delete' AND hsn_code IS NOT NULL AND hsn_code<>'') AS with_hsn
            FROM `{$this->table}`")->getRow();
        return [
            'active'   => (int) $r->active, 'inactive' => (int) $r->inactive,
            'trashed'  => (int) $r->trashed, 'live' => (int) $r->live, 'with_hsn' => (int) $r->with_hsn,
        ];
    }

    public function getAll(string $statusFilter = ''): array
    {
        $b = $this->db()->table($this->table . ' h')
            ->select("h.id, h.product_name, h.unit, h.hsn_code, h.status, h.added_date, h.updated_date,
                TRIM(CONCAT(COALESCE(u.first_name,''),' ',COALESCE(u.last_name,''))) as added_by_name", false)
            ->join('users u', 'u.id = h.added_by', 'left');
        if ($statusFilter === 'Delete') {
            $b->where('h.status', 'Delete');
        } elseif ($statusFilter === 'Active' || $statusFilter === 'Inactive') {
            $b->where('h.status', $statusFilter);
        } else {
            $b->where('h.status <>', 'Delete');
        }
        return $b->orderBy('h.product_name', 'ASC')->get()->getResult();
    }

    public function getOne(int $id)
    {
        return $this->db()->table($this->table)->where('id', $id)->get()->getRow();
    }

    public function nameExists(string $name, ?int $excludeId = null): bool
    {
        $b = $this->db()->table($this->table)
            ->where('LOWER(product_name)', strtolower(trim($name)))
            ->where('status <>', 'Delete');
        if ($excludeId) { $b->where('id <>', $excludeId); }
        return $b->countAllResults() > 0;
    }

    public function add(array $data): int
    {
        helper('cr_cache');
        $data['added_date'] = date('Y-m-d H:i:s');
        $this->db()->table($this->table)->insert($data);
        cr_forget('mst_hsn_active');
        return (int) $this->db()->insertID();
    }

    public function update(int $id, array $data): int
    {
        helper('cr_cache');
        $data['updated_date'] = date('Y-m-d H:i:s');
        $this->db()->table($this->table)->where('id', $id)->update($data);
        cr_forget('mst_hsn_active');
        return $this->db()->affectedRows();
    }

    public function softDelete(int $id, ?int $userId = null): bool
    {
        helper('cr_cache');
        $this->db()->table($this->table)->where('id', $id)->update([
            'status' => 'Delete', 'deleted_by' => $userId, 'deleted_date' => date('Y-m-d H:i:s'),
        ]);
        cr_forget('mst_hsn_active');
        return true;
    }

    public function setStatus(int $id, string $status): bool
    {
        helper('cr_cache');
        $status = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
        $this->db()->table($this->table)->where('id', $id)->update([
            'status' => $status, 'updated_date' => date('Y-m-d H:i:s'),
        ]);
        cr_forget('mst_hsn_active');
        return true;
    }

    public function restore(int $id): bool
    {
        helper('cr_cache');
        $this->db()->table($this->table)->where('id', $id)->update([
            'status' => 'Active', 'deleted_by' => null, 'deleted_date' => null, 'updated_date' => date('Y-m-d H:i:s'),
        ]);
        cr_forget('mst_hsn_active');
        return true;
    }
}
