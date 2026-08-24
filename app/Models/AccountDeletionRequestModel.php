<?php

namespace App\Models;

use CodeIgniter\Model;

class AccountDeletionRequestModel extends Model
{
    protected $table         = 'account_deletion_requests';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'user_id', 'name', 'email', 'mobile', 'reason', 'source', 'status',
        'admin_note', 'ip_address', 'user_agent', 'processed_by', 'processed_at',
    ];

    /** Does this user already have an open (pending) request? */
    public function hasPending(int $userId): bool
    {
        return $this->where('user_id', $userId)->where('status', 'pending')->countAllResults() > 0;
    }
}
