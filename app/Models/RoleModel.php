<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table          = 'roles';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = ['name', 'code', 'description', 'is_superadmin', 'status'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'code' => 'required|alpha_dash|max_length[50]|is_unique[roles.code,id,{id}]',
    ];

    public function isSuperAdmin(int $roleId): bool
    {
        $row = $this->find($roleId);
        return $row && (int) $row['is_superadmin'] === 1;
    }
}
