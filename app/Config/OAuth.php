<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Social / OAuth 2.0 login configuration.
 *
 * Credentials are read from the environment (never commit secrets). Add these
 * to your `.env` to enable Google sign-in:
 *
 *   oauth.google.clientId     = 'xxxxxxxx.apps.googleusercontent.com'
 *   oauth.google.clientSecret = 'GOCSPX-xxxxxxxx'
 *   # redirectUri is optional — defaults to <baseURL>/auth/google/callback
 *   oauth.google.redirectUri  = 'https://your-domain/ERP/auth/google/callback'
 *
 * For the mobile app's native Google sign-in the ID token's audience is the
 * platform OAuth client id (Android / iOS), NOT the web clientId above. List
 * every client id that may sign in so the API accepts their tokens:
 *
 *   oauth.google.androidClientId = 'xxxx-android.apps.googleusercontent.com'
 *   oauth.google.iosClientId     = 'xxxx-ios.apps.googleusercontent.com'
 *   # or a comma-separated catch-all:
 *   oauth.google.allowedAudiences = 'id1.apps.googleusercontent.com,id2.apps.googleusercontent.com'
 *
 * To add another provider later (Apple, Microsoft, Facebook, GitHub), add a
 * key here and a matching Provider class under App\Libraries\OAuth.
 */
class OAuth extends BaseConfig
{
    /**
     * Per-provider settings, keyed by provider slug (used in the URL:
     * /auth/{provider} and /auth/{provider}/callback).
     *
     * @var array<string, array<string, mixed>>
     */
    public array $providers = [];

    public function __construct()
    {
        parent::__construct();

        $this->providers = [
            'google' => [
                'class'            => \App\Libraries\OAuth\GoogleProvider::class,
                'label'            => 'Google',
                'clientId'         => (string) env('oauth.google.clientId', ''),
                'clientSecret'     => (string) env('oauth.google.clientSecret', ''),
                'redirectUri'      => (string) env('oauth.google.redirectUri', site_url('auth/google/callback')),
                // Extra ID-token audiences accepted by the mobile API (native
                // sign-in mints tokens for the platform client id, not the web one).
                'androidClientId'  => (string) env('oauth.google.androidClientId', ''),
                'iosClientId'      => (string) env('oauth.google.iosClientId', ''),
                'allowedAudiences' => array_values(array_filter(array_map(
                    'trim',
                    explode(',', (string) env('oauth.google.allowedAudiences', ''))
                ))),
            ],
        ];
    }

    /**
     * Is a provider configured (has credentials) and thus usable?
     */
    public function enabled(string $provider): bool
    {
        $p = $this->providers[$provider] ?? null;

        return $p !== null && $p['clientId'] !== '' && $p['clientSecret'] !== '';
    }
}
