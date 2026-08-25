<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Google Play Billing / Developer API configuration. Credentials are read from
 * the environment — never commit the service-account key. Add to your `.env`:
 *
 *   googleplay.enabled            = true
 *   googleplay.packageName        = 'com.crind.hissabkitaab'
 *   # Path to the GCP service-account JSON key (with Android Publisher access),
 *   # relative to the project root or absolute:
 *   googleplay.serviceAccountPath = 'writable/keys/play-service-account.json'
 *   # Shared secret appended to the RTDN webhook URL so only Google's push can reach it:
 *   googleplay.rtdnSecret         = 'a-long-random-string'
 *   # Optional: override the product-id -> plan-code map (JSON). Defaults to the
 *   # product id BEING the plan code (yearly_299 / yearly_399 / yearly_499 / yearly_599).
 *   googleplay.productMap         = '{"basic_yearly":"yearly_299"}'
 *
 * Until enabled + a readable key is present, verification is inert: the API
 * endpoints exist but return a clear "billing not configured" error.
 */
class GooglePlay extends BaseConfig
{
    public bool $enabled = false;

    public string $packageName = 'com.crind.hissabkitaab';

    /** Path to the service-account JSON key (absolute, or relative to ROOTPATH). */
    public string $serviceAccountPath = '';

    /** Shared secret gating the public RTDN webhook. */
    public string $rtdnSecret = '';

    /**
     * Trust the client's Play purchase WITHOUT a server-side Google check, and
     * grant the plan immediately. Use only for testing / early rollout before the
     * service-account verification is set up — it trusts the app, so a crafted
     * request could self-grant a plan. Turn OFF once a service account is live.
     *   googleplay.trustClient = true
     */
    public bool $trustClient = false;

    /**
     * Play subscription product id → our plan code. Empty means "the product id
     * is the plan code" (the default the plan seeds already use).
     *
     * @var array<string,string>
     */
    public array $productMap = [];

    public function __construct()
    {
        parent::__construct();

        $this->enabled            = filter_var(env('googleplay.enabled', $this->enabled), FILTER_VALIDATE_BOOL);
        $this->packageName        = (string) env('googleplay.packageName', $this->packageName);
        $this->serviceAccountPath = (string) env('googleplay.serviceAccountPath', '');
        $this->rtdnSecret         = (string) env('googleplay.rtdnSecret', '');
        $this->trustClient        = filter_var(env('googleplay.trustClient', $this->trustClient), FILTER_VALIDATE_BOOL);

        $map = (string) env('googleplay.productMap', '');
        if ($map !== '') {
            $decoded = json_decode($map, true);
            if (is_array($decoded)) {
                $this->productMap = $decoded;
            }
        }
    }

    /** Absolute path to the service-account key, or '' if not set. */
    public function keyPath(): string
    {
        if ($this->serviceAccountPath === '') {
            return '';
        }
        $path = $this->serviceAccountPath;
        if (! preg_match('#^([a-zA-Z]:[\\\\/]|/)#', $path)) {
            $path = rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . $path;
        }
        return $path;
    }

    /** True when billing is switched on and the key file is readable. */
    public function isConfigured(): bool
    {
        return $this->enabled && $this->keyPath() !== '' && is_readable($this->keyPath());
    }

    /** Resolve a Play product id to our plan code (explicit map, else identity). */
    public function planCodeForProduct(string $productId): string
    {
        return $this->productMap[$productId] ?? $productId;
    }
}
