<?php

namespace Modules\UserTypes\Controllers;

use App\Controllers\BaseCrudController;
use App\Models\UserTypeModel;

class UserTypeController extends BaseCrudController
{
    protected string $moduleCode    = 'user_types';
    protected string $baseRoute     = 'user-types';
    protected string $viewNamespace = 'Modules\UserTypes\Views\\';
    protected string $titlePlural   = 'User Types';
    protected string $titleSingular = 'User Type';
    protected array  $searchable    = ['name', 'code'];

    public function __construct()
    {
        $this->model = new UserTypeModel();
    }

    protected function prepareData(array $post, ?array $existing): array
    {
        return [
            'name'        => $post['name'] ?? '',
            'code'        => $post['code'] ?? '',
            'description' => $post['description'] ?? null,
            'status'      => isset($post['status']) ? (int) $post['status'] : 1,
        ];
    }
}
