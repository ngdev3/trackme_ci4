<?php

namespace Modules\Roles\Controllers;

use App\Controllers\BaseCrudController;
use App\Models\RoleModel;

class RoleController extends BaseCrudController
{
    protected string $moduleCode    = 'roles';
    protected string $baseRoute     = 'roles';
    protected string $viewNamespace = 'Modules\Roles\Views\\';
    protected string $titlePlural   = 'Roles';
    protected string $titleSingular = 'Role';
    protected array  $searchable    = ['name', 'code'];

    public function __construct()
    {
        $this->model = new RoleModel();
    }

    protected function prepareData(array $post, ?array $existing): array
    {
        return [
            'name'          => $post['name'] ?? '',
            'code'          => $post['code'] ?? '',
            'description'   => $post['description'] ?? null,
            'is_superadmin' => isset($post['is_superadmin']) ? (int) $post['is_superadmin'] : 0,
            'status'        => isset($post['status']) ? (int) $post['status'] : 1,
        ];
    }
}
