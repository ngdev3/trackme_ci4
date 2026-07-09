<?php

namespace App\Models;

use CodeIgniter\Model;

/** Inventory party master (supplier / farmer / customer) — company-scoped. */
class InvPartyModel extends Model
{
    protected $table          = 'inv_parties';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['company_id', 'name', 'type', 'phone', 'status'];

    /**
     * Active parties for a company. $role filters by who can supply / buy:
     * 'supplier' → supplier|both, 'customer' → customer|both, null → all.
     */
    public function forCompany(?int $companyId, ?string $role = null): array
    {
        $b = $this->where('status', 1);
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        if ($role === 'supplier') {
            $b->groupStart()->where('type', 'supplier')->orWhere('type', 'both')->groupEnd();
        } elseif ($role === 'customer') {
            $b->groupStart()->where('type', 'customer')->orWhere('type', 'both')->groupEnd();
        }
        return $b->orderBy('name', 'ASC')->findAll();
    }

    /** Find an existing party by name (case-insensitive) or create it. Returns id. */
    public function findOrCreate(int $companyId, string $name, string $type = 'both'): int
    {
        $name = trim($name);
        if ($name === '') {
            return 0;
        }
        $row = $this->where('company_id', $companyId)->where('LOWER(name)', strtolower($name))->first();
        if ($row) {
            return (int) $row['id'];
        }
        return (int) $this->insert(['company_id' => $companyId, 'name' => $name, 'type' => $type, 'status' => 1]);
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
