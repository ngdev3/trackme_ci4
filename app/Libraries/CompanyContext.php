<?php

namespace App\Libraries;

use App\Models\CompanyModel;
use App\Models\CompanyUserModel;
use Config\Services;

/**
 * Tracks the signed-in user's "active company" in the session and enforces
 * company-wise access: a user may only make a company active if they are a
 * member of it. This is the single seam every company-scoped feature should use
 * to read the current company id, so one company's data never leaks into
 * another's.
 */
class CompanyContext
{
    protected CompanyModel $companies;
    protected CompanyUserModel $members;
    protected $session;

    public function __construct()
    {
        $this->companies = new CompanyModel();
        $this->members   = new CompanyUserModel();
        $this->session   = Services::session();
    }

    /**
     * The active company id for the current user, lazily resolved: keep the
     * session value if it is still a valid membership, otherwise fall back to
     * the user's first company, otherwise null (needs onboarding).
     */
    public function activeId(): ?int
    {
        $uid = (int) (Services::session()->get('user_id') ?? 0);
        if ($uid === 0) {
            return null;
        }

        $id = (int) ($this->session->get('company_id') ?? 0);
        if ($id > 0 && $this->members->isMember($id, $uid)) {
            return $id;
        }

        $companies = $this->companies->forUser($uid);
        if ($companies !== []) {
            $this->setActive((int) $companies[0]['id']);
            return (int) $companies[0]['id'];
        }

        $this->clear();
        return null;
    }

    /** Make a company active, if the current user belongs to it. */
    public function setActive(int $companyId): bool
    {
        $uid = (int) (Services::session()->get('user_id') ?? 0);
        if ($uid === 0 || ! $this->members->isMember($companyId, $uid)) {
            return false;
        }
        $company    = $this->companies->find($companyId);
        $membership = $this->members->membership($companyId, $uid);
        if (! $company) {
            return false;
        }
        $overrides = null;
        if (! empty($membership['permissions'])) {
            $decoded = json_decode((string) $membership['permissions'], true);
            $overrides = is_array($decoded) ? $decoded : null;
        }
        $this->session->set([
            'company_id'          => $companyId,
            'company_name'        => $company['name'],
            'company_role'        => $membership['role'] ?? 'viewer',
            'company_permissions' => $overrides,
        ]);
        return true;
    }

    /** The active company record, or null. */
    public function current(): ?array
    {
        $id = $this->activeId();
        return $id ? $this->companies->find($id) : null;
    }

    /** The current user's role in the active company (owner|admin|staff), or null. */
    public function role(): ?string
    {
        $this->activeId(); // ensure resolved
        return $this->session->get('company_role');
    }

    /** All companies the current user belongs to. */
    public function userCompanies(): array
    {
        $uid = (int) (Services::session()->get('user_id') ?? 0);
        return $uid ? $this->companies->forUser($uid) : [];
    }

    public function clear(): void
    {
        $this->session->remove(['company_id', 'company_name', 'company_role']);
    }
}
