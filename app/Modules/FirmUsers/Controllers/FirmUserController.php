<?php

namespace Modules\FirmUsers\Controllers;

use App\Controllers\BaseController;
use App\Models\CompanyModel;
use App\Models\CompanyUserModel;
use App\Models\UserModel;
use Config\Services;

/**
 * Firm user management. A customer (firm owner/admin) creates and manages the
 * users of their ACTIVE firm — name, email, mobile, firm role, per-module
 * permissions and active status. Every firm user is scoped to that one firm
 * (a company_users membership) so they can only ever access their firm.
 */
class FirmUserController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected UserModel $users;
    protected CompanyUserModel $members;

    public function __construct()
    {
        $this->users   = new UserModel();
        $this->members = new CompanyUserModel();
    }

    /** Only the firm owner/admin may manage firm users; needs an active firm. */
    private function guard()
    {
        $companyId = company_id();
        if (! $companyId) {
            return redirect()->to(site_url('company/create'))->with('error', 'Create a firm first.');
        }
        if (! in_array(company_role(), ['owner', 'admin'], true)) {
            return redirect()->to(site_url('dashboard'))->with('error', 'You do not have access to manage firm users.');
        }
        return null;
    }

    private function firm(): array
    {
        return (new CompanyModel())->find(company_id());
    }

    /**
     * The firm owner account can only be changed by the owner. Without this an
     * admin could demote (role → viewer), disable (status = 0) or delete the
     * account holder and lock them out of their own firm. Returns a redirect to
     * block the action, or null to allow it.
     */
    private function protectOwner(int $targetUserId)
    {
        $ownerId = (int) ($this->firm()['owner_id'] ?? 0);
        if ($ownerId === $targetUserId && company_role() !== 'owner') {
            return redirect()->to(site_url('firm-users'))
                ->with('error', 'Only the firm owner can change the owner account.');
        }
        return null;
    }

    public function index()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        return $this->render('index', [
            'title'      => 'Firm Users',
            'breadcrumb' => [['label' => 'Firm'], ['label' => 'Users']],
            'firm'       => $this->firm(),
            'rows'       => $this->members->firmUsers(company_id()),
        ]);
    }

    public function create()
    {
        if ($r = $this->guard()) {
            return $r;
        }
        return $this->render('form', $this->formData(null, null, 'create'));
    }

    public function edit($id = null)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $membership = $this->members->membership(company_id(), (int) $id === 0 ? -1 : (int) $id);
        // $id is the user id; look up membership by user in this firm.
        $membership = $this->members->where('company_id', company_id())->where('user_id', (int) $id)->first();
        if (! $membership) {
            return redirect()->to(site_url('firm-users'))->with('error', 'Firm user not found.');
        }
        return $this->render('form', $this->formData($this->users->find((int) $id), $membership, 'edit'));
    }

    private function formData(?array $user, ?array $membership, string $mode): array
    {
        $overrides = [];
        if ($membership && ! empty($membership['permissions'])) {
            $decoded = json_decode((string) $membership['permissions'], true);
            $overrides = is_array($decoded) ? $decoded : [];
        }
        return [
            'title'      => $mode === 'edit' ? 'Edit Firm User' : 'Add Firm User',
            'breadcrumb' => [['label' => 'Firm Users', 'url' => site_url('firm-users')], ['label' => $mode === 'edit' ? 'Edit' : 'Add']],
            'user'       => $user,
            'membership' => $membership,
            'mode'       => $mode,
            'overrides'  => $overrides,
            'errors'     => session()->getFlashdata('errors') ?? [],
        ];
    }

    public function store()
    {
        if ($r = $this->guard()) {
            return $r;
        }

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[8]',
            'mobile'   => 'permit_empty|max_length[20]',
            'role'     => 'required|in_list[' . implode(',', array_keys(firm_roles())) . ']',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $firm  = $this->firm();
        $email = (string) $this->request->getPost('email');

        $userId = $this->users->insert([
            'name'         => $this->request->getPost('name'),
            'email'        => $email,
            'mobile'       => $this->request->getPost('mobile'),
            'username'     => $this->users->generateUniqueUsername($email),
            'password'     => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'account_type' => 'firm_user',
            'auth_provider'=> 'local',
            'status'       => (int) ($this->request->getPost('status') ?? 1),
        ], true);

        if (! $userId) {
            return redirect()->back()->withInput()->with('errors', $this->users->errors());
        }

        // Base app role so they can reach dashboard/notes/reminders (app RBAC);
        // firm-module access is governed separately by their firm role.
        if ($viewer = (new \App\Models\RoleModel())->where('code', 'viewer')->first()) {
            $this->users->syncRoles((int) $userId, [(int) $viewer['id']]);
        }

        $this->members->insert([
            'company_id'  => company_id(),
            'customer_id' => (int) $firm['owner_id'],
            'user_id'     => (int) $userId,
            'role'        => (string) $this->request->getPost('role'),
            'permissions' => $this->permissionsJson(),
            'status'      => 1,
        ]);

        activity_log('FirmUsers', 'Add', "Firm user #{$userId} added to firm #" . company_id());
        return redirect()->to(site_url('firm-users'))->with('success', 'Firm user created.');
    }

    public function update($id = null)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $id         = (int) $id;
        $membership = $this->members->where('company_id', company_id())->where('user_id', $id)->first();
        if (! $membership) {
            return redirect()->to(site_url('firm-users'))->with('error', 'Firm user not found.');
        }
        if ($r = $this->protectOwner($id)) {
            return $r;
        }

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => "required|valid_email|is_unique[users.email,id,{$id}]",
            'password' => 'permit_empty|min_length[8]',
            'role'     => 'required|in_list[' . implode(',', array_keys(firm_roles())) . ']',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'   => $this->request->getPost('name'),
            'email'  => $this->request->getPost('email'),
            'mobile' => $this->request->getPost('mobile'),
            'status' => (int) ($this->request->getPost('status') ?? 1),
        ];
        if ($pwd = (string) $this->request->getPost('password')) {
            $data['password'] = password_hash($pwd, PASSWORD_DEFAULT);
        }
        $this->users->skipValidation(true)->update($id, $data);

        $this->members->update($membership['id'], [
            'role'        => (string) $this->request->getPost('role'),
            'permissions' => $this->permissionsJson(),
            'status'      => (int) ($this->request->getPost('status') ?? 1),
        ]);

        activity_log('FirmUsers', 'Edit', "Firm user #{$id} updated in firm #" . company_id());
        return redirect()->to(site_url('firm-users'))->with('success', 'Firm user updated.');
    }

    public function delete($id = null)
    {
        if ($r = $this->guard()) {
            return $r;
        }
        $id         = (int) $id;
        $membership = $this->members->where('company_id', company_id())->where('user_id', $id)->first();
        if (! $membership) {
            return redirect()->to(site_url('firm-users'))->with('error', 'Firm user not found.');
        }
        if ($r = $this->protectOwner($id)) {
            return $r;
        }
        $this->members->delete($membership['id']);
        // Deactivate the login if they belong to no other firm.
        if ($this->members->where('user_id', $id)->countAllResults() === 0) {
            $this->users->update($id, ['status' => 0]);
        }
        activity_log('FirmUsers', 'Delete', "Firm user #{$id} removed from firm #" . company_id());
        return redirect()->to(site_url('firm-users'))->with('success', 'Firm user removed.');
    }

    /** JSON of checked firm-module overrides, or null to fall back to the role. */
    private function permissionsJson(): ?string
    {
        $perms = array_values(array_filter((array) $this->request->getPost('permissions')));
        return $perms !== [] ? json_encode($perms) : null;
    }
}
