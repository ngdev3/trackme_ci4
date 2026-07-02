<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginLogModel extends Model
{
    protected $table         = 'login_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = ['user_id', 'username', 'ip_address', 'user_agent', 'status', 'message', 'created_at'];

    public function recent(int $limit = 10): array
    {
        return $this->select('login_logs.*, users.name AS user_name')
            ->join('users', 'users.id = login_logs.user_id', 'left')
            ->orderBy('login_logs.id', 'DESC')
            ->findAll($limit);
    }
}
