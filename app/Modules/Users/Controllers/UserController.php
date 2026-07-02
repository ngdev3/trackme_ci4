<?php

namespace Modules\Users\Controllers;

use App\Controllers\BaseController;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\UserTypeModel;

class UserController extends BaseController
{
    protected UserModel $users;
    protected string $moduleCode = 'users';
    protected string $baseRoute  = 'users';
    protected string $vns        = 'Modules\Users\Views\\';

    public function __construct()
    {
        $this->users = new UserModel();
    }

    // ---------------------------------------------------------------
    public function index()
    {
        $search  = trim((string) $this->request->getGet('q'));
        $builder = $this->users->withRelations()->orderBy('users.id', 'DESC');

        if ($search !== '') {
            $builder->groupStart()
                ->like('users.name', $search)
                ->orLike('users.email', $search)
                ->orLike('users.username', $search)
                ->orLike('users.mobile', $search)
                ->groupEnd();
        }

        return $this->render('index', [
            'title'      => 'Users',
            'breadcrumb' => [['label' => 'Users']],
            'rows'       => $builder->paginate(10),
            'pager'      => $this->users->pager,
            'search'     => $search,
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
        ]);
    }

    // ---------------------------------------------------------------
    public function create()
    {
        return $this->render('form', $this->formData(null, 'create'));
    }

    public function edit($id = null)
    {
        $row = $this->users->find((int) $id);
        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        return $this->render('form', $this->formData($row, 'edit'));
    }

    // ---------------------------------------------------------------
    public function store()
    {
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
        ];

        $this->users->allowValidation(false)->insert($data);
        $id = $this->users->getInsertID();
        $this->users->syncRoles((int) $id, (array) $this->request->getPost('roles'));

        activity_log('Users', 'Add', "User #{$id} ({$data['username']}) created");
        return redirect()->to(site_url('users'))->with('success', 'User created successfully.');
    }

    // ---------------------------------------------------------------
    public function update($id = null)
    {
        $id  = (int) $id;
        $row = $this->users->find($id);
        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
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
            'user_type_id' => $this->request->getPost('user_type_id') ?: null,
            'status'       => (int) ($this->request->getPost('status') ?? 1),
        ];

        if ($pwd = (string) $this->request->getPost('password')) {
            $data['password'] = password_hash($pwd, PASSWORD_DEFAULT);
        }
        if ($img = $this->handleUpload()) {
            $data['profile_image'] = $img;
        }

        $this->users->allowValidation(false)->update($id, $data);
        $this->users->syncRoles($id, (array) $this->request->getPost('roles'));

        activity_log('Users', 'Edit', "User #{$id} updated");
        return redirect()->to(site_url('users'))->with('success', 'User updated successfully.');
    }

    // ---------------------------------------------------------------
    public function delete($id = null)
    {
        $id = (int) $id;
        if (! $this->users->find($id)) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
        }
        if ($id === user_id()) {
            return redirect()->to(site_url('users'))->with('error', 'You cannot delete your own account.');
        }
        $this->users->delete($id);
        activity_log('Users', 'Delete', "User #{$id} deleted");
        return redirect()->to(site_url('users'))->with('success', 'User deleted successfully.');
    }

    public function toggleStatus($id = null)
    {
        $id  = (int) $id;
        $row = $this->users->find($id);
        if (! $row) {
            return redirect()->to(site_url('users'))->with('error', 'User not found.');
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
        $types = ['' => '— Select —'];
        foreach ((new UserTypeModel())->where('status', 1)->findAll() as $t) {
            $types[$t['id']] = $t['name'];
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
            'roles'         => (new RoleModel())->where('status', 1)->orderBy('name')->findAll(),
            'assignedRoles' => $row ? $this->users->roleIds((int) $row['id']) : [],
            'moduleCode'    => $this->moduleCode,
            'baseRoute'     => $this->baseRoute,
        ];
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
