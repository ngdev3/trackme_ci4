<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountingGroupModel extends Model
{
    protected $table         = 'accounting_groups';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['company_id', 'name', 'nature', 'parent', 'is_default'];

    public function forCompany(int $companyId): array
    {
        return $this->where('company_id', $companyId)->orderBy('id', 'ASC')->findAll();
    }
}
