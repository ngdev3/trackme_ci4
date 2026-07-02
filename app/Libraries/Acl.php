<?php

namespace App\Libraries;

use App\Models\ModuleModel;
use App\Models\RolePermissionModel;
use Config\Services;

/**
 * Access-control lookups driven entirely by the database.
 *
 * A Super Admin role bypasses every check. All other roles are resolved
 * against the role_permissions matrix. Results are memoised per request.
 */
class Acl
{
    protected RolePermissionModel $rolePerms;
    protected ModuleModel $modules;
    protected $session;

    /** @var array<string, list<string>> module_code => granted action codes */
    protected array $actionCache = [];
    protected ?array $viewableCache = null;

    public function __construct()
    {
        $this->rolePerms = new RolePermissionModel();
        $this->modules   = new ModuleModel();
        $this->session   = Services::session();
    }

    public function isSuperAdmin(): bool
    {
        return (bool) $this->session->get('is_superadmin');
    }

    /**
     * @return list<int>
     */
    protected function roleIds(): array
    {
        return (array) ($this->session->get('role_ids') ?? []);
    }

    /**
     * Actions the current user may perform on a module.
     *
     * @return list<string>
     */
    public function actions(string $moduleCode): array
    {
        if ($this->isSuperAdmin()) {
            return ['view', 'add', 'edit', 'delete', 'print', 'export', 'approve'];
        }
        if (! array_key_exists($moduleCode, $this->actionCache)) {
            $this->actionCache[$moduleCode] = $this->rolePerms->actionsFor($this->roleIds(), $moduleCode);
        }
        return $this->actionCache[$moduleCode];
    }

    /**
     * Can the current user perform $action on $moduleCode?
     */
    public function can(string $moduleCode, string $action = 'view'): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }
        return in_array($action, $this->actions($moduleCode), true);
    }

    /**
     * Module codes (with a URL) the user can at least view.
     *
     * @return list<string>
     */
    public function viewableModuleCodes(): array
    {
        if ($this->isSuperAdmin()) {
            // Every module with a URL.
            $rows = $this->modules->where('status', 1)->where('url IS NOT NULL')->findColumn('code') ?? [];
            return $rows;
        }
        if ($this->viewableCache === null) {
            $this->viewableCache = $this->rolePerms->viewableModuleCodes($this->roleIds());
        }
        return $this->viewableCache;
    }

    public function canView(string $moduleCode): bool
    {
        return $this->can($moduleCode, 'view');
    }
}
