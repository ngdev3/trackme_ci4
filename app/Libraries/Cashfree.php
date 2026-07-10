<?php

namespace App\Libraries;

use Config\Services;

/**
 * Thin client for the Cashfree Payments (PG) Orders API. Creates orders, reads
 * their status back for server-side verification, and validates webhook
 * signatures. All credentials/URLs come from Config\Cashfree (env-driven).
 *
 * Flow: createOrder() → return payment_session_id to the browser (Cashfree.js
 * checkout) → Cashfree redirects to the return_url and POSTs the webhook →
 * fetchOrder()/verifyWebhookSignature() confirm PAID before we activate.
 */
class Cashfree
{
    private \Config\Cashfree $cfg;

    public function __construct(?\Config\Cashfree $cfg = null)
    {
        $this->cfg = $cfg ?? config('Cashfree');
    }

    public function isConfigured(): bool
    {
        return $this->cfg->isConfigured();
    }

    public function jsMode(): string
    {
        return $this->cfg->jsMode();
    }

    /**
     * Create a Cashfree order.
     *
     * @return array{ok:bool, session?:string, cf_order_id?:string, status?:string, error?:string, raw?:array}
     */
    public function createOrder(
        string $orderId,
        float $amount,
        array $customer,
        string $returnUrl,
        string $notifyUrl,
        string $note = ''
    ): array {
        $payload = [
            'order_id'       => $orderId,
            'order_amount'   => round($amount, 2),
            'order_currency' => 'INR',
            'customer_details' => [
                'customer_id'    => (string) ($customer['id'] ?? 'guest'),
                'customer_email' => (string) ($customer['email'] ?? ''),
                'customer_phone' => (string) ($customer['phone'] ?? ''),
                'customer_name'  => (string) ($customer['name'] ?? ''),
            ],
            'order_meta' => [
                'return_url' => $returnUrl,
                'notify_url' => $notifyUrl,
            ],
            'order_note' => $note,
        ];

        $res = $this->post('/orders', $payload);
        if (! $res['ok']) {
            return $res;
        }
        $body = $res['body'];

        if (empty($body['payment_session_id'])) {
            return ['ok' => false, 'error' => $body['message'] ?? 'No payment session returned.', 'raw' => $body];
        }
        return [
            'ok'          => true,
            'session'     => (string) $body['payment_session_id'],
            'cf_order_id' => (string) ($body['cf_order_id'] ?? ''),
            'status'      => (string) ($body['order_status'] ?? ''),
            'raw'         => $body,
        ];
    }

    /**
     * Fetch an order's current server-side truth (never trust the browser).
     *
     * @return array{ok:bool, status?:string, amount?:float, cf_payment_id?:string, error?:string, raw?:array}
     */
    public function fetchOrder(string $orderId): array
    {
        $res = $this->get('/orders/' . rawurlencode($orderId));
        if (! $res['ok']) {
            return $res;
        }
        $body = $res['body'];
        return [
            'ok'     => true,
            'status' => (string) ($body['order_status'] ?? ''), // ACTIVE|PAID|EXPIRED|TERMINATED
            'amount' => (float) ($body['order_amount'] ?? 0),
            'raw'    => $body,
        ];
    }

    /** The most recent successful payment id for an order, if any. */
    public function fetchPaidPaymentId(string $orderId): ?string
    {
        foreach ($this->fetchPayments($orderId) as $p) {
            if (($p['payment_status'] ?? '') === 'SUCCESS') {
                return (string) ($p['cf_payment_id'] ?? '');
            }
        }
        return null;
    }

    /**
     * Status of the latest payment attempt on an order, uppercased, or null when
     * no attempt has been made. Values: SUCCESS | FAILED | PENDING |
     * USER_DROPPED | CANCELLED | NOT_ATTEMPTED.
     */
    public function fetchLatestPaymentStatus(string $orderId): ?string
    {
        $payments = $this->fetchPayments($orderId);
        if ($payments === []) {
            return null;
        }
        // The API returns attempts oldest→newest; take the last.
        $last = $payments[array_key_last($payments)];
        $s    = strtoupper((string) ($last['payment_status'] ?? ''));
        return $s !== '' ? $s : null;
    }

    /** All payment attempts for an order (empty on error). */
    private function fetchPayments(string $orderId): array
    {
        $res = $this->get('/orders/' . rawurlencode($orderId) . '/payments');
        return ($res['ok'] && is_array($res['body'] ?? null)) ? $res['body'] : [];
    }

    /**
     * Verify a webhook payload signature. Cashfree signs base64(HMAC-SHA256(
     * timestamp + rawBody, secretKey)). Constant-time compared.
     */
    public function verifyWebhookSignature(string $timestamp, string $rawBody, string $signature): bool
    {
        if ($signature === '' || $this->cfg->secretKey === '') {
            return false;
        }
        $expected = base64_encode(hash_hmac('sha256', $timestamp . $rawBody, $this->cfg->secretKey, true));
        return hash_equals($expected, $signature);
    }

    // ---------------------------------------------------------------
    private function post(string $path, array $payload): array
    {
        return $this->request('post', $path, $payload);
    }

    private function get(string $path): array
    {
        return $this->request('get', $path, null);
    }

    /**
     * @return array{ok:bool, body?:array, error?:string, code?:int}
     */
    private function request(string $method, string $path, ?array $payload): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'Payment gateway is not configured.'];
        }

        $client = Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
        $opts   = [
            'headers' => [
                'Accept'          => 'application/json',
                'Content-Type'    => 'application/json',
                'x-api-version'   => $this->cfg->apiVersion,
                'x-client-id'     => $this->cfg->appId,
                'x-client-secret' => $this->cfg->secretKey,
            ],
        ];
        if ($payload !== null) {
            $opts['body'] = json_encode($payload);
        }

        try {
            $response = $client->request(strtoupper($method), $this->cfg->baseUrl() . $path, $opts);
        } catch (\Throwable $e) {
            log_message('error', 'Cashfree request failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'Could not reach the payment gateway.'];
        }

        $code = $response->getStatusCode();
        $body = json_decode((string) $response->getBody(), true);

        if ($code >= 200 && $code < 300) {
            return ['ok' => true, 'body' => is_array($body) ? $body : [], 'code' => $code];
        }
        return [
            'ok'    => false,
            'error' => is_array($body) ? ($body['message'] ?? 'Gateway error.') : 'Gateway error.',
            'code'  => $code,
            'body'  => is_array($body) ? $body : [],
        ];
    }
}
