<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Truecaller OAuth (SDK v3) sign-in. The Android app obtains an authorization
 * code via the Truecaller app, and the server exchanges it (PKCE) for the
 * verified phone number. Everything is config-gated: until `clientId` is set the
 * endpoint returns a clean "not configured" error and no Truecaller call is made.
 *
 * Set in .env:
 *   truecaller.enabled  = true
 *   truecaller.clientId = <your Truecaller OAuth client id>
 *   truecaller.region   = noneu   ; 'noneu' (India/RoW) or 'eu'
 */
class Truecaller extends BaseConfig
{
    public bool $enabled = false;

    /** Truecaller OAuth client id (from the Truecaller developer console). */
    public string $clientId = '';

    /** Data region: 'noneu' (default, India/rest-of-world) or 'eu'. */
    public string $region = 'noneu';

    /** True once a client id is present and the feature is switched on. */
    public function isConfigured(): bool
    {
        return $this->enabled && $this->clientId !== '';
    }

    /** OAuth base host for the configured region. */
    public function oauthBase(): string
    {
        return strtolower($this->region) === 'eu'
            ? 'https://oauth-account-eu.truecaller.com'
            : 'https://oauth-account-noneu.truecaller.com';
    }
}
