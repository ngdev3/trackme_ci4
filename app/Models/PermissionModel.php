<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table         = 'permissions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'code', 'sort_order'];

    public function ordered(): array
    {
        return $this->orderBy('sort_order', 'ASC')->findAll();
    }
}
