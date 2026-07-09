<?php

namespace App\Models;

use CodeIgniter\Model;

/** Inventory product master — company-scoped. */
class InvProductModel extends Model
{
    protected $table          = 'inv_products';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['company_id', 'name', 'unit', 'avg_weight', 'rate', 'sku', 'low_stock', 'status'];

    /** Active products for a company (for dropdowns), alphabetical. */
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
