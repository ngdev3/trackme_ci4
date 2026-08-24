<?php

namespace Modules\Api\Controllers;

use App\Models\CompanyModel;
use App\Models\CompanyUserModel;
use App\Models\ModuleModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;
use App\Models\UserModel;
use App\Models\UserPermissionModel;

/**
 * Session context for the mobile app.
 *
 *   GET  /api/v1/me                     (Bearer) [?company_id=]
 *   POST /api/v1/company/switch         (Bearer) {company_id}
 *
 * Returns the same authorization facts the web app resolves per user + active
 * company — companies, active company + role, package feature flags and the
 * module→actions permission map — so the app can gate menus/screens exactly
 * like the backend. Reuses existing models + the subscription helper; it changes
 * no business logic.
 */
class MeApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    /** Resolve + authorise the active company for the caller (optional override). */
    private function activeCompany(array $user): ?array
    {
        $companies = (new CompanyModel())->forUser((int) $user['id']);
        if ($companies === []) {
            return null;
        }
        $requested = (int) ($this->input('company_id') ?? $this->request->getGet('company_id') ?? 0);
        if ($requested > 0) {
            foreach ($companies as $c) {
                if ((int) $c['id'] === $requested) {
                    return $c;
                }
            }
        }
        return $companies[0];
    }

    /** True if any of the user's roles is flagged super admin (mirrors Auth). */
    private function isSuperAdmin(array $roleIds): bool
    {
        if ($roleIds === []) {
            return false;
        }
        return (new RoleModel())->whereIn('id', $roleIds)->where('is_superadmin', 1)->countAllResults() > 0;
    }

    public function me()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        return $this->respond($this->context($user));
    }

    /**
     * Update the caller's own profile (name / email / mobile). Username and role
     * stay fixed — those are managed elsewhere. Reuses UserModel validation
     * (email uniqueness excludes the caller via the {id} placeholder).
     */
    public function updateProfile()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        $id     = (int) $user['id'];
        $mobile = $this->input('mobile', $user['mobile']);
        $mobile = is_string($mobile) ? trim($mobile) : $mobile;

        $data = [
            'name'   => trim((string) $this->input('name', $user['name'])),
            'email'  => trim((string) $this->input('email', $user['email'])),
            'mobile' => ($mobile === '' || $mobile === null) ? null : $mobile,
        ];

        // Validate with the caller's own id baked into the uniqueness rule so it
        // excludes their existing row (mirrors the web ProfileController).
        $rules = [
            'name'   => 'required|min_length[2]|max_length[150]',
            'email'  => "required|valid_email|max_length[191]|is_unique[users.email,id,{$id}]",
            'mobile' => 'permit_empty|max_length[20]',
        ];
        $messages = ['email' => ['is_unique' => 'This email is already registered.']];

        $validation = \Config\Services::validation();
        if (! $validation->setRules($rules, $messages)->run($data)) {
            return $this->failValidationErrors($validation->getErrors());
        }

        $userModel = new UserModel();
        $userModel->skipValidation(true)->update($id, $data);

        $fresh = $userModel->find($id);
        return $this->respond(['status' => 'ok', 'user' => $this->publicUser($fresh)]);
    }

    /**
     * Raise a request to delete the caller's account. This does NOT delete
     * anything — it files the request in the inquiries inbox for a super admin to
     * review and action (self-service deletion is a hard delete for the user but a
     * soft delete for the system; only a super admin can reactivate, on request).
     * Idempotent-ish: a fresh pending request per submit is fine (staff triage).
     *
     *   POST /api/v1/me/deletion-request  (Bearer) {reason?}
     */
    public function deletionRequest()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        // Light throttle so a stuck button can't spam the inbox.
        if ($this->tooManyAttempts('del-req-' . (int) $user['id'], 3, 10 * MINUTE)) {
            return $this->fail('You have already sent a request. Our team will get back to you shortly.', 429);
        }

        $reason = trim((string) ($this->input('reason') ?? ''));
        if (mb_strlen($reason) > 1000) {
            $reason = mb_substr($reason, 0, 1000);
        }

        $uid   = (int) $user['id'];
        $name  = (string) ($user['name'] ?? $user['username'] ?? 'User');
        $email = (string) ($user['email'] ?? '');

        $requests = new \App\Models\AccountDeletionRequestModel();

        // One open request at a time — resubmits just report the existing one.
        if ($requests->hasPending($uid)) {
            return $this->respond([
                'status'  => 'ok',
                'message' => 'Your account deletion request is already pending review. Our team will get back to you.',
            ]);
        }

        $requests->insert([
            'user_id'    => $uid,
            'name'       => mb_substr($name, 0, 150),
            'email'      => mb_substr($email, 0, 190),
            'mobile'     => isset($user['mobile']) ? mb_substr((string) $user['mobile'], 0, 20) : null,
            'reason'     => $reason !== '' ? $reason : null,
            'source'     => 'app',
            'status'     => 'pending',
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => mb_substr((string) $this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);

        log_message('info', 'Account deletion requested by user #{id}', ['id' => $uid]);

        return $this->respond([
            'status'  => 'ok',
            'message' => 'Your account deletion request has been submitted. Our team will review it and get back to you.',
        ]);
    }

    // --- Support: one ongoing conversation per user --------------------------

    /** GET /api/v1/me/support — the user's single support thread (clears unread). */
    public function support()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $svc  = new \App\Services\SupportConversation();
        $conv = $svc->getFor($user);
        if (! $conv) {
            return $this->respond(['status' => 'ok', 'has_thread' => false, 'unread' => false, 'open' => true, 'messages' => []]);
        }
        (new \App\Models\InquiryModel())->update((int) $conv['id'], ['customer_unread' => 0]);
        return $this->respond([
            'status'     => 'ok',
            'has_thread' => true,
            'unread'     => false,
            'open'       => $conv['status'] !== 'closed',
            'messages'   => $svc->messages($conv),
        ]);
    }

    /** POST /api/v1/me/support {message} — append a message (creates on first use). */
    public function supportSend()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $message = trim((string) ($this->input('message') ?? ''));
        if ($message === '') {
            return $this->fail('Please type a message.', 422);
        }
        (new \App\Services\SupportConversation())->appendCustomer($user, $message, (string) ($this->input('subject') ?? ''), [
            'ip' => $this->request->getIPAddress(),
            'ua' => (string) $this->request->getUserAgent()->getAgentString(),
        ]);
        return $this->respond(['status' => 'ok', 'message' => 'Message sent to support.']);
    }

    /** Validate a company switch and return the refreshed context for it. */
    public function switchCompany()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $companyId = (int) $this->input('company_id', 0);
        if ($companyId <= 0 || ! (new CompanyUserModel())->isMember($companyId, (int) $user['id'])) {
            return $this->failValidationErrors('You are not a member of that company.');
        }
        return $this->respond($this->context($user, $companyId));
    }

    /** Assemble the full session context payload. */
    private function context(array $user, ?int $forceCompanyId = null): array
    {
        $userModel = new UserModel();
        $roleIds   = $userModel->roleIds((int) $user['id']);
        $isSuper   = $this->isSuperAdmin($roleIds);

        $allCompanies = (new CompanyModel())->forUser((int) $user['id']);
        $members      = new CompanyUserModel();

        // Active company: forced (switch) → requested (?company_id) → first.
        $active = null;
        if ($forceCompanyId) {
            foreach ($allCompanies as $c) {
                if ((int) $c['id'] === $forceCompanyId) {
                    $active = $c;
                    break;
                }
            }
        }
        $active   = $active ?? $this->activeCompany($user);
        $activeId = $active ? (int) $active['id'] : null;

        // Stamp "last opened" for the company that is now the active one, so the
        // app can show a per-company Last-active time (mobile settings / companies
        // list) and sort by most-recently-used. Fires on both /me and switch.
        if ($activeId) {
            $members->touchActive($activeId, (int) $user['id']);
        }

        // Per-company last-active (read AFTER the touch above so the active
        // company reflects "just now").
        $lastActive = $members->lastActiveMap((int) $user['id']);

        // Company list with the caller's role in each. forUser() already joins
        // company_users.role AS membership_role, so read it from the row instead
        // of a per-company membership() query (was an N+1 over the company list).
        $companies = [];
        foreach ($allCompanies as $c) {
            $isOwner = (int) $c['owner_id'] === (int) $user['id'];
            $companies[] = [
                'id'             => (int) $c['id'],
                'name'           => $c['name'],
                'state'          => $c['state'] ?? null,
                'gst_number'     => $c['gst_number'] ?? null,
                'business_type'  => $c['business_type'] ?? null,
                'role'           => $c['membership_role'] ?? ($isOwner ? 'owner' : 'staff'),
                'is_owner'       => $isOwner,
                'created_at'     => $c['created_at'] ?? null,
                'last_active_at' => $lastActive[(int) $c['id']] ?? null,
            ];
        }

        $activeRole = null;
        if ($active) {
            $activeRole = $active['membership_role'] ?? ((int) $active['owner_id'] === (int) $user['id'] ? 'owner' : 'staff');
        }

        // Package features for the active company's owning customer.
        $ownerId  = $active ? (int) $active['owner_id'] : (int) $user['id'];
        $features = [];
        foreach (array_keys(feature_catalog()) as $feature) {
            $features[$feature] = $isSuper ? true : customer_has_feature($ownerId, $feature);
        }
        $plan = customer_effective_plan($ownerId);

        // Permission map: module code → granted actions (role ∪ user grants).
        $permissions = $this->permissions($roleIds, (int) $user['id'], $isSuper);

        return [
            'status'            => 'ok',
            'user'              => $this->publicUser($user),
            'is_superadmin'     => $isSuper,
            'active_company_id' => $activeId,
            'role'              => $activeRole,
            'companies'         => $companies,
            'features'          => $features,
            'permissions'       => $permissions,
            'plan'              => ['name' => $plan['name'] ?? null, 'code' => $plan['code'] ?? null],
        ];
    }

    /**
     * Build module_code → [actions] for every module the user can view, mirroring
     * Acl::actions() (role grants ∪ direct user grants; super admin = all).
     *
     * @return array<string, list<string>>
     */
    private function permissions(array $roleIds, int $userId, bool $isSuper): array
    {
        $modules   = new ModuleModel();
        $rolePerms = new RolePermissionModel();
        $userPerms = new UserPermissionModel();

        $allActions = ['view', 'add', 'edit', 'delete', 'print', 'export', 'approve'];
        $codes      = $modules->where('status', 1)->where('url IS NOT NULL')->findColumn('code') ?? [];

        if ($isSuper) {
            $map = [];
            foreach ($codes as $code) {
                $map[$code] = $allActions;
            }
            return $map;
        }

        // Batched: two queries total (role grants + user grants) for ALL modules,
        // instead of the previous per-module N+1 (2 * moduleCount queries).
        $roleMap = $rolePerms->actionsForModules($roleIds, $codes);
        $userMap = $userPerms->actionsForModules($userId, $codes);

        $map = [];
        foreach ($codes as $code) {
            $merged = array_values(array_unique(array_merge($roleMap[$code] ?? [], $userMap[$code] ?? [])));
            if ($merged !== []) {
                $map[$code] = $merged;
            }
        }
        return $map;
    }
}
