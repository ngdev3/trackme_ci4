<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table         = 'role_permissions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['role_id', 'module_id', 'permission_id'];

    /**
     * All grants for a role as [module_id][permission_code] => true.
     */
    public function matrixForRole(int $roleId): array
    {
        $rows = $this->select('role_permissions.module_id, permissions.code AS perm_code')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->where('role_permissions.role_id', $roleId)
            ->get()->getResultArray();

        $matrix = [];
        foreach ($rows as $r) {
            $matrix[(int) $r['module_id']][$r['perm_code']] = true;
        }
        return $matrix;
    }

    /**
     * Replace all grants for a role.
     *
     * @param array<int, list<int>> $moduleToPermIds  module_id => [permission_id, ...]
     */
    public function syncRole(int $roleId, array $moduleToPermIds): void
    {
        $this->where('role_id', $roleId)->delete();
        $rows = [];
        foreach ($moduleToPermIds as $moduleId => $permIds) {
            foreach ($permIds as $pid) {
                $rows[] = ['role_id' => $roleId, 'module_id' => (int) $moduleId, 'permission_id' => (int) $pid];
            }
        }
        if ($rows !== []) {
            $this->insertBatch($rows);
        }
    }

    /**
     * Permission codes a set of roles hold on a given module code.
     *
     * @param list<int> $roleIds
     * @return list<string>
     */
    public function actionsFor(array $roleIds, string $moduleCode): array
    {
        if ($roleIds === []) {
            return [];
        }
        $rows = $this->select('DISTINCT permissions.code AS code', false)
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->join('modules', 'modules.id = role_permissions.module_id')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->where('modules.code', $moduleCode)
            ->get()->getResultArray();

        return array_map(static fn ($r) => $r['code'], $rows);
    }

    /**
     * Module codes (that have a URL) any of the given roles can at least "view".
     *
     * @param list<int> $roleIds
     * @return list<string>
     */
    public function viewableModuleCodes(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }
        $rows = $this->select('DISTINCT modules.code AS code', false)
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->join('modules', 'modules.id = role_permissions.module_id')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->where('permissions.code', 'view')
            ->get()->getResultArray();

        return array_map(static fn ($r) => $r['code'], $rows);
    }
}
