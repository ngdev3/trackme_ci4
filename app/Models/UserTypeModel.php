<?php

namespace App\Models;

use CodeIgniter\Model;

class UserTypeModel extends Model
{
    protected $table          = 'user_types';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = ['name', 'code', 'description', 'status'];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'code' => 'required|alpha_dash|max_length[50]|is_unique[user_types.code,id,{id}]',
    ];
}
