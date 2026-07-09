<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionPlanModel extends Model
{
    protected $table         = 'subscription_plans';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['name', 'code', 'price', 'billing_cycle', 'max_firms', 'max_users', 'features', 'status'];

    public function active(): array
    {
        return $this->where('status', 1)->orderBy('price', 'ASC')->findAll();
    }
}
