<?php

namespace App\Libraries\OAuth;

use Config\OAuth as OAuthConfig;

/**
 * Resolves configured OAuth providers by slug. This is the single seam the rest
 * of the app talks to, so adding a provider never touches controllers/routes.
 */
class OAuthManager
{
    private OAuthConfig $config;

    public function __construct(?OAuthConfig $config = null)
    {
        $this->config = $config ?? config('OAuth');
    }

    /**
     * Instantiate a usable provider, or throw if unknown / not configured.
     *
     * @throws OAuthException
     */
    public function provider(string $key): ProviderInterface
    {
        if (! isset($this->config->providers[$key])) {
            throw new OAuthException('Unknown sign-in provider.');
        }
        if (! $this->config->enabled($key)) {
            throw new OAuthException(ucfirst($key) . ' sign-in is not configured on this server.');
        }

        $settings = $this->config->providers[$key];
        $class    = $settings['class'];

        return new $class($settings);
    }

    /**
     * Provider slugs that are fully configured (for rendering buttons).
     *
     * @return list<string>
     */
    public function enabledProviders(): array
    {
        return array_values(array_filter(
            array_keys($this->config->providers),
            fn (string $key) => $this->config->enabled($key),
        ));
    }
}
