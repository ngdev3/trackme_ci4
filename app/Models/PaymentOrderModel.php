<?php

namespace App\Models;

use CodeIgniter\Model;

class PaymentOrderModel extends Model
{
    protected $table         = 'payment_orders';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'order_id', 'cf_order_id', 'customer_id', 'plan_id', 'amount', 'currency',
        'status', 'payment_session_id', 'cf_payment_id', 'activated', 'refunded',
        'invoice_no', 'invoice_date',
    ];

    public function findByOrderId(string $orderId): ?array
    {
        return $this->where('order_id', $orderId)->first();
    }

    /** A customer's payment history, newest first. */
    public function forCustomer(int $customerId): array
    {
        return $this->select('payment_orders.*, subscription_plans.name AS plan_name')
            ->join('subscription_plans', 'subscription_plans.id = payment_orders.plan_id', 'left')
            ->where('customer_id', $customerId)
            ->orderBy('payment_orders.id', 'DESC')
            ->findAll();
    }
}
