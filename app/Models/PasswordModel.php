<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Password Manager vault records. Company-scoped: every query filters by the
 * active company so one company's credentials never leak into another's.
 * `created_by` records the author. The password itself lives in `password_enc`
 * (encrypted — see App\Libraries\PasswordVault) and is never selected into a
 * listing; it is only decrypted on an explicit reveal.
 */
class PasswordModel extends Model
{
    protected $table          = 'passwords';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'company_id', 'title', 'website', 'username', 'password_enc',
        'notes', 'category', 'created_by',
    ];

    protected $validationRules = [
        'title' => 'required|min_length[1]|max_length[191]',
    ];

    /** Scoped builder for the active company (newest first by default). */
    public function scoped(?int $companyId)
    {
        $b = $this->where('passwords.deleted_at', null);
        if ($companyId !== null) {
            $b->where('passwords.company_id', $companyId);
        }
        return $b;
    }

    /** Find one record, honouring the company scope. */
    public function findForCompany(int $id, ?int $companyId): ?array
    {
        $row = $this->find($id);
        if (! $row) {
            return null;
        }
        if ($companyId !== null && (int) $row['company_id'] !== $companyId) {
            return null;
        }
        return $row;
    }

    /**
     * Distinct, non-empty categories used in the active company — powers the
     * category filter dropdown.
     *
     * @return list<string>
     */
    public function categories(?int $companyId): array
    {
        $b = $this->builder()
            ->select('category')
            ->where('deleted_at', null)
            ->where('category IS NOT NULL')
            ->where("category !=", '')
            ->groupBy('category')
            ->orderBy('category', 'ASC');
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return array_values(array_filter(array_map(
            static fn ($r) => (string) $r['category'],
            $b->get()->getResultArray()
        )));
    }
}
