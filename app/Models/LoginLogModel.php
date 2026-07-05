<?php

namespace App\Models;

use CodeIgniter\Model;

class LoginLogModel extends Model
{
    protected $table         = 'login_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'user_id', 'username', 'ip_address', 'user_agent', 'browser',
        'operating_system', 'device_type', 'status', 'failure_reason',
        'is_suspicious', 'suspicious_reason', 'message', 'login_at',
        'logout_at', 'last_activity_at', 'session_duration', 'created_at',
    ];

    public function recent(int $limit = 10): array
    {
        return $this->select('login_logs.*, users.name AS user_name')
            ->join('users', 'users.id = login_logs.user_id', 'left')
            ->orderBy('login_logs.id', 'DESC')
            ->findAll($limit);
    }

    public function markActivity(?int $logId): void
    {
        if (! $logId) {
            return;
        }

        $this->update($logId, ['last_activity_at' => date('Y-m-d H:i:s')]);
    }

    public function closeSession(?int $logId): void
    {
        if (! $logId) {
            return;
        }

        $row = $this->find($logId);
        if (! $row || ! empty($row['logout_at'])) {
            return;
        }

        $now = date('Y-m-d H:i:s');
        $loginAt = $row['login_at'] ?: $row['created_at'] ?: $now;
        $duration = max(0, strtotime($now) - strtotime($loginAt));

        $this->update($logId, [
            'logout_at'        => $now,
            'last_activity_at' => $now,
            'session_duration' => $duration,
        ]);
    }
}
