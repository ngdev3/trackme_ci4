<?php

namespace App\Models;

use CodeIgniter\Model;

class NoteCategoryModel extends Model
{
    protected $table         = 'note_categories';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = ['user_id', 'company_id', 'name', 'color'];

    protected $validationRules = [
        'name' => 'required|min_length[1]|max_length[60]',
    ];

    /** Categories belonging to a company (shared across its members). */
    public function forCompany(?int $companyId): array
    {
        return $this->where('company_id', $companyId)->orderBy('name', 'ASC')->findAll();
    }
}
