<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\RolePermissionModel;

/**
 * Role_permissions — CI4 port of admin/Role_permissions (Super-Admin only).
 * Edits the RBAC role labels and the role×module permission matrix
 * (erp_user_type_roles + erp_role_module_permissions) that the backend gate and
 * the per-user screen both read. Gated: super admin only (beyond the rbac filter).
 */
class Role_permissions extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];

    public function index()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return redirect()->to(base_url('permission_denied'));
        }

        $model = new RolePermissionModel();
        $model->ensureTables();

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $model->saveRoles($this->request->getPost('roles'));
            $model->savePermissions($this->request->getPost('permissions'));
            return redirect()->to(base_url('admin/role_permissions'))->with('success', 'Roles and module permissions updated successfully.');
        }

        return _layout('\App\Modules\Admin\Views\role_permissions\index', [
            'title'       => 'Role Permissions · C R Industries ERP',
            'roles'       => $model->roles(),
            'modules'     => $model->modules(),
            'permissions' => $model->permissionsMatrix(),
        ]);
    }
}
