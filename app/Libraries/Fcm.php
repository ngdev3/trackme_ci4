<?php

namespace App\Libraries;

use Config\Services;

/**
 * Firebase Cloud Messaging (HTTP v1) sender for native device tokens.
 *
 * Native app installs register an FCM/APNs device token (stored in
 * push_subscriptions with p256dh='fcm'); this delivers a notification to those
 * tokens. Web browsers use the separate VAPID Web-Push path (WebPush).
 *
 * Configuration (in .env) — the feature is a no-op until a service account is
 * provided, so nothing breaks on installs that don't use native push:
 *
 *   fcm.serviceAccount = 'C:/path/to/firebase-service-account.json'
 *
 * The project id and OAuth token endpoint are read from that JSON. The service
 * account is created in the Firebase console (Project settings → Service
 * accounts → Generate new private key) and must belong to the same Firebase
 * project whose google-services.json ships in the Android app.
 *
 * End-to-end delivery needs a real device token, so it can only be exercised
 * from an installed app build; token assembly here is otherwise self-contained.
 */
class Fcm
{
    private const SCOPE   = 'https://www.googleapis.com/auth/firebase.messaging';
    private const SEND_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';

    /** Decoded service-account JSON, or null when not configured. */
    private ?array $account = null;

    public function __construct()
    {
        $path = $this->resolvePath((string) env('fcm.serviceAccount', ''));
        if ($path !== '' && is_file($path)) {
            $json = json_decode((string) file_get_contents($path), true);
            if (is_array($json) && ! empty($json['client_email']) && ! empty($json['private_key']) && ! empty($json['project_id'])) {
                $this->account = $json;
            } else {
                log_message('error', '[FCM] service account file is present but invalid: {p}', ['p' => $path]);
            }
        }
    }

    /** Whether FCM is configured and usable. */
    public function isConfigured(): bool
    {
        return $this->account !== null;
    }

    /**
     * Resolve the configured path. An absolute path (Windows drive or POSIX
     * root) is used as-is; anything else is treated as relative to the project
     * root (ROOTPATH), so `writable/keys/fcm-service-account.json` works the
     * same on local and the server without hardcoding an absolute path.
     */
    private function resolvePath(string $path): string
    {
        if ($path === '') {
            return '';
        }
        if (! preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $path)) {
            $path = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . $path;
        }
        return $path;
    }

    /** The Firebase project id sends are targeted at (from the service account). */
    public function projectId(): ?string
    {
        return $this->account['project_id'] ?? null;
    }

    /** The service-account identity (for diagnostics). */
    public function clientEmail(): ?string
    {
        return $this->account['client_email'] ?? null;
    }

    /**
     * End-to-end configuration probe used by `php spark fcm:doctor`. Mints a real
     * OAuth token and attempts one send (to a dummy token unless a real device
     * token is given), returning the raw outcome so a human can diagnose it.
     *
     * @return array{configured:bool,project_id:?string,client_email:?string,token_minted:bool,send_status:?int,send_body:?string,error:?string}
     */
    public function probe(string $token = 'DUMMY_DIAGNOSTIC_TOKEN'): array
    {
        $out = [
            'configured'   => $this->isConfigured(),
            'project_id'   => $this->projectId(),
            'client_email' => $this->clientEmail(),
            'token_minted' => false,
            'send_status'  => null,
            'send_body'    => null,
            'error'        => null,
        ];
        if (! $this->isConfigured()) {
            $out['error'] = 'fcm.serviceAccount is not set or the JSON is invalid.';
            return $out;
        }

        try {
            $accessToken       = $this->accessToken();
            $out['token_minted'] = true;
        } catch (\Throwable $e) {
            $out['error'] = 'Could not mint OAuth token: ' . $e->getMessage();
            return $out;
        }

        try {
            $client = Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
            $res    = $client->post(sprintf(self::SEND_URL, $this->account['project_id']), [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken, 'Content-Type' => 'application/json'],
                'body'    => json_encode(['message' => [
                    'token'        => $token,
                    'notification' => ['title' => 'FCM doctor', 'body' => 'Configuration probe'],
                ]]),
            ]);
            $out['send_status'] = $res->getStatusCode();
            $out['send_body']   = substr((string) $res->getBody(), 0, 500);
        } catch (\Throwable $e) {
            $out['error'] = 'Send request failed: ' . $e->getMessage();
        }

        return $out;
    }

    /**
     * Send one notification to many device tokens. Returns the list of tokens
     * the FCM service reported as permanently invalid (caller should prune them).
     *
     * @param list<string> $tokens
     * @return list<string> invalid/expired tokens
     */
    public function sendToTokens(array $tokens, string $title, string $body, ?string $url = null): array
    {
        $tokens = array_values(array_unique(array_filter($tokens)));
        if ($tokens === [] || ! $this->isConfigured()) {
            return [];
        }

        try {
            $accessToken = $this->accessToken();
        } catch (\Throwable $e) {
            log_message('error', '[FCM] could not mint access token: ' . $e->getMessage());
            return [];
        }

        $sendUrl = sprintf(self::SEND_URL, $this->account['project_id']);
        $client  = Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
        $invalid = [];

        foreach ($tokens as $token) {
            $message = [
                'message' => [
                    'token'        => $token,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data'         => ['url' => (string) ($url ?? '')],
                    'android'      => ['priority' => 'high'],
                ],
            ];

            try {
                $res    = $client->post($sendUrl, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken,
                        'Content-Type'  => 'application/json',
                    ],
                    'body'    => json_encode($message),
                ]);
                $status = $res->getStatusCode();

                // 400 (invalid token) / 404 (unregistered token) mean the token
                // is dead. 403 is usually a sender/project mismatch; keep those
                // tokens so a corrected Firebase service account can use them.
                if (in_array($status, [400, 404], true)) {
                    $invalid[] = $token;
                    log_message('warning', '[FCM] token rejected ({s}): {b}', ['s' => $status, 'b' => substr((string) $res->getBody(), 0, 300)]);
                } elseif ($status === 403) {
                    // Sender/project mismatch: the service account is not authorised
                    // to send on project "{project_id}". Almost always means
                    // fcm.serviceAccount points at the wrong Firebase project — it
                    // must be a key generated FROM the same project as the app's
                    // google-services.json. Keep the token so a corrected key works.
                    log_message('error', '[FCM] 403 permission denied on project "{p}" — fcm.serviceAccount ({e}) is not authorised for this Firebase project. Run `php spark fcm:doctor`. Body: {b}', [
                        'p' => $this->account['project_id'] ?? '?',
                        'e' => $this->account['client_email'] ?? '?',
                        'b' => substr((string) $res->getBody(), 0, 300),
                    ]);
                } elseif ($status < 200 || $status >= 300) {
                    log_message('error', '[FCM] send failed ({s}): {b}', ['s' => $status, 'b' => substr((string) $res->getBody(), 0, 300)]);
                }
            } catch (\Throwable $e) {
                log_message('error', '[FCM] send exception: ' . $e->getMessage());
            }
        }

        return $invalid;
    }

    /**
     * Mint a short-lived OAuth2 access token from the service account via the
     * JWT-bearer grant. Cached in-process for this request.
     */
    private function accessToken(): string
    {
        static $cached = null;
        static $expiry = 0;
        if ($cached !== null && $expiry > time() + 30) {
            return $cached;
        }

        $now    = time();
        $tokenUri = (string) ($this->account['token_uri'] ?? 'https://oauth2.googleapis.com/token');

        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss'   => $this->account['client_email'],
            'scope' => self::SCOPE,
            'aud'   => $tokenUri,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        $signingInput = $this->base64Url(json_encode($header)) . '.' . $this->base64Url(json_encode($claims));
        $signature    = '';
        if (! openssl_sign($signingInput, $signature, $this->account['private_key'], 'sha256WithRSAEncryption')) {
            throw new \RuntimeException('Failed to sign the FCM service-account JWT.');
        }
        $jwt = $signingInput . '.' . $this->base64Url($signature);

        $client = Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
        $res    = $client->post($tokenUri, [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);

        $body = json_decode((string) $res->getBody(), true) ?: [];
        if (empty($body['access_token'])) {
            throw new \RuntimeException('FCM token endpoint did not return an access token: ' . substr((string) $res->getBody(), 0, 300));
        }

        $cached = (string) $body['access_token'];
        $expiry = $now + (int) ($body['expires_in'] ?? 3600);

        return $cached;
    }

    private function base64Url(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
