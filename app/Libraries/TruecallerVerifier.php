<?php

namespace App\Libraries;

use Config\Services;

/**
 * Server-side verification of a Truecaller OAuth (SDK v3) sign-in. The Android
 * client returns an authorization code + PKCE code verifier; we exchange them
 * with Truecaller for an access token, then fetch the verified user profile
 * (phone number, name, email). The client is never trusted — the phone number
 * is only accepted after this round-trip to Truecaller succeeds.
 */
class TruecallerVerifier
{
    private \Config\Truecaller $cfg;

    public function __construct()
    {
        $this->cfg = config('Truecaller');
    }

    public function isConfigured(): bool
    {
        return $this->cfg->isConfigured();
    }

    /**
     * Exchange an authorization code for the verified Truecaller profile.
     *
     * @return array{ok:bool, error?:string, phone?:string, name?:string, email?:?string, raw?:array}
     */
    public function verify(string $authorizationCode, string $codeVerifier): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'error' => 'not_configured'];
        }
        if ($authorizationCode === '' || $codeVerifier === '') {
            return ['ok' => false, 'error' => 'missing_code'];
        }

        $base = $this->cfg->oauthBase();
        $curl = Services::curlrequest(['timeout' => 15, 'http_errors' => false]);

        // 1) Authorization code → access token (PKCE, no client secret).
        try {
            $tokenRes = $curl->post($base . '/v1/token', [
                'form_params' => [
                    'grant_type'    => 'authorization_code',
                    'client_id'     => $this->cfg->clientId,
                    'code'          => $authorizationCode,
                    'code_verifier' => $codeVerifier,
                ],
                'headers' => ['Accept' => 'application/json'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Truecaller] token request failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'network'];
        }

        $token = json_decode((string) $tokenRes->getBody(), true);
        $access = is_array($token) ? ($token['access_token'] ?? '') : '';
        if ($tokenRes->getStatusCode() >= 400 || $access === '') {
            log_message('error', '[Truecaller] token exchange rejected: ' . (string) $tokenRes->getBody());
            return ['ok' => false, 'error' => 'invalid_code'];
        }

        // 2) Access token → verified user profile.
        try {
            $infoRes = $curl->get($base . '/v1/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $access,
                    'Accept'        => 'application/json',
                ],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[Truecaller] userinfo request failed: ' . $e->getMessage());
            return ['ok' => false, 'error' => 'network'];
        }

        $info = json_decode((string) $infoRes->getBody(), true);
        if ($infoRes->getStatusCode() >= 400 || ! is_array($info)) {
            return ['ok' => false, 'error' => 'userinfo_failed'];
        }

        $phone = $this->normalizePhone((string) ($info['phone_number'] ?? ''));
        if ($phone === '') {
            return ['ok' => false, 'error' => 'no_phone'];
        }

        $name = trim(($info['given_name'] ?? '') . ' ' . ($info['family_name'] ?? ''));

        return [
            'ok'    => true,
            'phone' => $phone,
            'name'  => $name !== '' ? $name : null,
            'email' => ! empty($info['email']) ? (string) $info['email'] : null,
            'raw'   => $info,
        ];
    }

    /** Reduce a Truecaller number to the local 10-digit form the app stores. */
    private function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', $raw);
        // Strip a leading country code (India 91) → keep the last 10 digits.
        if (strlen($digits) > 10) {
            $digits = substr($digits, -10);
        }
        return strlen($digits) === 10 ? $digits : '';
    }
}
