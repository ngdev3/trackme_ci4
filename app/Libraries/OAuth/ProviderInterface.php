<?php

namespace App\Libraries\OAuth;

/**
 * Contract every social-login provider implements. Add a new provider
 * (Apple, Microsoft, Facebook, GitHub) by writing one class that fulfils this
 * interface and registering it in Config\OAuth — nothing else changes.
 */
interface ProviderInterface
{
    /** Provider slug used in routes and stored as users.auth_provider. */
    public function key(): string;

    /** Human label for buttons ("Google"). */
    public function label(): string;

    /**
     * The provider's consent-screen URL to redirect the user to.
     *
     * @param string $state Opaque anti-CSRF token echoed back to the callback.
     */
    public function getAuthorizationUrl(string $state): string;

    /**
     * Exchange the authorization code for tokens, verify them, and return the
     * normalized profile.
     *
     * @throws OAuthException on any network / token / verification failure.
     */
    public function fetchUser(string $code): OAuthUserProfile;
}
