<?php

namespace App\Models;

use CodeIgniter\Model;

class SubscriptionModel extends Model
{
    protected $table         = 'subscriptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = ['customer_id', 'plan_id', 'status', 'payment_status', 'payment_id', 'started_at', 'expires_at', 'expiry_notified_at'];

    /** The customer's current subscription (with plan name), or null. */
    public function forCustomer(int $customerId): ?array
    {
        return $this->select('subscriptions.*, subscription_plans.name AS plan_name, subscription_plans.code AS plan_code')
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.customer_id', $customerId)
            ->orderBy('subscriptions.id', 'DESC')
            ->first();
    }

    /** Ensure a customer has a subscription; start a free trial if not. */
    public function ensureFor(int $customerId, ?int $planId): void
    {
        if ($this->where('customer_id', $customerId)->countAllResults() > 0) {
            return;
        }
        helper('subscription');
        $days = function_exists('sub_trial_days') ? sub_trial_days() : 30;
        $this->insert([
            'customer_id'    => $customerId,
            'plan_id'        => $planId,
            'status'         => 'trial',
            'payment_status' => 'trial',
            'started_at'     => date('Y-m-d H:i:s'),
            'expires_at'     => date('Y-m-d H:i:s', strtotime('+' . $days . ' days')),
        ]);
    }

    /**
     * Activate a paid plan with an explicit expiry supplied by the payment
     * provider (Google Play Billing gives the authoritative renewal date, so we
     * mirror it rather than computing +cycle). Idempotent per customer.
     */
    public function activateWithExpiry(int $customerId, int $planId, string $expiresAt, ?string $paymentId = null): void
    {
        $row  = $this->where('customer_id', $customerId)->orderBy('id', 'DESC')->first();
        $data = [
            'plan_id'            => $planId,
            'status'             => 'active',
            'payment_status'     => 'paid',
            'expires_at'         => $expiresAt,
            'expiry_notified_at' => null,
        ];
        if ($paymentId !== null && $paymentId !== '') {
            $data['payment_id'] = $paymentId;
        }
        if ($row) {
            $this->update($row['id'], $data);
        } else {
            $this->insert($data + ['customer_id' => $customerId, 'started_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * Activate a paid plan for a customer, extending the paid window by the plan's
     * billing cycle (from now, or from an unexpired current expiry).
     */
    public function activatePaid(int $customerId, array $plan): void
    {
        $cycle  = $plan['billing_cycle'] ?? 'yearly';
        $months = $cycle === 'monthly' ? 1 : ($cycle === 'lifetime' ? 1200 : 12);

        $row  = $this->where('customer_id', $customerId)->orderBy('id', 'DESC')->first();
        $base = ($row && ! empty($row['expires_at']) && strtotime($row['expires_at']) > time())
            ? strtotime($row['expires_at']) : time();
        $expires = date('Y-m-d H:i:s', strtotime('+' . $months . ' months', $base));

        $data = [
            'plan_id'        => (int) $plan['id'],
            'status'         => 'active',
            'payment_status' => 'paid',
            'expires_at'     => $expires,
            // Clear the lapse marker so a future expiry can notify again.
            'expiry_notified_at' => null,
        ];
        if ($row) {
            $this->update($row['id'], $data);
        } else {
            $this->insert($data + ['customer_id' => $customerId, 'started_at' => date('Y-m-d H:i:s')]);
        }
    }

    /**
     * Cancel a customer's subscription. Marks it cancelled + unpaid so access
     * drops to the Basic floor immediately (see subscription_helper). Existing
     * data is never touched — only access is withdrawn.
     */
    public function cancel(int $customerId): bool
    {
        $row = $this->where('customer_id', $customerId)->orderBy('id', 'DESC')->first();
        if (! $row) {
            return false;
        }
        return (bool) $this->update($row['id'], [
            'status'         => 'cancelled',
            'payment_status' => 'unpaid',
        ]);
    }
}
