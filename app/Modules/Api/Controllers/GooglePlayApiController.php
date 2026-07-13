<?php

namespace Modules\Api\Controllers;

use App\Libraries\GooglePlayVerifier;
use App\Models\GooglePlayPurchaseModel;
use App\Models\PaymentOrderModel;
use App\Models\SettingModel;
use App\Models\SubscriptionModel;
use App\Models\SubscriptionPlanModel;

/**
 * Google Play Billing endpoints for the Android app.
 *
 *   POST /api/v1/google-play/verify-purchase   (Bearer) {product_id, purchase_token}
 *   POST /api/v1/google-play/rtdn/{secret}     (public, Pub/Sub push)
 *
 * SECURITY: the Android client is never trusted. Every purchase token is
 * verified against the Play Developer API (GooglePlayVerifier) before any plan
 * is activated; reused tokens are rejected. RTDN keeps the server in sync with
 * renewals / cancellations / expirations / refunds, and re-verifies each token
 * rather than trusting the notification body.
 *
 * Verification is config-gated (Config\GooglePlay): until a service-account key
 * is present, these endpoints return a clean "not configured" error.
 */
class GooglePlayApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    // ownerId() is inherited from BaseApiController (identical resolution).

    /**
     * Verify an Android subscription purchase and activate the matching plan.
     */
    public function verifyPurchase()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        $productId = trim((string) ($this->input('product_id') ?? ''));
        $token     = trim((string) ($this->input('purchase_token') ?? ''));
        if ($productId === '' || $token === '') {
            return $this->failValidationErrors('product_id and purchase_token are required.');
        }

        $verifier = new GooglePlayVerifier();
        if (! $verifier->isConfigured()) {
            return $this->fail('Billing is not configured on the server yet.', 503);
        }

        $ownerId = $this->ownerId($user);

        // Map the Play product id to one of our plans.
        $planCode = config('GooglePlay')->planCodeForProduct($productId);
        $plan     = (new SubscriptionPlanModel())->where('code', $planCode)->where('status', 1)->first();
        if (! $plan) {
            return $this->failValidationErrors("No active plan is mapped to product '{$productId}'.");
        }

        // Reuse protection: a token already tied to a different customer is rejected.
        $purchases = new GooglePlayPurchaseModel();
        $existing  = $purchases->findByToken($token);
        if ($existing && (int) $existing['customer_id'] !== $ownerId) {
            return $this->failForbidden('This purchase is linked to another account.');
        }

        // Authoritative state from Google.
        $res = $verifier->getSubscription($token);
        if (! $res['ok']) {
            $msg = $res['error'] === 'not_found'
                ? 'Purchase not found or no longer valid.'
                : 'Could not verify the purchase. Please try again.';
            return $this->failValidationErrors($msg);
        }
        $data = $res['data'];

        $parsed = $this->parseSubscription($verifier, $data, $productId);
        if (! $parsed['product_match']) {
            return $this->failValidationErrors('The purchase does not match the requested product.');
        }

        $this->persistPurchase($purchases, $token, $ownerId, (int) $user['id'], (int) $plan['id'], $productId, $parsed, $data);

        // Acknowledge (v1) if Google hasn't recorded it yet, so it is not auto-refunded.
        if (! $parsed['acknowledged'] && $verifier->acknowledge($productId, $token)) {
            $purchases->upsertByToken($token, ['acknowledged' => 1]);
        }

        // Grant access only for a genuinely active/grace subscription with a future expiry.
        if ($parsed['grant'] && $parsed['expiry']) {
            (new SubscriptionModel())->activateWithExpiry($ownerId, (int) $plan['id'], $parsed['expiry'], $parsed['order_id']);
            $purchases->upsertByToken($token, ['activated' => 1]);
            $this->recordTransaction($ownerId, $plan, (string) $parsed['order_id']);
            if (function_exists('activity_log')) {
                activity_log('Subscription', 'Edit', "Play Billing: activated {$plan['name']} (order {$parsed['order_id']})");
            }

            // Email a purchase confirmation to the account that owns the plan.
            $owner = (new \App\Models\UserModel())->find($ownerId);
            if ($owner && ! empty($owner['email'])) {
                \Config\Services::mailer()->subscriptionPurchase((string) $owner['email'], (string) ($owner['name'] ?? ''), [
                    'plan'       => $plan['name'],
                    'amount'     => $plan['price'],
                    'currency'   => '₹',
                    'expires_at' => (string) $parsed['expiry'],
                ]);
            }
        }

        return $this->respond([
            'status'             => 'ok',
            'subscription_state' => $parsed['status'],
            'plan'               => $plan['name'],
            'plan_code'          => $plan['code'],
            'expires_at'         => $parsed['expiry'],
            'auto_renewing'      => $parsed['auto_renewing'],
            'granted'            => $parsed['grant'],
        ]);
    }

    /**
     * Real-time Developer Notifications (Pub/Sub push). Public, gated by a shared
     * secret in the path. Always answers 200 quickly so Pub/Sub does not retry;
     * the token is re-verified against Google rather than trusting the payload.
     */
    public function rtdn($secret = '')
    {
        $cfg = config('GooglePlay');
        if ($cfg->rtdnSecret === '' || ! hash_equals($cfg->rtdnSecret, (string) $secret)) {
            return $this->failUnauthorized('Invalid notification secret.');
        }

        $body = $this->request->getJSON(true);
        $b64  = $body['message']['data'] ?? null;
        if (! $b64) {
            return $this->respond(['status' => 'ok']); // nothing to do (e.g. Pub/Sub verification ping)
        }

        $notification = json_decode((string) base64_decode($b64, true), true);
        if (is_array($notification)) {
            try {
                $this->handleNotification($notification);
            } catch (\Throwable $e) {
                log_message('error', '[GooglePlay] RTDN handling failed: ' . $e->getMessage());
            }
        }

        // Acknowledge receipt regardless — re-verification is the source of truth.
        return $this->respond(['status' => 'ok']);
    }

    // -----------------------------------------------------------------
    // Internals
    // -----------------------------------------------------------------

    /** Normalise a Subscriptions v2 payload to the fields we store/act on. */
    private function parseSubscription(GooglePlayVerifier $verifier, array $data, string $wantProductId): array
    {
        $state  = (string) ($data['subscriptionState'] ?? '');
        $status = $verifier->mapState($state);

        $expiry       = null;
        $productMatch  = false;
        $autoRenewing = false;
        $basePlanId   = null;

        foreach ($data['lineItems'] ?? [] as $li) {
            if (! empty($li['productId']) && $li['productId'] === $wantProductId) {
                $productMatch = true;
            }
            if (! empty($li['expiryTime'])) {
                $expiry = date('Y-m-d H:i:s', strtotime($li['expiryTime']));
            }
            if (isset($li['autoRenewingPlan'])) {
                $autoRenewing = (bool) ($li['autoRenewingPlan']['autoRenewEnabled'] ?? true);
            }
            if (! empty($li['offerDetails']['basePlanId'])) {
                $basePlanId = $li['offerDetails']['basePlanId'];
            }
        }

        $ack   = (string) ($data['acknowledgementState'] ?? '');
        $grant = in_array($status, ['active', 'in_grace'], true)
            && $expiry !== null && strtotime($expiry) > time();

        return [
            'status'         => $status,
            'expiry'         => $expiry,
            'product_match'  => $productMatch,
            'auto_renewing'  => $autoRenewing,
            'base_plan_id'   => $basePlanId,
            'order_id'       => $data['latestOrderId'] ?? null,
            'linked_token'   => $data['linkedPurchaseToken'] ?? null,
            'acknowledged'   => $ack === 'ACKNOWLEDGEMENT_STATE_ACKNOWLEDGED',
            'grant'          => $grant,
        ];
    }

    /** Upsert the google_play_purchases record from parsed data. */
    private function persistPurchase(
        GooglePlayPurchaseModel $purchases,
        string $token,
        int $customerId,
        int $userId,
        int $planId,
        string $productId,
        array $p,
        array $raw
    ): void {
        $purchases->upsertByToken($token, [
            'customer_id'           => $customerId,
            'user_id'               => $userId,
            'plan_id'               => $planId,
            'product_id'            => $productId,
            'base_plan_id'          => $p['base_plan_id'],
            'order_id'              => $p['order_id'],
            'linked_purchase_token' => $p['linked_token'],
            'expiry_time'           => $p['expiry'],
            'status'                => $p['status'],
            'auto_renewing'         => $p['auto_renewing'] ? 1 : 0,
            'acknowledged'          => $p['acknowledged'] ? 1 : 0,
            'raw'                   => json_encode($raw),
        ]);
    }

    /**
     * Apply an RTDN by re-verifying the token and reconciling both the purchase
     * record and the customer subscription.
     */
    private function handleNotification(array $notification): void
    {
        // Refund / chargeback.
        if (isset($notification['voidedPurchaseNotification'])) {
            $token = $notification['voidedPurchaseNotification']['purchaseToken'] ?? '';
            $this->revoke($token, 'refunded');
            return;
        }

        // Test ping from the Play Console.
        if (isset($notification['testNotification'])) {
            log_message('info', '[GooglePlay] RTDN test notification received.');
            return;
        }

        $sub = $notification['subscriptionNotification'] ?? null;
        if (! $sub || empty($sub['purchaseToken'])) {
            return;
        }
        $token     = (string) $sub['purchaseToken'];
        $productId = (string) ($sub['subscriptionId'] ?? '');
        $type      = (int) ($sub['notificationType'] ?? 0);

        $purchases = new GooglePlayPurchaseModel();
        $record    = $purchases->findByToken($token);
        // Unknown token with no prior record → nothing to reconcile against a customer.
        if (! $record && $productId === '') {
            return;
        }

        $verifier = new GooglePlayVerifier();
        if (! $verifier->isConfigured()) {
            return;
        }
        $res = $verifier->getSubscription($token);
        if (! $res['ok']) {
            return;
        }
        $productId = $productId !== '' ? $productId : (string) ($record['product_id'] ?? '');
        $parsed    = $this->parseSubscription($verifier, $res['data'], $productId);

        // Resolve the customer + plan.
        $customerId = $record ? (int) $record['customer_id'] : 0;
        if ($customerId === 0) {
            return; // cannot map an unseen token to a customer safely
        }
        $planCode = config('GooglePlay')->planCodeForProduct($productId);
        $plan     = (new SubscriptionPlanModel())->where('code', $planCode)->first();
        $planId   = $plan ? (int) $plan['id'] : (int) ($record['plan_id'] ?? 0);

        // Persist the fresh state.
        $purchases->upsertByToken($token, [
            'product_id'    => $productId,
            'base_plan_id'  => $parsed['base_plan_id'],
            'order_id'      => $parsed['order_id'],
            'expiry_time'   => $parsed['expiry'],
            'status'        => $parsed['status'],
            'auto_renewing' => $parsed['auto_renewing'] ? 1 : 0,
            'last_notification_type' => $type,
            'raw'           => json_encode($res['data']),
        ]);

        $subs = new SubscriptionModel();
        if ($parsed['grant'] && $planId > 0 && $parsed['expiry']) {
            // RENEWED / RECOVERED / RESTARTED / still-active CANCEL (access until expiry).
            $subs->activateWithExpiry($customerId, $planId, $parsed['expiry'], $parsed['order_id']);
            $purchases->upsertByToken($token, ['activated' => 1]);
            // Each billing period carries its own Play order id, so a renewal upserts
            // a fresh paid transaction (idempotent on the order id).
            if ($plan) {
                $this->recordTransaction($customerId, $plan, (string) $parsed['order_id']);
            }
        } elseif (in_array($parsed['status'], ['expired', 'revoked'], true)) {
            // EXPIRED / REVOKED — drop to the free floor.
            $subs->cancel($customerId);
            $purchases->upsertByToken($token, ['activated' => 0]);
        }
    }

    /** Mark a purchase revoked/refunded and withdraw access. */
    private function revoke(string $token, string $status): void
    {
        if ($token === '') {
            return;
        }
        $purchases = new GooglePlayPurchaseModel();
        $record    = $purchases->findByToken($token);
        if (! $record) {
            return;
        }
        $purchases->update($record['id'], ['status' => $status, 'activated' => 0, 'auto_renewing' => 0]);
        (new SubscriptionModel())->cancel((int) $record['customer_id']);
        if (function_exists('activity_log')) {
            activity_log('Subscription', 'Edit', "Play Billing: {$status} — access withdrawn (token …" . substr($token, -8) . ')');
        }
    }

    /**
     * Record a Play Billing payment in the shared payment_orders ledger so the
     * Super Admin Transactions page and revenue totals include it alongside the
     * web (Cashfree) flow. Keyed on the Play order id (unique per billing period),
     * so re-verification or a repeated RTDN never issues a second invoice.
     */
    private function recordTransaction(int $customerId, array $plan, string $orderId): void
    {
        if ($orderId === '' || $customerId <= 0) {
            return;
        }
        $orders   = new PaymentOrderModel();
        $existing = $orders->findByOrderId($orderId);
        if ($existing && (int) $existing['activated'] === 1) {
            return; // already recorded for this billing period
        }

        $data = [
            'customer_id'  => $customerId,
            'plan_id'      => (int) $plan['id'],
            'amount'       => (float) $plan['price'],
            'currency'     => 'INR',
            'gateway'      => 'googleplay',
            'status'       => 'paid',
            'activated'    => 1,
            'invoice_no'   => $this->nextInvoiceNo(),
            'invoice_date' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $orders->update((int) $existing['id'], $data);
        } else {
            $orders->insert(['order_id' => $orderId] + $data);
        }
    }

    /**
     * Next sequential invoice number for the current financial year (Apr–Mar),
     * mirroring the web SubscriptionController so both gateways share one series.
     */
    private function nextInvoiceNo(): string
    {
        $set     = new SettingModel();
        $fyStart = (int) date('n') >= 4 ? (int) date('Y') : (int) date('Y') - 1;
        $key     = 'invoice_seq_' . $fyStart;
        $seq     = (int) $set->get($key, 0, 0) + 1;
        $set->put($key, (string) $seq, 0);

        $prefix = (string) (function_exists('setting') ? setting('invoice_prefix', 'INV') : 'INV');
        $label  = $fyStart . '-' . substr((string) ($fyStart + 1), -2);
        return $prefix . '/' . $label . '/' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
