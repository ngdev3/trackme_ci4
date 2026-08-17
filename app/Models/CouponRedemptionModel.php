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

    /**
     * The redemption trail for the Super Admin: who used which coupon, when, and
     * what they got — newest first. Optionally filter by a single coupon or a
     * free-text search over customer / code / order.
     *
     * @return array<int,array<string,mixed>>
     */
    public function recent(?int $couponId = null, string $q = '', int $limit = 300): array
    {
        $b = $this->select('coupon_redemptions.*, coupons.code AS coupon_code, coupons.kind AS coupon_kind,'
                . ' users.name AS customer_name, users.email AS customer_email, subscription_plans.name AS plan_name')
            ->join('coupons', 'coupons.id = coupon_redemptions.coupon_id', 'left')
            ->join('users', 'users.id = coupon_redemptions.customer_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = coupon_redemptions.plan_id', 'left')
            ->orderBy('coupon_redemptions.id', 'DESC');

        if ($couponId) {
            $b->where('coupon_redemptions.coupon_id', $couponId);
        }
        if ($q !== '') {
            $b->groupStart()
                ->like('coupons.code', $q)
                ->orLike('users.name', $q)
                ->orLike('users.email', $q)
                ->orLike('coupon_redemptions.order_id', $q)
                ->groupEnd();
        }
        return $b->findAll($limit);
    }

    /** Headline totals for the trail page. */
    public function summary(): array
    {
        $row = $this->select("COUNT(*) AS total,
                SUM(CASE WHEN kind='discount' THEN 1 ELSE 0 END) AS discounts,
                SUM(CASE WHEN kind='redeem' THEN 1 ELSE 0 END) AS redeems,
                COALESCE(SUM(amount_discounted),0) AS total_discount,
                COALESCE(SUM(days_granted),0) AS total_days")
            ->first();
        return $row ?: ['total' => 0, 'discounts' => 0, 'redeems' => 0, 'total_discount' => 0, 'total_days' => 0];
    }
}
