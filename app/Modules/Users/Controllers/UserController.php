<?php

namespace Modules\Users\Controllers;

use App\Controllers\BaseController;
use App\Models\ModuleModel;
use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\UserPermissionModel;
use App\Models\UserTypeModel;
use Config\Database;

class UserController extends BaseController
{
    protected UserModel $users;
    protected string $moduleCode = 'users';
    protected string $baseRoute  = 'users';
    protected string $vns        = 'Modules\Users\Views\\';
    private array $superOnlyModuleCodes = ['logs', 'activity_logs', 'login_logs', 'roles', 'user_types', 'permissions'];

    public function __construct()
    {
        $this->users = new UserModel();
    }

    // ---------------------------------------------------------------
    // Control-hierarchy helpers
    // ---------------------------------------------------------------
    private function isSuper(): bool
    {
        return (bool) session()->get('is_superadmin');
    }

    private function requireUserPermission(string $action)
    {
        if ($this->isSuper() || can($this->moduleCode, $action)) {
            return null;
        }

        return redirect()->to(site_url('users'))->with('error', 'You do not have permission to manage users.');
    }

    /** Ids the current user may manage, or null for "everyone" (super admin). */
    private function manageableIds(): ?array
    {
        return $this->users->manageableIds((int) user_id(), $this->isSuper());
    }

    /** May the current user manage the target account? */
    private function canManage(int $targetId): bool
    {
        $ids = $this->manageableIds();
        return $ids === null || in_array($targetId, $ids, true);
    }

    /** Apply the Users-page visibility scope to a query builder. */
    private function applyUserScope($builder, string $alias = 'users')
    {
        $scope = $this->manageableIds();
        if ($scope !== null) {
            $builder->whereIn($alias . '.id', $scope ?: [-1]);
        }

        return $builder;
    }

    private function scopedUserCount(array $where = []): int
    {
        $builder = Database::connect()->table('users')->where('deleted_at', null);
        $this->applyUserScope($builder);
        foreach ($where as $field => $value) {
            $builder->where($field, $value);
        }
        return $builder->countAllResults();
    }

    private function scopedStats(): array
    {
        $total  = $this->scopedUserCount();
        $active = $this->scopedUserCount(['status' => 1]);
        $db     = Database::connect();

        $roles = $db->table('users')
            ->select('COUNT(DISTINCT user_roles.role_id) AS total', false)
            ->join('user_roles', 'user_roles.user_id = users.id', 'left')
            ->where('users.deleted_at', null);
        $this->applyUserScope($roles);

        $types = $db->table('users')
            ->select('COUNT(DISTINCT users.user_type_id) AS total', false)
            ->where('users.deleted_at', null)
            ->where('users.user_type_id IS NOT NULL');
        $this->applyUserScope($types);

        return [
            'total_users'    => $total,
            'active_users'   => $active,
            'inactive_users' => max(0, $total - $active),
            'total_roles'    => $this->isSuper() ? (int) ($roles->get()->getRowArray()['total'] ?? 0) : 0,
            'total_types'    => $this->isSuper() ? (int) ($types->get()->getRowArray()['total'] ?? 0) : 0,
        ];
    }

    private function scopedUsersByType(): array
    {
        $builder = Database::connect()->table('users')
            ->select('COALESCE(user_types.name, "Unassigned") AS label, COUNT(users.id) AS total')
            ->join('user_types', 'user_types.id = users.user_type_id', 'left')
            ->where('users.deleted_at', null)
            ->groupBy('users.user_type_id')
            ->orderBy('total', 'DESC');
        $this->applyUserScope($builder);
        $rows = $builder->get()->getResultArray();

        return [
            'labels' => array_map(static fn ($r) => $r['label'], $rows),
            'data'   => array_map(static fn ($r) => (int) $r['total'], $rows),
        ];
    }

    private function scopedUsersByRole(): array
    {
        $builder = Database::connect()->table('users')
            ->select('COALESCE(roles.name, "No Role") AS label, COUNT(DISTINCT users.id) AS total', false)
            ->join('user_roles', 'user_roles.user_id = users.id', 'left')
            ->join('roles', 'roles.id = user_roles.role_id', 'left')
            ->where('users.deleted_at', null)
            ->groupBy('roles.id')
            ->orderBy('total', 'DESC');
        $this->applyUserScope($builder);
        $rows = $builder->get()->getResultArray();

        return [
            'labels' => array_map(static fn ($r) => $r['label'], $rows),
            'data'   => array_map(static fn ($r) => (int) $r['total'], $rows),
        ];
    }

    private function scopedUserGrowth(int $months = 6): array
    {
        $labels = $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01 00:00:00', strtotime("-{$i} months"));
            $end   = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
            $labels[] = date('M Y', strtotime($start));

            $builder = Database::connect()->table('users')
                ->where('deleted_at', null)
                ->where('created_at >=', $start)
                ->where('created_at <=', $end);
            $this->applyUserScope($builder);
            $data[] = $builder->countAllResults();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /** Role ids the current user may assign (non-super cannot grant super-admin roles). */
    private function assignableRoleIds(): ?array
    {
        if ($this->isSuper()) {
            return null; // no restriction
        }
        return array_map(
            static fn ($r) => (int) $r['id'],
            (new RoleModel())->where('is_superadmin', 0)->findAll()
        );
    }

    // ---------------------------------------------------------------
    /**
     * Runs the sortable + scoped + searched + paginated query and returns the
     * data both the full page and the AJAX fragment need.
     */
    private function listData(): array
    {
        $search = trim((string) $this->request->getGet('q'));

        // Whitelisted sortable columns (key => real column) — prevents injection.
        $sortable = [
            'id'       => 'users.id',
            'name'     => 'users.name',
            'username' => 'users.username',
            'email'    => 'users.email',
            'mobile'   => 'users.mobile',
            'type'     => 'user_type_name',
            'status'   => 'users.status',
        ];
        $sort = (string) $this->request->getGet('sort');
        $sort = isset($sortable[$sort]) ? $sort : 'id';
        $dir  = strtolower((string) $this->request->getGet('dir')) === 'asc' ? 'asc' : 'desc';

        $builder = $this->users->withRelations()->orderBy($sortable[$sort], $dir);

        // Non-super admins only see relevant accounts they control.
        $this->applyUserScope($builder);

        if ($search !== '') {
            $builder->groupStart()
                ->like('users.name', $search)
                ->orLike('users.email', $search)
                ->orLike('users.username', $search)
                ->orLike('users.mobile', $search)
                ->groupEnd();
        }

        // Page size — whitelisted, with an "all" option. "All" is capped at a
        // safe maximum so a huge table can never exhaust memory / freeze the
        // browser (pagination still covers the rest beyond the cap).
        $per     = (string) $this->request->getGet('per');
        $per     = in_array($per, ['25', '35', '50', '100', '1000', 'all'], true) ? $per : '25';
        $perNum  = $per === 'all' ? 2000 : (int) $per;

        $rows = $builder->paginate($perNum);

        // Attach each customer's subscription (plan + payment status) in one query.
        $ids = array_column($rows, 'id');
        if ($ids !== []) {
            $subs = [];
            $subRows = \Config\Database::connect()->table('subscriptions s')
                ->select('s.customer_id, s.payment_status, s.status, p.code AS plan_code, p.name AS plan_name')
                ->join('subscription_plans p', 'p.id = s.plan_id', 'left')
                ->whereIn('s.customer_id', $ids)
                ->orderBy('s.id', 'DESC')
                ->get()->getResultArray();
            foreach ($subRows as $s) {
                $subs[$s['customer_id']] = $subs[$s['customer_id']] ?? $s; // keep latest
            }
            foreach ($rows as &$r) {
                $r['subscription'] = $subs[$r['id']] ?? null;
            }
            unset($r);
        }

        return [
            'rows'       => $rows,
            'pager'      => $this->users->pager,
            'search'     => $search,
            'sort'       => $sort,
            'dir'        => $dir,
            'per'        => $per,
            'scopeLabel' => $this->isSuper() ? 'All Users' : 'Relevant Users',
            'showRoleType' => $this->isSuper(),
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ];
    }

    public function index()
    {
        $data = $this->listData();
        $stats = $this->scopedStats();

        return $this->render('index', array_merge($data, [
            'title'      => 'Users',
            'breadcrumb' => [['label' => 'Users']],
            'stats'      => $stats,
            'charts'     => [
                'byRole' => $this->isSuper() ? $this->scopedUsersByRole() : ['labels' => [], 'data' => []],
                'byType' => $this->isSuper() ? $this->scopedUsersByType() : ['labels' => [], 'data' => []],
                'growth' => $this->scopedUserGrowth(6),
                'status' => ['labels' => ['Active', 'Inactive'], 'data' => [
                    (int) $stats['active_users'],
                    (int) $stats['inactive_users'],
                ]],
            ],
            'css'        => [base_url('assets/css/tm-table.css'), base_url('assets/css/users.css')],
            'js'         => [base_url('assets/vendor/chart/chart.umd.min.js'), base_url('assets/js/users.js')],
        ]));
    }

    /**
     * AJAX fragment: just the users table/grid card for sort + pagination
     * without a full page reload. Falls back to the full page for non-AJAX.
     */
    public function list()
    {
        if (! $this->request->isAJAX()) {
            return redirect()->to(site_url('users') . '?' . (string) ($_SERVER['QUERY_STRING'] ?? ''));
        }
        return view('Modules\Users\Views\_list', $this->listData());
    }

    // ---------------------------------------------------------------
    public function create()
    {
        if ($redirect = $this->requireUserPermission('add')) {
            return $redirect;
        }

        return $this->render('form', $this->formData(null, 'create'));
    }

    public function edit($id = null)
    {
        if ($redirect = $this->requireUserPermission('edit')) {
            return $redirect;
        }

        $row = $this->users->find((int) $id);
        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        if (! $this->canManage((int) $id)) {
            return redirect()->to(site_url('users'))->with('error', 'You do not have access to that user.');
        }
        return $this->render('form', $this->formData($row, 'edit'));
    }

    // ---------------------------------------------------------------
    public function store()
    {
        if ($redirect = $this->requireUserPermission('add')) {
            return $redirect;
        }

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => 'required|valid_email|is_unique[users.email]',
            'username' => 'required|alpha_dash|min_length[3]|is_unique[users.username]',
            'password' => 'required|min_length[8]',
            'mobile'   => 'permit_empty|max_length[20]',
            'profile_image' => 'permit_empty|is_image[profile_image]|max_size[profile_image,2048]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'         => $this->request->getPost('name'),
            'email'        => $this->request->getPost('email'),
            'mobile'       => $this->request->getPost('mobile'),
            'username'     => $this->request->getPost('username'),
            'password'     => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
            'user_type_id' => $this->request->getPost('user_type_id') ?: null,
            'status'       => (int) ($this->request->getPost('status') ?? 1),
            'profile_image'=> $this->handleUpload(),
            'must_change_password' => (int) (bool) $this->request->getPost('must_change_password'),
            'mobile_login_enabled' => (int) (bool) $this->request->getPost('mobile_login_enabled'),
            'web_push_enabled'     => (int) (bool) $this->request->getPost('web_push_enabled'),
            'account_type' => 'super_admin',
            // Control hierarchy: the creator owns the new account.
            'parent_id'    => user_id(),
        ];
        if ($this->isSuper()) {
            $data['user_type_id'] = $this->sanitizeTypeId($data['user_type_id']);
        } else {
            unset($data['user_type_id']);
        }

        $this->users->skipValidation(true)->insert($data);
        $id = $this->users->getInsertID();
        if ($this->isSuper()) {
            $this->users->syncRoles((int) $id, $this->sanitizeRoleIds((array) $this->request->getPost('roles')));
        }
        $this->syncPermissions((int) $id);

        activity_log('Users', 'Add', "User #{$id} ({$data['username']}) created");
        return redirect()->to(site_url('users'))->with('success', 'User created successfully.');
    }

    // ---------------------------------------------------------------
    public function update($id = null)
    {
        if ($redirect = $this->requireUserPermission('edit')) {
            return $redirect;
        }

        $id  = (int) $id;
        $row = $this->users->find($id);
        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        if (! $this->canManage($id)) {
            return redirect()->to(site_url('users'))->with('error', 'You do not have access to that user.');
        }

        $rules = [
            'name'     => 'required|min_length[2]|max_length[150]',
            'email'    => "required|valid_email|is_unique[users.email,id,{$id}]",
            'username' => "required|alpha_dash|min_length[3]|is_unique[users.username,id,{$id}]",
            'password' => 'permit_empty|min_length[8]',
            'mobile'   => 'permit_empty|max_length[20]',
            'profile_image' => 'permit_empty|is_image[profile_image]|max_size[profile_image,2048]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data = [
            'name'         => $this->request->getPost('name'),
            'email'        => $this->request->getPost('email'),
            'mobile'       => $this->request->getPost('mobile'),
            'username'     => $this->request->getPost('username'),
            'status'       => (int) ($this->request->getPost('status') ?? 1),
            'must_change_password' => (int) (bool) $this->request->getPost('must_change_password'),
            'mobile_login_enabled' => (int) (bool) $this->request->getPost('mobile_login_enabled'),
            'web_push_enabled'     => (int) (bool) $this->request->getPost('web_push_enabled'),
        ];
        if ($this->isSuper()) {
            $data['user_type_id'] = $this->sanitizeTypeId($this->request->getPost('user_type_id') ?: null);
        }

        if ($pwd = (string) $this->request->getPost('password')) {
            $data['password'] = password_hash($pwd, PASSWORD_DEFAULT);
        }
        if ($img = $this->handleUpload()) {
            $data['profile_image'] = $img;
        }

        $this->users->skipValidation(true)->update($id, $data);
        if ($this->isSuper()) {
            $this->users->syncRoles($id, $this->sanitizeRoleIds((array) $this->request->getPost('roles')));
        }
        $this->syncPermissions($id);

        activity_log('Users', 'Edit', "User #{$id} updated");
        return redirect()->to(site_url('users'))->with('success', 'User updated successfully.');
    }

    // ---------------------------------------------------------------
    public function delete($id = null)
    {
        if ($redirect = $this->requireUserPermission('delete')) {
            return $redirect;
        }

        $id = (int) $id;
        if (! $this->users->find($id)) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        if ($id === user_id()) {
            return redirect()->to(site_url('users'))->with('error', 'You cannot delete your own account.');
        }
        if (! $this->canManage($id)) {
            return redirect()->to(site_url('users'))->with('error', 'You do not have access to that user.');
        }
        $this->users->delete($id);
        activity_log('Users', 'Delete', "User #{$id} deleted");
        return redirect()->to(site_url('users'))->with('success', 'User deleted successfully.');
    }

    public function toggleStatus($id = null)
    {
        if ($redirect = $this->requireUserPermission('edit')) {
            return $redirect;
        }

        $id  = (int) $id;
        $row = $this->users->find($id);
        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        if (! $this->canManage($id)) {
            return redirect()->to(site_url('users'))->with('error', 'You do not have access to that user.');
        }
        $new = (int) $row['status'] === 1 ? 0 : 1;
        $this->users->update($id, ['status' => $new]);
        activity_log('Users', 'Edit', "User #{$id} status changed");
        return redirect()->back()->with('success', 'Status updated.');
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------
    private function formData(?array $row, string $mode): array
    {
        $isSuper = $this->isSuper();

        $types = ['' => '— Select —'];
        foreach ((new UserTypeModel())->where('status', 1)->findAll() as $t) {
            // Only a super admin may assign the Super Admin type.
            if (! $isSuper && ($t['code'] ?? '') === 'super_admin') {
                continue;
            }
            $types[$t['id']] = $t['name'];
        }

        $roles = $isSuper ? (new RoleModel())->where('status', 1)->orderBy('name')->findAll() : [];

        // Modules ordered parent-then-child for a readable permission matrix.
        $modules = (new ModuleModel())
            ->orderBy('COALESCE(parent_id, id)', 'ASC', false)
            ->orderBy('parent_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();
        if (! $isSuper) {
            $modules = array_values(array_filter(
                $modules,
                fn ($module) => ! in_array($module['code'] ?? '', $this->superOnlyModuleCodes, true)
            ));
        }

        return [
            'title'         => $mode === 'edit' ? 'Edit User' : 'Add User',
            'breadcrumb'    => [
                ['label' => 'Users', 'url' => site_url('users')],
                ['label' => $mode === 'edit' ? 'Edit' : 'Add'],
            ],
            'row'           => $row,
            'mode'          => $mode,
            'errors'        => session()->getFlashdata('errors') ?? [],
            'typeOptions'   => $types,
            'roles'         => $roles,
            'assignedRoles' => $row ? $this->users->roleIds((int) $row['id']) : [],
            'modules'       => $modules,
            'permissions'   => (new PermissionModel())->ordered(),
            'grantedPerms'  => $row ? (new UserPermissionModel())->matrixForUser((int) $row['id']) : [],
            'showRoleType'  => $isSuper,
            'moduleCode'    => $this->moduleCode,
            'baseRoute'     => $this->baseRoute,
        ];
    }

    /**
     * Drop any role ids the current user is not allowed to assign (defense in
     * depth behind the already-filtered form dropdown).
     *
     * @param array<int|string> $roleIds
     * @return list<int>
     */
    private function sanitizeRoleIds(array $roleIds): array
    {
        $ids     = array_map('intval', $roleIds);
        $allowed = $this->assignableRoleIds();
        if ($allowed === null) {
            return $ids;
        }
        return array_values(array_intersect($ids, $allowed));
    }

    /**
     * Prevent a non-super admin from assigning the Super Admin user type.
     */
    private function sanitizeTypeId($typeId): ?int
    {
        $typeId = $typeId ? (int) $typeId : null;
        if ($typeId === null || $this->isSuper()) {
            return $typeId;
        }
        $type = (new UserTypeModel())->find($typeId);
        return ($type && ($type['code'] ?? '') === 'super_admin') ? null : $typeId;
    }

    /**
     * Persist the per-user module/action grants posted as perm[module_id][] = permission_id.
     */
    private function syncPermissions(int $userId): void
    {
        $posted = (array) $this->request->getPost('perm');
        $map    = [];
        $blockedModuleIds = [];
        if (! $this->isSuper()) {
            $blockedModuleIds = array_map(
                static fn ($module) => (int) $module['id'],
                (new ModuleModel())->whereIn('code', $this->superOnlyModuleCodes)->findAll()
            );
        }
        foreach ($posted as $moduleId => $permIds) {
            if (in_array((int) $moduleId, $blockedModuleIds, true)) {
                continue;
            }
            $ids = array_map('intval', (array) $permIds);
            $map[(int) $moduleId] = $ids;
        }
        (new UserPermissionModel())->syncUser($userId, $map);
    }

    /** Upload the profile image if present; returns stored filename or null. */
    private function handleUpload(): ?string
    {
        $file = $this->request->getFile('profile_image');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return null;
        }
        $dir = FCPATH . 'uploads/users';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $newName = $file->getRandomName();
        $file->move($dir, $newName);
        return $newName;
    }
}
