<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\UserPermissionModel;
use App\Modules\Admin\Models\RolePermissionModel;

/**
 * User_permissions — CI4 port of admin/User_permissions (Super-Admin only).
 * Manage a single user: module access overrides, activate/deactivate, default
 * template, role, force password-change, mobile app access, and password. Super
 * admins are never modifiable here (they have full access).
 */
class User_permissions extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];

    public function index()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return redirect()->to(base_url('permission_denied'));
        }

        $model = new UserPermissionModel();
        $roleModel = new RolePermissionModel();
        $model->ensureTable();

        if (strtoupper($this->request->getMethod()) === 'POST') {
            return $this->handlePost($model);
        }

        $selected = (int) $this->request->getGet('user_id');
        $selectedUser = $selected ? $model->getUser($selected) : null;
        if ($selectedUser && $model->isSuperAdminUser($selectedUser)) {
            $selected = 0;
            $selectedUser = null;
        }

        $roleMap = [];
        foreach ($roleModel->roles() as $role) {
            $roleMap[(int) $role->user_type] = $role;
        }

        return _layout('\App\Modules\Admin\Views\user_permissions\index', [
            'title'             => 'User Permissions · C R Industries ERP',
            'users'             => $model->manageableUsers(),
            'modules'           => $model->modules(),
            'selected_user_id'  => $selected,
            'selected_user'     => $selectedUser,
            'user_permissions'  => $selected ? $model->permissionsForUser($selected) : [],
            'has_config'        => $selected ? $model->hasConfig($selected) : false,
            'templates'         => $model->templates(),
            'current_template'  => $selectedUser ? $model->userTemplate($selectedUser) : null,
            'role_map'          => $roleMap,
            'role_list'         => $roleModel->roles(),
        ]);
    }

    /** Dispatch the per-action POST forms (profile/status/template/…/modules). */
    private function handlePost(UserPermissionModel $model)
    {
        $req      = $this->request;
        $selected = (int) $req->getPost('user_id');
        $target   = $model->getUser($selected);

        if (! $target) {
            return redirect()->to(base_url('admin/user_permissions'))->with('error', 'Please select a valid user.');
        }
        if ($model->isSuperAdminUser($target)) {
            return redirect()->to(base_url('admin/user_permissions'))->with('error', 'Super Admin already has complete access and cannot be modified here.');
        }

        $action = $req->getPost('form_action');
        $back   = base_url('admin/user_permissions?user_id=' . $selected);

        if ($action === 'profile') {
            $first  = trim((string) $req->getPost('first_name'));
            $email  = trim((string) $req->getPost('email'));
            $mobile = trim((string) $req->getPost('mobile'));
            $ut     = (int) $req->getPost('user_type');
            $status = $req->getPost('status') === 'Inactive' ? 'Inactive' : 'Active';
            $firm   = (int) $req->getPost('default_firm');

            $errors = [];
            if ($first === '') { $errors[] = 'First name is required.'; }
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'A valid email is required.';
            } elseif ($model->emailExists($email, $selected)) {
                $errors[] = 'That email is already used by another user.';
            }
            if ($mobile === '') { $errors[] = 'Mobile is required.'; }
            if (! array_key_exists($ut, $model->validUserTypes())) { $errors[] = 'Please select a valid role.'; }
            if ($firm && ! $model->isValidTemplate($firm)) { $errors[] = 'Please select a valid default template.'; }

            if ($errors) {
                return redirect()->to($back)->with('error', implode(' ', $errors));
            }
            $update = [
                'first_name' => $first, 'last_name' => trim((string) $req->getPost('last_name')),
                'email' => $email, 'mobile' => $mobile, 'user_type' => $ut, 'status' => $status,
                'remark' => trim((string) $req->getPost('remark')),
            ];
            if ($firm) { $update['default_firm'] = $firm; }
            $model->updateProfile($selected, $update);
            return redirect()->to($back)->with('success', 'User details updated successfully.');
        }

        if ($action === 'status') {
            $s = $model->setStatus($selected, $req->getPost('status'));
            return redirect()->to($back)->with('success', 'User has been set to ' . $s . '.');
        }
        if ($action === 'template') {
            return $model->setTemplate($selected, $req->getPost('default_firm'))
                ? redirect()->to($back)->with('success', 'Default template updated successfully.')
                : redirect()->to($back)->with('error', 'Please choose a valid active template.');
        }
        if ($action === 'force_password') {
            $flag = (int) $req->getPost('force_flag');
            $model->setForcePasswordChange($selected, $flag);
            return redirect()->to($back)->with('success', $flag
                ? 'This user must now change their password at next login before they can access anything.'
                : 'Forced password-change requirement has been removed.');
        }
        if ($action === 'mobile_access') {
            $flag = (int) $req->getPost('app_access');
            $model->setAppAccess($selected, $flag);
            return redirect()->to($back)->with('success', $flag
                ? 'Mobile app access has been enabled for this user.'
                : 'Mobile app access has been blocked. This user can no longer log in to the mobile app.');
        }
        if ($action === 'password') {
            $pw = trim((string) $req->getPost('new_password'));
            if (strlen($pw) < 6) {
                return redirect()->to($back)->with('error', 'Password must be at least 6 characters.');
            }
            $model->setPassword($selected, $pw);
            return redirect()->to($back)->with('success', 'Password updated successfully.');
        }
        if ($req->getPost('reset')) {
            $model->resetUser($selected);
            return redirect()->to($back)->with('success', 'Permissions reset. This user now follows the role defaults.');
        }

        // default: save the module checkboxes
        $model->saveForUser($selected, $req->getPost('modules'));
        return redirect()->to($back)->with('success', 'Module permissions updated successfully.');
    }
}
