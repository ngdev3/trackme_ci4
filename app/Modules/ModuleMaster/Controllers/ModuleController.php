<?php

namespace Modules\ModuleMaster\Controllers;

use App\Controllers\BaseCrudController;
use App\Models\ModuleModel;
use App\Models\PermissionModel;
use App\Models\RoleModel;
use App\Models\RolePermissionModel;

class ModuleController extends BaseCrudController
{
    protected string $moduleCode    = 'modules';
    protected string $baseRoute     = 'modules';
    protected string $viewNamespace = 'Modules\ModuleMaster\Views\\';
    protected string $titlePlural   = 'Modules';
    protected string $titleSingular = 'Module';
    protected array  $searchable    = ['name', 'code', 'url'];

    public function __construct()
    {
        $this->model = new ModuleModel();
    }

    protected function prepareData(array $post, ?array $existing): array
    {
        return [
            'name'       => $post['name'] ?? '',
            'code'       => $post['code'] ?? '',
            'url'        => $post['url'] !== '' ? ($post['url'] ?? null) : null,
            'icon'       => $post['icon'] ?? 'bi bi-circle',
            'parent_id'  => ! empty($post['parent_id']) ? (int) $post['parent_id'] : null,
            'sort_order' => (int) ($post['sort_order'] ?? 0),
            'is_menu'    => isset($post['is_menu']) ? (int) $post['is_menu'] : 1,
            'status'     => isset($post['status']) ? (int) $post['status'] : 1,
        ];
    }

    /** Provide the parent dropdown options to create/edit views. */
    protected function viewData(array $data): array
    {
        $model  = new ModuleModel();
        $exclude = isset($data['row']['id']) ? (int) $data['row']['id'] : null;
        $parents = ['' => '— None (top level) —'];
        foreach ($model->parents($exclude) as $p) {
            $parents[$p['id']] = $p['name'];
        }
        $data['parentOptions'] = $parents;
        return $data;
    }

    /**
     * When a new module is created, auto-enrol it into the permission section:
     * grant the Admin role full CRUD on it so it is immediately usable (Super
     * Admin bypasses checks; other roles are configured from the matrix). The
     * module already appears in every role's matrix automatically.
     */
    protected function afterSave(int $id, array $data, string $mode): void
    {
        if ($mode !== 'create') {
            return;
        }
        $adminRole = (new RoleModel())->where('code', 'admin')->first();
        if (! $adminRole) {
            return;
        }
        $rolePerms = new RolePermissionModel();
        $map       = [];
        foreach ((new PermissionModel())->whereIn('code', ['view', 'add', 'edit', 'delete'])->findAll() as $p) {
            $map[$id][] = (int) $p['id'];
        }
        // Merge onto the admin's existing grants rather than replacing them.
        $current = [];
        foreach ($rolePerms->where('role_id', $adminRole['id'])->findAll() as $rp) {
            $current[(int) $rp['module_id']][] = (int) $rp['permission_id'];
        }
        foreach ($map as $moduleId => $permIds) {
            $current[$moduleId] = array_values(array_unique(array_merge($current[$moduleId] ?? [], $permIds)));
        }
        $rolePerms->syncRole((int) $adminRole['id'], $current);
    }

    /** Show parent name in the listing. */
    protected function listQuery()
    {
        return $this->model
            ->select('modules.*, parent.name AS parent_name')
            ->join('modules AS parent', 'parent.id = modules.parent_id', 'left')
            ->orderBy('modules.sort_order', 'ASC')
            ->orderBy('modules.id', 'ASC');
    }
}
