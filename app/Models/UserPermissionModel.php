<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Direct per-user module/action grants. Structurally a twin of
 * RolePermissionModel, keyed by user_id instead of role_id. These grants are
 * merged with (added to) the user's role grants when authorising.
 */
class UserPermissionModel extends Model
{
    protected $table         = 'user_permissions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['user_id', 'module_id', 'permission_id'];

    /**
     * All grants for a user as [module_id][permission_code] => true.
     */
    public function matrixForUser(int $userId): array
    {
        $rows = $this->select('user_permissions.module_id, permissions.code AS perm_code')
            ->join('permissions', 'permissions.id = user_permissions.permission_id')
            ->where('user_permissions.user_id', $userId)
            ->get()->getResultArray();

        $matrix = [];
        foreach ($rows as $r) {
            $matrix[(int) $r['module_id']][$r['perm_code']] = true;
        }
        return $matrix;
    }

    /**
     * Replace all direct grants for a user.
     *
     * @param array<int, list<int>> $moduleToPermIds  module_id => [permission_id, ...]
     */
    public function syncUser(int $userId, array $moduleToPermIds): void
    {
        $this->where('user_id', $userId)->delete();
        $rows = [];
        foreach ($moduleToPermIds as $moduleId => $permIds) {
            foreach ($permIds as $pid) {
                $rows[] = ['user_id' => $userId, 'module_id' => (int) $moduleId, 'permission_id' => (int) $pid];
            }
        }
        if ($rows !== []) {
            $this->insertBatch($rows);
        }
    }

    /**
     * Permission codes granted directly to a user on a given module code.
     *
     * @return list<string>
     */
    public function actionsFor(int $userId, string $moduleCode): array
    {
        $rows = $this->select('DISTINCT permissions.code AS code', false)
            ->join('permissions', 'permissions.id = user_permissions.permission_id')
            ->join('modules', 'modules.id = user_permissions.module_id')
            ->where('user_permissions.user_id', $userId)
            ->where('modules.code', $moduleCode)
            ->get()->getResultArray();

        return array_map(static fn ($r) => $r['code'], $rows);
    }

    /**
     * Module codes (that have a URL) a user can at least "view" directly.
     *
     * @return list<string>
     */
    public function viewableModuleCodes(int $userId): array
    {
        $rows = $this->select('DISTINCT modules.code AS code', false)
            ->join('permissions', 'permissions.id = user_permissions.permission_id')
            ->join('modules', 'modules.id = user_permissions.module_id')
            ->where('user_permissions.user_id', $userId)
            ->where('permissions.code', 'view')
            ->get()->getResultArray();

        return array_map(static fn ($r) => $r['code'], $rows);
    }
}
