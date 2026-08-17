<?php

namespace App\Libraries;

use App\Models\CouponModel;
use App\Models\CouponRedemptionModel;
use App\Models\SubscriptionModel;
use App\Models\SubscriptionPlanModel;

/**
 * Coupon engine — the single source of truth for validating and applying
 * subscription coupons, shared by the web checkout (Cashfree), the Super Admin,
 * and the mobile API. Two kinds:
 *
 *   discount → reduces a paid order amount (percent or fixed ₹). Web/Cashfree.
 *   redeem   → grants free plan time directly (works everywhere incl. mobile).
 *
 * Every method returns a plain array so controllers can respond as JSON without
 * further shaping. Nothing here throws for a merely-invalid code — the caller
 * gets ['ok' => false, 'message' => ...].
 */
class Coupon
{
    private CouponModel $coupons;
    private CouponRedemptionModel $redemptions;

    public function __construct()
    {
        $this->coupons     = new CouponModel();
        $this->redemptions = new CouponRedemptionModel();
    }

    /**
     * Validate a code for a customer. Runs every gate EXCEPT the min-amount /
     * discount maths (those only make sense for a discount against an amount, and
     * are checked in preview()/applyToOrder). Returns the coupon row on success.
     *
     * @return array{ok:bool, message:string, coupon?:array}
     */
    public function validate(string $code, int $customerId, ?int $planId = null): array
    {
        $coupon = $this->coupons->findByCode($code);
        if (! $coupon) {
            return ['ok' => false, 'message' => 'This code is not valid.'];
        }
        if ((int) $coupon['status'] !== 1) {
            return ['ok' => false, 'message' => 'This code is no longer active.'];
        }

        $now = time();
        if (! empty($coupon['starts_at']) && strtotime((string) $coupon['starts_at']) > $now) {
            return ['ok' => false, 'message' => 'This code is not active yet.'];
        }
        if (! empty($coupon['expires_at']) && strtotime((string) $coupon['expires_at']) < $now) {
            return ['ok' => false, 'message' => 'This code has expired.'];
        }

        // Global cap.
        if ($coupon['max_redemptions'] !== null
            && (int) $coupon['redeemed_count'] >= (int) $coupon['max_redemptions']) {
            return ['ok' => false, 'message' => 'This code has reached its redemption limit.'];
        }

        // Per-customer cap.
        $perUser = (int) ($coupon['per_user_limit'] ?? 1);
        if ($perUser > 0
            && $this->redemptions->countForCustomer((int) $coupon['id'], $customerId) >= $perUser) {
            return ['ok' => false, 'message' => 'You have already used this code.'];
        }

        // Plan restriction (discount codes may be limited to one plan).
        if ($coupon['kind'] === 'discount'
            && $coupon['plan_id'] !== null
            && $planId !== null
            && (int) $coupon['plan_id'] !== (int) $planId) {
            return ['ok' => false, 'message' => 'This code does not apply to the selected plan.'];
        }

        return ['ok' => true, 'message' => 'Code applied.', 'coupon' => $coupon];
    }

    /**
     * The discount (₹) a coupon yields on an amount. Percent discounts honour an
     * optional cap; fixed discounts never exceed the amount. Always ≥ 0 and ≤ amount.
     */
    public function computeDiscount(array $coupon, float $amount): float
    {
        if ($coupon['kind'] !== 'discount' || $amount <= 0) {
            return 0.0;
        }
        $value = (float) $coupon['discount_value'];
        if (($coupon['discount_type'] ?? 'percent') === 'percent') {
            $off = $amount * ($value / 100);
            if ($coupon['max_discount'] !== null && $coupon['max_discount'] !== '') {
                $off = min($off, (float) $coupon['max_discount']);
            }
        } else {
            $off = $value;
        }
        $off = min($off, $amount);
        return round(max(0.0, $off), 2);
    }

    /**
     * Preview a DISCOUNT code against a plan's price without recording anything —
     * powers the "apply coupon" button on checkout.
     *
     * @return array{ok:bool, message:string, code?:string, discount?:float, final?:float, original?:float}
     */
    public function preview(string $code, int $customerId, int $planId, float $amount): array
    {
        $res = $this->validate($code, $customerId, $planId);
        if (! $res['ok']) {
            return $res;
        }
        $coupon = $res['coupon'];
        if ($coupon['kind'] !== 'discount') {
            return ['ok' => false, 'message' => 'This is a redeem code — use "Redeem a code" to claim free plan time.'];
        }
        if ((float) $coupon['min_amount'] > 0 && $amount < (float) $coupon['min_amount']) {
            return ['ok' => false, 'message' => 'This code needs a minimum order of ₹' . number_format((float) $coupon['min_amount'], 0) . '.'];
        }
        $discount = $this->computeDiscount($coupon, $amount);
        if ($discount <= 0) {
            return ['ok' => false, 'message' => 'This code gives no discount on this plan.'];
        }
        return [
            'ok'       => true,
            'message'  => 'Coupon applied — you save ₹' . number_format($discount, 2) . '.',
            'code'     => $coupon['code'],
            'original' => round($amount, 2),
            'discount' => $discount,
            'final'    => round($amount - $discount, 2),
        ];
    }

    /**
     * Record a discount redemption once a paid order is actually activated. Called
     * from the Cashfree activation path. Idempotent per order_id.
     */
    public function recordDiscount(array $coupon, int $customerId, string $orderId, ?int $planId, float $amountDiscounted): void
    {
        $already = $this->redemptions->where('order_id', $orderId)->countAllResults();
        if ($already > 0) {
            return;
        }
        $this->redemptions->insert([
            'coupon_id'         => (int) $coupon['id'],
            'customer_id'       => $customerId,
            'kind'              => 'discount',
            'order_id'          => $orderId,
            'plan_id'           => $planId,
            'amount_discounted' => round($amountDiscounted, 2),
            'days_granted'      => 0,
        ]);
        $this->coupons->bumpRedeemed((int) $coupon['id']);
    }

    /**
     * Redeem a REDEEM code: grants free plan time to the customer immediately and
     * records the redemption. Works on every platform (no gateway involved).
     *
     * @return array{ok:bool, message:string, plan?:string, plan_id?:int, days?:int, expires_at?:string}
     */
    public function redeem(string $code, int $customerId): array
    {
        $res = $this->validate($code, $customerId);
        if (! $res['ok']) {
            return $res;
        }
        $coupon = $res['coupon'];
        if ($coupon['kind'] !== 'redeem') {
            return ['ok' => false, 'message' => 'This is a discount code — apply it at checkout, not here.'];
        }

        $days = (int) ($coupon['free_days'] ?? 0);
        if ($days <= 0) {
            return ['ok' => false, 'message' => 'This code is misconfigured (no free period).'];
        }
        $planId = $coupon['plan_id'] !== null ? (int) $coupon['plan_id'] : null;
        $plans  = new SubscriptionPlanModel();
        $plan   = $planId ? $plans->find($planId) : null;
        if (! $plan || (int) $plan['status'] !== 1) {
            return ['ok' => false, 'message' => 'The plan for this code is unavailable.'];
        }

        // Extend from the later of "now" and any current unexpired window, so a
        // redeem code stacks on top of remaining paid/trial time rather than
        // shortening it.
        $subs = new SubscriptionModel();
        $cur  = $subs->forCustomer($customerId);
        $base = ($cur && ! empty($cur['expires_at']) && strtotime((string) $cur['expires_at']) > time())
            ? strtotime((string) $cur['expires_at']) : time();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . $days . ' days', $base));

        $subs->activateWithExpiry($customerId, (int) $plan['id'], $expiresAt, 'COUPON:' . $coupon['code']);

        $this->redemptions->insert([
            'coupon_id'         => (int) $coupon['id'],
            'customer_id'       => $customerId,
            'kind'              => 'redeem',
            'order_id'          => null,
            'plan_id'           => (int) $plan['id'],
            'amount_discounted' => 0,
            'days_granted'      => $days,
        ]);
        $this->coupons->bumpRedeemed((int) $coupon['id']);

        if (function_exists('activity_log')) {
            activity_log('Subscription', 'Add', "Redeemed code {$coupon['code']} → {$days} days of {$plan['name']} for customer #{$customerId}");
        }

        return [
            'ok'         => true,
            'message'    => "Code redeemed — {$days} days of {$plan['name']} added.",
            'plan'       => $plan['name'],
            'plan_id'    => (int) $plan['id'],
            'days'       => $days,
            'expires_at' => $expiresAt,
        ];
    }
}
