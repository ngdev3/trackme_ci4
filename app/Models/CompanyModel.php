<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyModel extends Model
{
    protected $table          = 'companies';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'owner_id', 'name', 'financial_year_from', 'books_beginning_from',
        'state', 'country', 'gst_registration_type', 'gst_number',
        'address', 'mobile', 'email', 'business_type', 'status',
    ];

    /** Indian GSTIN: 2-digit state + 10-char PAN + entity + Z + checksum. */
    public const GST_REGEX = '/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Za-z]{1}Z[0-9A-Za-z]{1}$/';

    /** Does the user already own a company with this (case-insensitive) name? */
    public function nameTakenByUser(string $name, int $ownerId, ?int $exceptId = null): bool
    {
        $b = $this->where('owner_id', $ownerId)->where('LOWER(name)', strtolower(trim($name)));
        if ($exceptId) {
            $b->where('id !=', $exceptId);
        }
        return $b->countAllResults() > 0;
    }

    /**
     * Total companies OWNED by a customer, INCLUDING soft-deleted ones in Trash.
     * This is the figure the subscription firm-limit is measured against: a
     * company sitting in the Recycle Bin still occupies a slot until it is
     * permanently removed.
     */
    public function totalOwned(int $ownerId): int
    {
        return $this->withDeleted()->where('owner_id', $ownerId)->countAllResults();
    }

    /** Active (non-trashed) companies owned by a customer. */
    public function activeOwned(int $ownerId): int
    {
        return $this->where('owner_id', $ownerId)->countAllResults();
    }

    /** Companies a user is a member of (any role), newest first. */
    public function forUser(int $userId): array
    {
        return $this->select('companies.*, company_users.role AS membership_role')
            ->join('company_users', 'company_users.company_id = companies.id')
            ->where('company_users.user_id', $userId)
            ->where('company_users.status', 1)
            ->orderBy('companies.id', 'DESC')
            ->findAll();
    }

    /** Soft-deleted (trashed) companies a user belongs to, most recent first. */
    public function trashedForUser(int $userId): array
    {
        return $this->onlyDeleted()
            ->select('companies.*, company_users.role AS membership_role')
            ->join('company_users', 'company_users.company_id = companies.id')
            ->where('company_users.user_id', $userId)
            ->orderBy('companies.deleted_at', 'DESC')
            ->findAll();
    }

    /** All soft-deleted companies (Super Admin view). */
    public function trashedAll(): array
    {
        return $this->onlyDeleted()->orderBy('deleted_at', 'DESC')->findAll();
    }
}
