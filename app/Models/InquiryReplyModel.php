<?php

namespace App\Models;

use CodeIgniter\Model;

class InquiryReplyModel extends Model
{
    protected $table         = 'inquiry_replies';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;
    protected $allowedFields = ['inquiry_id', 'sender_type', 'user_id', 'name', 'message'];

    /** All replies for an inquiry, oldest first (chat order). */
    public function thread(int $inquiryId): array
    {
        return $this->where('inquiry_id', $inquiryId)->orderBy('id', 'ASC')->findAll();
    }
}
