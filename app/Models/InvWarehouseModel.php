<?php

namespace App\Models;

use CodeIgniter\Model;

/** Inventory warehouse / godown master — company-scoped. */
class InvWarehouseModel extends Model
{
    protected $table          = 'inv_warehouses';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['company_id', 'name', 'location', 'capacity', 'status'];

    public function forCompany(?int $companyId): array
    {
        $b = $this->where('status', 1);
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return $b->orderBy('name', 'ASC')->findAll();
    }

    public function findForCompany(int $id, ?int $companyId): ?array
    {
        $row = $this->find($id);
        if (! $row) {
            return null;
        }
        return ($companyId === null || (int) $row['company_id'] === $companyId) ? $row : null;
    }
}
