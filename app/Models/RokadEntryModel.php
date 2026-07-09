<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Cash-book entries. All balances are derived here from the raw rows so they
 * are always correct after any add/edit/delete — nothing is cached.
 */
class RokadEntryModel extends Model
{
    protected $table          = 'rokad_entries';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = ['company_id', 'entry_date', 'particular', 'jama', 'naam', 'remarks', 'created_by'];

    protected $validationRules = [
        'particular' => 'required|min_length[1]|max_length[191]',
        'entry_date' => 'required|valid_date[Y-m-d]',
    ];

    public function findForCompany(int $id, int $companyId): ?array
    {
        return $this->where('company_id', $companyId)->find($id) ?: null;
    }

    /** All entries for a single day, in the order they were recorded. */
    public function dayEntries(int $companyId, string $date): array
    {
        return $this->where('company_id', $companyId)->where('entry_date', $date)
            ->orderBy('id', 'ASC')->findAll();
    }

    /** [jama, naam] totals for a single day. */
    public function dayTotals(int $companyId, string $date): array
    {
        $row = $this->select('COALESCE(SUM(jama),0) AS j, COALESCE(SUM(naam),0) AS n')
            ->where('company_id', $companyId)->where('entry_date', $date)->first();
        return [(float) ($row['j'] ?? 0), (float) ($row['n'] ?? 0)];
    }

    /**
     * Net (jama − naam) of all entries strictly before $date, optionally not
     * counting anything before the book's opening date. Used to derive a day's
     * opening balance = base opening + netBefore().
     */
    public function netBefore(int $companyId, string $date, ?string $sinceDate = null): float
    {
        $b = $this->select('COALESCE(SUM(jama - naam),0) AS net')
            ->where('company_id', $companyId)->where('entry_date <', $date);
        if ($sinceDate) {
            $b->where('entry_date >=', $sinceDate);
        }
        return (float) ($b->first()['net'] ?? 0);
    }

    /** Distinct dates that have entries (for navigation), newest first. */
    public function search(int $companyId, string $q, int $limit = 100): array
    {
        return $this->where('company_id', $companyId)
            ->groupStart()->like('particular', $q)->orLike('remarks', $q)->groupEnd()
            ->orderBy('entry_date', 'DESC')->orderBy('id', 'DESC')
            ->findAll($limit);
    }
}
