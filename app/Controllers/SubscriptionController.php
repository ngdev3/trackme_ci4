<?php

namespace App\Controllers;

use App\Libraries\Cashfree;
use App\Libraries\TaxInvoice;
use App\Models\PaymentOrderModel;
use App\Models\SettingModel;
use App\Models\SubscriptionModel;
use App\Models\SubscriptionPlanModel;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Customer-facing subscription / upgrade page. Shows the current plan and trial
 * status and the paid packages. Customers pay online through the Cashfree
 * gateway (self-serve, auto-activated) — the Super Admin can also activate
 * manually as a fallback.
 */
class SubscriptionController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'settings', 'format', 'company', 'subscription'];

    public function index()
    {
        $plans = (new SubscriptionPlanModel())
            ->where('status', 1)->where('price >', 0)
            ->orderBy('price', 'ASC')->findAll();

        // Build each package's feature bullets straight from its DB flags so the
        // cards always match what is actually enforced (nothing hardcoded).
        $planFeatures = [];
        foreach ($plans as $p) {
            $planFeatures[(int) $p['id']] = $this->cardFeatures($p);
        }

        $state = sub_state();

        // Which plan card to mark "Active" (green) + its end date — only when the
        // customer is on a paid plan.
        $activePlanId = null;
        $activeUntil  = null;
        if (($state['reason'] ?? '') === 'paid') {
            $cid = sub_customer_id();
            $sub = $cid ? (new SubscriptionModel())->forCustomer($cid) : null;
            if ($sub) {
                $activePlanId = $sub['plan_id'] !== null ? (int) $sub['plan_id'] : null;
                $activeUntil  = $sub['expires_at'] ?? null;
            }
        }

        return $this->render('subscription', [
            'title'        => 'Subscription',
            'breadcrumb'   => [['label' => 'Subscription']],
            'state'        => $state,
            'trialDays'    => sub_trial_days(),
            'plans'        => $plans,
            'planFeatures' => $planFeatures,
            'activePlanId' => $activePlanId,
            'activeUntil'  => $activeUntil,
            'locked'       => [],
            'gatewayReady' => (new Cashfree())->isConfigured(),
            'gatewayMode'  => (new Cashfree())->jsMode(),
        ]);
    }

    // =================================================================
    // Cashfree online payment (self-serve, auto-activation)
    // =================================================================

    /**
     * Create a Cashfree order for a plan and return its payment session so the
     * browser SDK can open checkout. AJAX JSON. Never trusts the client for the
     * amount — it is read from the plan row.
     */
    public function pay($planId = null)
    {
        $cf = new Cashfree();
        if (! $cf->isConfigured()) {
            return $this->response->setStatusCode(503)->setJSON(['ok' => false, 'message' => 'Online payment is not available right now.']);
        }

        $plan = (new SubscriptionPlanModel())->where('id', (int) $planId)->where('status', 1)->where('price >', 0)->first();
        if (! $plan) {
            return $this->response->setStatusCode(404)->setJSON(['ok' => false, 'message' => 'Plan not found.']);
        }

        $customerId = (int) (sub_customer_id() ?? user_id());
        if (! $customerId) {
            return $this->response->setStatusCode(401)->setJSON(['ok' => false, 'message' => 'Please sign in again.']);
        }

        $user    = current_user() ?: [];
        $orderId = 'SUB' . $customerId . 'P' . (int) $plan['id'] . 'T' . time() . random_int(100, 999);

        $orders = new PaymentOrderModel();
        $orders->insert([
            'order_id'    => $orderId,
            'customer_id' => $customerId,
            'plan_id'     => (int) $plan['id'],
            'amount'      => round((float) $plan['price'], 2),
            'currency'    => 'INR',
            'status'      => 'created',
        ]);

        $res = $cf->createOrder(
            $orderId,
            (float) $plan['price'],
            [
                'id'    => 'cust_' . $customerId,
                'email' => (string) ($user['email'] ?? ''),
                'phone' => $this->customerPhone($user),
                'name'  => (string) ($user['name'] ?? 'Customer'),
            ],
            site_url('subscription/callback') . '?order_id=' . $orderId,
            site_url('subscription/webhook'),
            $plan['name'] . ' subscription'
        );

        if (! $res['ok']) {
            $orders->where('order_id', $orderId)->set(['status' => 'failed'])->update();
            return $this->response->setStatusCode(502)->setJSON(['ok' => false, 'message' => $res['error'] ?? 'Could not start payment.']);
        }

        $orders->where('order_id', $orderId)->set([
            'cf_order_id'        => $res['cf_order_id'] ?? null,
            'payment_session_id' => $res['session'],
        ])->update();

        return $this->response->setJSON([
            'ok'          => true,
            'order_id'    => $orderId,
            'session'     => $res['session'],
            'mode'        => $cf->jsMode(),
        ]);
    }

    /**
     * Cashfree return_url. The customer lands here after checkout; we confirm the
     * order server-side (never trust the redirect) and activate on PAID.
     */
    public function callback()
    {
        $orderId = (string) $this->request->getGet('order_id');
        $order   = $orderId ? (new PaymentOrderModel())->findByOrderId($orderId) : null;

        if (! $order) {
            return redirect()->to(site_url('subscription'))->with('error', 'Payment reference not found.');
        }
        if ((int) $order['activated'] === 1) {
            return redirect()->to(site_url('subscription'))->with('success', 'Your subscription is active. Thank you!');
        }

        $cf     = new Cashfree();
        $status = $cf->fetchOrder($orderId);
        $orderStatus = $status['ok'] ? strtoupper((string) ($status['status'] ?? '')) : '';

        // Paid → activate.
        if ($orderStatus === 'PAID') {
            $this->activateOrder($order, $cf->fetchPaidPaymentId($orderId));
            return redirect()->to(site_url('subscription'))->with('success', 'Payment successful — your subscription is now active!');
        }

        // Distinguish an outright failure from a payment that is merely pending.
        $payStatus = $cf->fetchLatestPaymentStatus($orderId);
        $failed    = in_array($payStatus, ['FAILED', 'USER_DROPPED', 'CANCELLED'], true)
            || in_array($orderStatus, ['EXPIRED', 'TERMINATED', 'TERMINATION_REQUESTED'], true);

        if ($failed) {
            (new PaymentOrderModel())->where('order_id', $orderId)->set(['status' => 'failed'])->update();
            $this->notifySubscription(
                (int) $order['customer_id'],
                'error',
                'Payment could not be completed',
                'Your recent subscription payment did not go through. No plan was activated. If any amount was deducted, it will be refunded within 3-5 days. Tap to try again.',
                site_url('subscription')
            );
            return redirect()->to(site_url('subscription'))
                ->with('error', 'Your payment could not be completed. Please try again. If any amount was deducted, it will be refunded within 3-5 days.');
        }

        // Still processing (e.g. bank pending) — the webhook will finalise it.
        return redirect()->to(site_url('subscription'))
            ->with('info', 'Your payment is being processed. If it succeeds, your subscription will activate automatically in a few minutes.');
    }

    /**
     * Cashfree webhook (notify_url). Server-to-server; signature-verified. This is
     * the authoritative activation path (fires even if the customer closes the
     * browser). No auth/CSRF — guarded by the HMAC signature instead.
     */
    public function webhook()
    {
        $raw       = $this->request->getBody() ?? '';
        $signature = (string) $this->request->getHeaderLine('x-webhook-signature');
        $timestamp = (string) $this->request->getHeaderLine('x-webhook-timestamp');

        $cf = new Cashfree();
        if (! $cf->verifyWebhookSignature($timestamp, $raw, $signature)) {
            log_message('warning', 'Cashfree webhook: signature verification failed.');
            return $this->response->setStatusCode(401)->setJSON(['ok' => false]);
        }

        $data    = json_decode($raw, true) ?: [];
        $orderId = (string) ($data['data']['order']['order_id'] ?? '');
        $payStat = (string) ($data['data']['payment']['payment_status'] ?? '');
        $cfPayId = (string) ($data['data']['payment']['cf_payment_id'] ?? '');

        if ($orderId !== '') {
            $orders = new PaymentOrderModel();
            $order  = $orders->findByOrderId($orderId);
            if ($order && (int) $order['activated'] !== 1) {
                if ($payStat === 'SUCCESS') {
                    // Re-confirm against the API before activating (defence in depth).
                    $status = $cf->fetchOrder($orderId);
                    if ($status['ok'] && ($status['status'] ?? '') === 'PAID') {
                        $this->activateOrder($order, $cfPayId ?: null);
                    }
                } elseif (in_array($payStat, ['FAILED', 'USER_DROPPED', 'CANCELLED'], true)) {
                    $orders->where('order_id', $orderId)->set(['status' => 'failed'])->update();
                }
            }
        }

        return $this->response->setJSON(['ok' => true]);
    }

    /**
     * Activate the subscription for a verified-paid order. Idempotent and
     * amount-checked; sets the order to paid + records the payment id.
     */
    private function activateOrder(array $order, ?string $cfPaymentId): void
    {
        $orders = new PaymentOrderModel();

        $plan = (new SubscriptionPlanModel())->find((int) $order['plan_id']);
        // Guard against tampering: the charged amount must match the plan price.
        if (! $plan || round((float) $plan['price'], 2) !== round((float) $order['amount'], 2)) {
            $orders->where('order_id', $order['order_id'])->set(['status' => 'failed'])->update();
            log_message('error', 'Cashfree activation blocked: amount/plan mismatch for order ' . $order['order_id']);
            return;
        }

        (new SubscriptionModel())->activatePaid((int) $order['customer_id'], $plan);

        $orders->where('order_id', $order['order_id'])->set([
            'status'        => 'paid',
            'activated'     => 1,
            'cf_payment_id' => $cfPaymentId,
            'invoice_no'    => $this->nextInvoiceNo(),
            'invoice_date'  => date('Y-m-d H:i:s'),
        ])->update();

        // Stamp the payment reference on the customer's current subscription.
        $sub = (new SubscriptionModel())->where('customer_id', (int) $order['customer_id'])->orderBy('id', 'DESC')->first();
        if ($sub) {
            (new SubscriptionModel())->update($sub['id'], ['payment_id' => $order['order_id']]);
        }

        activity_log('Subscription', 'Add', "Cashfree payment {$order['order_id']} activated plan #{$order['plan_id']} for customer #{$order['customer_id']}");

        // Tell the customer their payment went through and the plan is live.
        $this->notifySubscription(
            (int) $order['customer_id'],
            'success',
            'Payment successful — ' . $plan['name'] . ' activated',
            'Your payment of ₹' . number_format((float) $order['amount'], 2) . ' was received and the ' . $plan['name'] . ' plan is now active. Tap to view your payment history and tax receipt.',
            site_url('subscription/transactions')
        );
    }

    /**
     * Raise an in-app (and best-effort browser push) notification for a
     * subscription event, targeted at the owning customer. Never lets a
     * notification failure break the billing action.
     */
    private function notifySubscription(int $customerId, string $type, string $title, string $message, ?string $url = null): void
    {
        if ($customerId <= 0) {
            return;
        }
        try {
            service('notifier')->user($customerId, $title, $message, [
                'type'       => $type,
                'module'     => null, // null module = always visible to the targeted user
                'priority'   => $type === 'success' ? 'high' : 'normal',
                'action_url' => $url ?: site_url('subscription'),
                'created_by' => $customerId,
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Subscription notification failed: ' . $e->getMessage());
        }
    }

    /** A Cashfree-acceptable 10-digit phone (customer's mobile, else a placeholder). */
    private function customerPhone(array $user): string
    {
        $digits = preg_replace('/\D+/', '', (string) ($user['mobile'] ?? ''));
        $digits = substr((string) $digits, -10);
        return strlen($digits) === 10 ? $digits : '9999999999';
    }

    /** Next gap-free invoice number for the current financial year (FY sequence). */
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

    // =================================================================
    // Payment history + tax receipt
    // =================================================================

    /** The signed-in customer's payment history. */
    public function transactions()
    {
        $customerId = (int) (sub_customer_id() ?? user_id());
        $orders     = $customerId ? (new PaymentOrderModel())->forCustomer($customerId) : [];
        $sub        = $customerId ? (new SubscriptionModel())->forCustomer($customerId) : null;

        return $this->render('subscription_transactions', [
            'title'      => 'Payment History',
            'breadcrumb' => [['label' => 'Subscription', 'url' => site_url('subscription')], ['label' => 'Payment History']],
            'orders'     => $orders,
            'state'      => sub_state(),
            'sub'        => $sub,
        ]);
    }

    /**
     * Download the GST tax receipt (PDF) for a paid order. Only the owning
     * customer or a Super Admin may fetch it.
     */
    public function receipt($orderId = null)
    {
        $order = (new PaymentOrderModel())->findByOrderId((string) $orderId);
        if (! $order || (int) $order['activated'] !== 1) {
            return redirect()->to(site_url('subscription/transactions'))->with('error', 'Receipt is only available for completed payments.');
        }

        $isOwner = (int) $order['customer_id'] === (int) (sub_customer_id() ?? user_id());
        $isAdmin = function_exists('is_super_admin_account') && is_super_admin_account();
        if (! $isOwner && ! $isAdmin) {
            return redirect()->to(site_url('subscription/transactions'))->with('error', 'You cannot access this receipt.');
        }

        $inv  = TaxInvoice::build($order);
        $html = view('receipt', ['inv' => $inv]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $name = 'Receipt-' . preg_replace('/[^A-Za-z0-9._-]+/', '-', (string) ($order['invoice_no'] ?: $order['order_id']));
        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $name . '.pdf"')
            ->setBody($dompdf->output());
    }

    /**
     * The bullet list for a plan card, derived from its DB flags: firm/user
     * limits, the baseline features every package includes, then the gated
     * features this package unlocks. Stays in sync with the enforced flags.
     *
     * @return list<string>
     */
    private function cardFeatures(array $p): array
    {
        helper('subscription');

        $limit = $p['max_firms'];
        $firms = ($limit === null || $limit === '' || (int) $limit === 0)
            ? 'Unlimited Firms'
            : ((int) $limit) . ' Firm' . ((int) $limit === 1 ? '' : 's') . ' Only';

        $users = ($p['max_users'] === null || $p['max_users'] === '' || (int) $p['max_users'] === 0)
            ? 'Unlimited Users'
            : number_format((int) $p['max_users']) . ' Users';

        // Baseline features included in every package (incl. Basic).
        $list = [
            $firms,
            $users,
            'Rokadh Parcha',
            'PDF / Print',
            'Reports',
            'Attachments',
            'Statement Download',
            'Opening Balance',
        ];

        // Gated features this package unlocks (read from feat_* flags).
        foreach (feature_catalog() as $key => $label) {
            if ((int) ($p['feat_' . $key] ?? 0) === 1) {
                $list[] = $label;
            }
        }

        return $list;
    }
}
