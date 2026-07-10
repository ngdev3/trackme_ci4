<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Cashfree Payments (PG) configuration. Credentials are read from the
 * environment — never commit them. Add to your `.env`:
 *
 *   cashfree.env        = 'sandbox'   # or 'production'
 *   cashfree.appId      = 'TESTxxxxxxxx'
 *   cashfree.secretKey  = 'cfsk_ma_test_xxxxxxxx'
 *   cashfree.apiVersion = '2023-08-01'
 */
class Cashfree extends BaseConfig
{
    /** 'sandbox' (test) or 'production' (live). */
    public string $env = 'sandbox';

    public string $appId = '';
    public string $secretKey = '';

    /** Cashfree PG API version sent as the x-api-version header. */
    public string $apiVersion = '2023-08-01';

    public function __construct()
    {
        parent::__construct();

        $this->env        = (string) env('cashfree.env', $this->env);
        $this->appId      = (string) env('cashfree.appId', '');
        $this->secretKey  = (string) env('cashfree.secretKey', '');
        $this->apiVersion = (string) env('cashfree.apiVersion', $this->apiVersion);
    }

    /** Whether real credentials are present. */
    public function isConfigured(): bool
    {
        return $this->appId !== '' && $this->secretKey !== '';
    }

    /** REST base URL for the active environment. */
    public function baseUrl(): string
    {
        return $this->env === 'production'
            ? 'https://api.cashfree.com/pg'
            : 'https://sandbox.cashfree.com/pg';
    }

    /** The JS SDK mode ('sandbox' | 'production') for Cashfree.js v3. */
    public function jsMode(): string
    {
        return $this->env === 'production' ? 'production' : 'sandbox';
    }
}
