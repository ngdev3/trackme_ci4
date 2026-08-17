<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Subscription coupon codes (discount + redeem). See CreateCoupons migration and
 * App\Libraries\Coupon for the validation / redemption rules.
 */
class CouponModel extends Model
{
    protected $table         = 'coupons';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps  = true;
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'code', 'description', 'kind', 'discount_type', 'discount_value', 'max_discount',
        'free_days', 'plan_id', 'min_amount', 'max_redemptions', 'per_user_limit',
        'redeemed_count', 'starts_at', 'expires_at', 'status', 'created_by',
    ];

    /** Normalise a user-typed code (trim + uppercase) for stable matching. */
    public static function normalize(string $code): string
    {
        return strtoupper(trim($code));
    }

    /** Find an active, non-deleted coupon by its (normalised) code, or null. */
    public function findByCode(string $code): ?array
    {
        $code = self::normalize($code);
        if ($code === '') {
            return null;
        }
        return $this->where('code', $code)->first();
    }

    /** Increment the global redemption counter (best-effort, atomic-ish). */
    public function bumpRedeemed(int $couponId): void
    {
        $this->db->table($this->table)
            ->where('id', $couponId)
            ->set('redeemed_count', 'redeemed_count + 1', false)
            ->update();
    }

    /** All coupons for the admin list, newest first (with the granted plan name). */
    public function listAll(): array
    {
        return $this->select('coupons.*, subscription_plans.name AS plan_name')
            ->join('subscription_plans', 'subscription_plans.id = coupons.plan_id', 'left')
            ->orderBy('coupons.id', 'DESC')
            ->findAll();
    }
}
