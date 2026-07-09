<?php

namespace App\Models;

use CodeIgniter\Model;

class CompanyUserModel extends Model
{
    protected $table         = 'company_users';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['company_id', 'customer_id', 'user_id', 'role', 'permissions', 'status'];

    /** owner = the customer; the rest are assignable firm-user roles. */
    public const ROLES = ['owner', 'admin', 'accountant', 'sales', 'purchase', 'inventory', 'viewer'];

    /** Active firm-user memberships for a firm, joined to the user record. */
    public function firmUsers(int $companyId): array
    {
        return $this->select('company_users.*, users.name, users.email, users.mobile, users.status AS user_status, users.last_login_at')
            ->join('users', 'users.id = company_users.user_id')
            ->where('company_users.company_id', $companyId)
            ->where('company_users.role !=', 'owner')
            ->orderBy('users.name', 'ASC')
            ->findAll();
    }

    /** The membership row for a user in a company, or null. */
    public function membership(int $companyId, int $userId): ?array
    {
        return $this->where('company_id', $companyId)->where('user_id', $userId)->first();
    }

    /** Is the user a member (active) of the company? */
    public function isMember(int $companyId, int $userId): bool
    {
        return $this->where('company_id', $companyId)->where('user_id', $userId)->where('status', 1)->countAllResults() > 0;
    }
}
