<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * One row per successful coupon use — enforces per-user / global caps and gives
 * the Super Admin an audit trail of who redeemed what.
 */
class CouponRedemptionModel extends Model
{
    protected $table         = 'coupon_redemptions';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;
    protected $updatedField   = '';         // insert-only ledger

    protected $allowedFields = [
        'coupon_id', 'customer_id', 'kind', 'order_id', 'plan_id',
        'amount_discounted', 'days_granted',
    ];

    /** How many times a given customer has already redeemed a coupon. */
    public function countForCustomer(int $couponId, int $customerId): int
    {
        return $this->where('coupon_id', $couponId)
            ->where('customer_id', $customerId)
            ->countAllResults();
    }
}
