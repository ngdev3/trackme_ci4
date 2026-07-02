<?php

namespace App\Models;

use CodeIgniter\Model;

class ModuleModel extends Model
{
    protected $table          = 'modules';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = ['name', 'code', 'url', 'icon', 'parent_id', 'sort_order', 'is_menu', 'status'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'code' => 'required|alpha_dash|max_length[50]|is_unique[modules.code,id,{id}]',
    ];

    /**
     * All active modules ordered for menu building.
     */
    public function activeOrdered(): array
    {
        return $this->where('status', 1)->orderBy('sort_order', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Parent modules for the parent-select dropdown (top-level only).
     */
    public function parents(?int $excludeId = null): array
    {
        $builder = $this->where('parent_id', null);
        if ($excludeId !== null) {
            $builder->where('id !=', $excludeId);
        }
        return $builder->orderBy('sort_order', 'ASC')->findAll();
    }

    public function byCode(string $code): ?array
    {
        return $this->where('code', $code)->first();
    }
}
