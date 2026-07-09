<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table         = 'subscriptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['customer_id', 'plan_id', 'status', 'payment_status', 'started_at', 'expires_at'];

    /** The customer's current subscription (with plan name), or null. */
    public function forCustomer(int $customerId): ?array
    {
        return $this->select('subscriptions.*, subscription_plans.name AS plan_name, subscription_plans.code AS plan_code')
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.customer_id', $customerId)
            ->orderBy('subscriptions.id', 'DESC')
            ->first();
    }

    /** Ensure a customer has a subscription; start a trial on the given plan if not. */
    public function ensureFor(int $customerId, ?int $planId): void
    {
        if ($this->where('customer_id', $customerId)->countAllResults() > 0) {
            return;
        }
        $this->insert([
            'customer_id'    => $customerId,
            'plan_id'        => $planId,
            'status'         => 'trial',
            'payment_status' => 'trial',
            'started_at'     => date('Y-m-d H:i:s'),
            'expires_at'     => date('Y-m-d H:i:s', strtotime('+14 days')),
        ]);
    }
}
