<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityLogModel extends Model
{
    protected $table         = 'activity_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['user_id', 'module', 'action', 'description', 'ip_address', 'user_agent', 'created_at'];

    public function withUser()
    {
        return $this->select('activity_logs.*, users.name AS user_name')
            ->join('users', 'users.id = activity_logs.user_id', 'left');
    }
}
