<?php

namespace App\Libraries;

use Config\Services;

/**
 * Server-side verification against the Google Play Developer API. Authenticates
 * with a service-account key (JWT bearer → OAuth2 access token) and reads a
 * subscription purchase's real state so the Android client is never trusted on
 * its own.
 *
 * Uses the modern Subscriptions v2 endpoint:
 *   GET .../purchases/subscriptionsv2/tokens/{token}
 * and acknowledges via the v1 endpoint (v2 has no acknowledge method).
 *
 * All credentials come from Config\GooglePlay (env-driven). When not configured,
 * every call returns ['ok' => false, 'error' => 'not_configured'] so callers can
 * surface a clean message without crashing.
 */
class GooglePlayVerifier
{
    private \Config\GooglePlay $cfg;
    private ?string $accessToken = null;

    private const SCOPE     = 'https://www.googleapis.com/auth/androidpublisher';
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';
    private const API_BASE  = 'https://androidpublisher.googleapis.com/androidpublisher/v3';

    public function __construct(?\Config\GooglePlay $cfg = null)
    {
        $this->cfg = $cfg ?? config('GooglePlay');
    }

    public function isConfigured(): bool
    {
        return $this->cfg->isConfigured();
    }

    /**
     * Fetch a subscription purchase's state (Subscriptions v2).
     *
     * @return array{ok:bool, error?:string, data?:array}
     */
    public function getSubscription(string $purchaseToken): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'not_configured'];
        }
        $token = $this->accessToken();
        if ($token === null) {
            return ['ok' => false, 'error' => 'auth_failed'];
        }

        $url = self::API_BASE . '/applications/' . rawurlencode($this->cfg->packageName)
            . '/purchases/subscriptionsv2/tokens/' . rawurlencode($purchaseToken);

        $res = $this->httpGet($url, $token);
        if (! $res['ok']) {
            return $res;
        }
        return ['ok' => true, 'data' => $res['data']];
    }

    /**
     * Acknowledge a subscription purchase (v1) so Google does not auto-refund it.
     * Safe to call when already acknowledged (Google returns 200/400 which we
     * treat as non-fatal).
     */
    public function acknowledge(string $productId, string $purchaseToken): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }
        $token = $this->accessToken();
        if ($token === null) {
            return false;
        }

        $url = self::API_BASE . '/applications/' . rawurlencode($this->cfg->packageName)
            . '/purchases/subscriptions/' . rawurlencode($productId)
            . '/tokens/' . rawurlencode($purchaseToken) . ':acknowledge';

        $client = Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
        $resp   = $client->post($url, [
            'headers' => ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json'],
            'body'    => '{}',
        ]);
        $code = $resp->getStatusCode();
        return $code >= 200 && $code < 300;
    }

    /**
     * Map a Subscriptions v2 `subscriptionState` to our internal status string.
     */
    public function mapState(string $subscriptionState): string
    {
        return match ($subscriptionState) {
            'SUBSCRIPTION_STATE_ACTIVE'           => 'active',
            'SUBSCRIPTION_STATE_IN_GRACE_PERIOD'  => 'in_grace',
            'SUBSCRIPTION_STATE_ON_HOLD'          => 'on_hold',
            'SUBSCRIPTION_STATE_PAUSED'           => 'paused',
            'SUBSCRIPTION_STATE_CANCELED'         => 'cancelled',
            'SUBSCRIPTION_STATE_EXPIRED'          => 'expired',
            'SUBSCRIPTION_STATE_PENDING'          => 'pending',
            default                               => 'pending',
        };
    }

    // -----------------------------------------------------------------
    // OAuth2 (service-account JWT bearer)
    // -----------------------------------------------------------------

    /** Obtain (and cache for this request) an access token for the Play API. */
    private function accessToken(): ?string
    {
        if ($this->accessToken !== null) {
            return $this->accessToken;
        }

        $creds = $this->credentials();
        if ($creds === null) {
            return null;
        }

        $now = time();
        $jwt = $this->signedJwt([
            'iss'   => $creds['client_email'],
            'scope' => self::SCOPE,
            'aud'   => $creds['token_uri'] ?? self::TOKEN_URI,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ], $creds['private_key']);
        if ($jwt === null) {
            return null;
        }

        $client = Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
        $resp   = $client->post($creds['token_uri'] ?? self::TOKEN_URI, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);
        if ($resp->getStatusCode() !== 200) {
            log_message('error', '[GooglePlay] token exchange failed: ' . $resp->getBody());
            return null;
        }
        $body = json_decode((string) $resp->getBody(), true);
        $this->accessToken = $body['access_token'] ?? null;
        return $this->accessToken;
    }

    /** Load + decode the service-account JSON key. */
    private function credentials(): ?array
    {
        $path = $this->cfg->keyPath();
        if ($path === '' || ! is_readable($path)) {
            return null;
        }
        $json = json_decode((string) file_get_contents($path), true);
        if (! is_array($json) || empty($json['client_email']) || empty($json['private_key'])) {
            log_message('error', '[GooglePlay] service-account key is missing client_email/private_key.');
            return null;
        }
        return $json;
    }

    /** Build and RS256-sign a JWT. Returns null if signing fails. */
    private function signedJwt(array $claims, string $privateKey): ?string
    {
        $header  = $this->b64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
        $payload = $this->b64url(json_encode($claims));
        $signingInput = $header . '.' . $payload;

        $signature = '';
        if (! openssl_sign($signingInput, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            log_message('error', '[GooglePlay] JWT signing failed.');
            return null;
        }
        return $signingInput . '.' . $this->b64url($signature);
    }

    private function b64url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * @return array{ok:bool, error?:string, data?:array}
     */
    private function httpGet(string $url, string $token): array
    {
        $client = Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
        $resp   = $client->get($url, ['headers' => ['Authorization' => 'Bearer ' . $token]]);
        $code   = $resp->getStatusCode();
        $body   = json_decode((string) $resp->getBody(), true);

        if ($code === 200 && is_array($body)) {
            return ['ok' => true, 'data' => $body];
        }
        if ($code === 404 || $code === 410) {
            return ['ok' => false, 'error' => 'not_found'];
        }
        log_message('error', "[GooglePlay] API GET {$code}: " . $resp->getBody());
        return ['ok' => false, 'error' => 'api_error'];
    }
}
