<?php

namespace Modules\Api\Controllers;

use App\Models\PaymentOrderModel;
use App\Models\SubscriptionModel;
use App\Models\SubscriptionPlanModel;

/**
 * Subscription / plan management for the mobile app. The subscription belongs to
 * the customer that owns the caller's active company (mirrors the web + MeApi
 * resolution via customer_effective_plan()). Bearer-token authenticated.
 *
 * NOTE: this build has no payment gateway. subscribe() assigns the chosen plan
 * directly — a dev/no-gateway flow, not a real purchase.
 */
class SubscriptionApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    // ownerId() is inherited from BaseApiController (identical resolution:
    // active company's owner_id, falling back to the user's own id).

    private function shapePlan(array $p): array
    {
        $features = [];
        foreach (SubscriptionPlanModel::FEATURE_COLUMNS as $col) {
            if ((int) ($p['feat_' . $col] ?? 0) === 1) {
                $features[] = function_exists('feature_label') ? feature_label($col) : $col;
            }
        }
        return [
            'id'            => (int) $p['id'],
            'name'          => $p['name'],
            'code'          => $p['code'],
            'price'         => (float) $p['price'],
            'billing_cycle' => $p['billing_cycle'],
            'max_firms'     => $p['max_firms'] !== null ? (int) $p['max_firms'] : null,
            'max_users'     => $p['max_users'] !== null ? (int) $p['max_users'] : null,
            'features'      => $features,
        ];
    }

    /** GET api/v1/subscription — current subscription + available plans. */
    public function index()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $ownerId = $this->ownerId($user);

        $sub       = (new SubscriptionModel())->forCustomer($ownerId);
        $effective = customer_effective_plan($ownerId);
        $plans     = array_map(fn (array $p) => $this->shapePlan($p), (new SubscriptionPlanModel())->active());

        $current = null;
        if ($sub) {
            $current = [
                'plan_id'        => $sub['plan_id'] !== null ? (int) $sub['plan_id'] : null,
                'plan_name'      => $effective['name'] ?? ($sub['plan_name'] ?? null),
                'plan_code'      => $effective['code'] ?? ($sub['plan_code'] ?? null),
                'status'         => $sub['status'],
                'payment_status' => $sub['payment_status'],
                'started_at'     => $sub['started_at'],
                'expires_at'     => $sub['expires_at'],
            ];
        }

        return $this->respond([
            'status'    => 'ok',
            'current'   => $current,
            'effective' => ['name' => $effective['name'] ?? null, 'code' => $effective['code'] ?? null],
            'plans'     => $plans,
        ]);
    }

    /**
     * GET api/v1/subscription/payments — the customer's payment history, newest
     * first. Mirrors the web SubscriptionController::transactions.
     */
    public function payments()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $ownerId = $this->ownerId($user);

        $orders = (new PaymentOrderModel())->forCustomer($ownerId);
        $out    = array_map(static fn (array $o): array => [
            'id'         => (int) $o['id'],
            'order_id'   => $o['order_id'],
            'invoice_no' => $o['invoice_no'] ?: null,
            'plan'       => $o['plan_name'] ?? null,
            'amount'     => (float) $o['amount'],
            'currency'   => $o['currency'] ?: '₹',
            'gateway'    => $o['gateway'],
            'status'     => $o['status'],
            'activated'  => (int) ($o['activated'] ?? 0) === 1,
            'refunded'   => (int) ($o['refunded'] ?? 0) === 1,
            'date'       => $o['invoice_date'] ?: $o['created_at'],
        ], $orders);

        return $this->respond(['status' => 'ok', 'payments' => $out]);
    }

    /** POST api/v1/subscription/subscribe — assign the chosen plan (no gateway). */
    public function subscribe()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $ownerId = $this->ownerId($user);

        $planId = (int) $this->input('plan_id');
        $plan   = (new SubscriptionPlanModel())->find($planId);
        if (! $plan || (int) $plan['status'] !== 1) {
            return $this->failValidationErrors('Invalid or inactive plan.');
        }

        $subs = new SubscriptionModel();
        if ((float) $plan['price'] <= 0) {
            // Free plan — activate without a paid window.
            $row  = $subs->where('customer_id', $ownerId)->orderBy('id', 'DESC')->first();
            $data = ['plan_id' => $planId, 'status' => 'active', 'payment_status' => 'free', 'expires_at' => null];
            if ($row) {
                $subs->update($row['id'], $data);
            } else {
                $subs->insert($data + ['customer_id' => $ownerId, 'started_at' => date('Y-m-d H:i:s')]);
            }
        } else {
            $subs->activatePaid($ownerId, $plan);
        }

        if (function_exists('activity_log')) {
            activity_log('Subscription', 'Edit', "Switched to plan {$plan['name']} (mobile)");
        }

        // Email a purchase confirmation for paid plans (the free/downgrade case
        // isn't a "purchase", so it stays silent).
        if ((float) $plan['price'] > 0) {
            $owner = (new \App\Models\UserModel())->find($ownerId);
            if ($owner && ! empty($owner['email'])) {
                $sub = (new SubscriptionModel())->forCustomer($ownerId);
                \Config\Services::mailer()->subscriptionPurchase((string) $owner['email'], (string) ($owner['name'] ?? ''), [
                    'plan'       => $plan['name'],
                    'amount'     => $plan['price'],
                    'currency'   => '₹',
                    'expires_at' => $sub['expires_at'] ?? '',
                ]);
            }
        }

        return $this->respond([
            'status'  => 'ok',
            'message' => "Subscription updated to {$plan['name']}.",
            'plan'    => $plan['name'],
        ]);
    }
}
