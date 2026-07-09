<?php

namespace Modules\Permissions\Controllers;

use App\Controllers\BaseController;
use App\Models\ModuleModel;
use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;

class PermissionController extends BaseController
{
    protected string $vns = 'Modules\Permissions\Views\\';

    public function index()
    {
        return $this->render('index', [
            'title'      => 'Permissions',
            'breadcrumb' => [['label' => 'Permissions']],
            'roles'      => (new RoleModel())->orderBy('name')->findAll(),
        ]);
    }

    public function matrix($roleId = null)
    {
        $roleId = (int) $roleId;
        $role   = (new RoleModel())->find($roleId);
        if (! $role) {
            return redirect()->to(site_url('permissions'))->with('error', 'Role not found.');
        }

        // Order modules parent-then-child for readable grouping.
        $modules = (new ModuleModel())
            ->orderBy('COALESCE(parent_id, id)', 'ASC', false)
            ->orderBy('parent_id', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->findAll();

        return $this->render('matrix', [
            'title'       => 'Permissions — ' . $role['name'],
            'breadcrumb'  => [
                ['label' => 'Permissions', 'url' => site_url('permissions')],
                ['label' => $role['name']],
            ],
            'role'        => $role,
            'modules'     => $modules,
            'permissions' => (new PermissionModel())->ordered(),
            'granted'     => (new RolePermissionModel())->matrixForRole($roleId),
        ]);
    }

    public function save($roleId = null)
    {
        $roleId = (int) $roleId;
        $role   = (new RoleModel())->find($roleId);
        if (! $role) {
            return redirect()->to(site_url('permissions'))->with('error', 'Role not found.');
        }

        // perm[module_id][] = permission_id
        $posted = (array) $this->request->getPost('perm');
        $map    = [];

        foreach ($posted as $moduleId => $permIds) {
            $map[(int) $moduleId] = array_map('intval', (array) $permIds);
        }

        (new RolePermissionModel())->syncRole($roleId, $map);
        activity_log('Permissions', 'Permission change', "Updated permissions for role '{$role['name']}' (#{$roleId})");

        return redirect()->to(site_url('permissions/matrix/' . $roleId))
            ->with('success', 'Permissions updated for ' . $role['name'] . '.');
    }
}
