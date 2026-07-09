<?php

namespace App\Models;

use CodeIgniter\Model;

class LedgerModel extends Model
{
    protected $table          = 'ledgers';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'company_id', 'group_id', 'name', 'opening_balance', 'opening_type',
        'gst_number', 'contact', 'notes',
    ];

    protected $validationRules = [
        'name' => 'required|min_length[1]|max_length[150]',
    ];

    /** All ledgers for a firm, joined to their group name. */
    public function forCompany(int $companyId)
    {
        return $this->select('ledgers.*, accounting_groups.name AS group_name, accounting_groups.nature AS group_nature')
            ->join('accounting_groups', 'accounting_groups.id = ledgers.group_id', 'left')
            ->where('ledgers.company_id', $companyId)
            ->orderBy('accounting_groups.name', 'ASC')
            ->orderBy('ledgers.name', 'ASC');
    }

    public function findForCompany(int $id, int $companyId): ?array
    {
        return $this->where('company_id', $companyId)->find($id) ?: null;
    }

    /** Ledgers as id => name for select boxes. */
    public function optionsForCompany(int $companyId): array
    {
        $out = [];
        foreach ($this->where('company_id', $companyId)->orderBy('name', 'ASC')->findAll() as $l) {
            $out[(int) $l['id']] = $l['name'];
        }
        return $out;
    }
}
