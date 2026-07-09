<?php

namespace App\Libraries;

use App\Models\ModuleModel;
use App\Models\RolePermissionModel;
use App\Models\UserPermissionModel;
use Config\Services;

/**
 * Access-control lookups driven entirely by the database.
 *
 * A Super Admin role bypasses every check. All other users are resolved
 * against the role_permissions matrix (via their roles) PLUS any grants
 * assigned directly to the user in user_permissions — the two are merged
 * (union). Results are memoised per request.
 */
class Acl
{
    protected RolePermissionModel $rolePerms;
    protected UserPermissionModel $userPerms;
    protected ModuleModel $modules;
    protected $session;

    /** @var array<string, list<string>> module_code => granted action codes */
    protected array $actionCache = [];
    protected ?array $viewableCache = null;

    public function __construct()
    {
        $this->rolePerms = new RolePermissionModel();
        $this->userPerms = new UserPermissionModel();
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

    protected function userId(): ?int
    {
        $id = $this->session->get('user_id');
        return $id ? (int) $id : null;
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
            $roleActions = $this->rolePerms->actionsFor($this->roleIds(), $moduleCode);
            $userActions = ($uid = $this->userId()) ? $this->userPerms->actionsFor($uid, $moduleCode) : [];
            $this->actionCache[$moduleCode] = array_values(array_unique(array_merge($roleActions, $userActions)));
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
            $roleCodes = $this->rolePerms->viewableModuleCodes($this->roleIds());
            $userCodes = ($uid = $this->userId()) ? $this->userPerms->viewableModuleCodes($uid) : [];
            $this->viewableCache = array_values(array_unique(array_merge($roleCodes, $userCodes)));
        }
        return $this->viewableCache;
    }

    public function canView(string $moduleCode): bool
    {
        return $this->can($moduleCode, 'view');
    }
}
