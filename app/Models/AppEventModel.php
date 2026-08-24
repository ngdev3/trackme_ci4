<?php

namespace App\Models;

use CodeIgniter\Model;

class AppEventModel extends Model
{
    protected $table         = 'app_events';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = false; // created_at supplied explicitly (batched sends)
    protected $allowedFields = ['user_id', 'event', 'label', 'route', 'platform', 'ip_address', 'user_agent', 'created_at'];
}
