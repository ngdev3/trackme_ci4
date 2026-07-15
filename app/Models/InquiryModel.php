<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryModel extends Model
{
    protected $table         = 'inquiries';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'name', 'email', 'phone', 'company', 'subject', 'message',
        'status', 'ip_address', 'user_agent',
    ];
}
