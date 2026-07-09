<?php

namespace App\Models;

use CodeIgniter\Model;

class CalculatorHistoryModel extends Model
{
    protected $table          = 'calculator_history';
    protected $primaryKey     = 'id';
    protected $returnType      = 'array';
    protected $useSoftDeletes  = true;
    protected $useTimestamps   = true;

    protected $allowedFields = ['user_id', 'title', 'expression', 'result'];

    protected $validationRules = [
        'expression' => 'required|max_length[255]',
        'result'     => 'required|max_length[100]',
        'title'      => 'permit_empty|max_length[150]',
    ];

    /**
     * A user's saved calculations, newest first.
     */
    public function forUser(int $userId, int $limit = 100): array
    {
        return $this->where('user_id', $userId)
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }
}
