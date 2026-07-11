<?php

namespace App\Libraries\OAuth;

/**
 * Google OAuth 2.0 / OpenID Connect provider (Authorization Code flow).
 *
 * Flow:
 *   1. getAuthorizationUrl() sends the user to Google's consent screen.
 *   2. Google redirects back with ?code=… to our callback.
 *   3. fetchUser() exchanges the code for tokens server-side (using the client
 *      secret), then validates the ID token claims and returns the profile.
 *
 * @see https://developers.google.com/identity/openid-connect/openid-connect
 */
class GoogleProvider extends AbstractProvider
{
    private const AUTH_URL      = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL     = 'https://oauth2.googleapis.com/token';
    private const TOKENINFO_URL = 'https://oauth2.googleapis.com/tokeninfo';

    private const VALID_ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    public function key(): string
    {
        return 'google';
    }

    public function label(): string
    {
        return 'Google';
    }

    public function getAuthorizationUrl(string $state): string
    {
        return self::AUTH_URL . '?' . $this->buildQuery([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'state'         => $state,
            'access_type'   => 'online',
            'prompt'        => 'select_account',
        ]);
    }

    public function fetchUser(string $code): OAuthUserProfile
    {
        $token = $this->postForm(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri'  => $this->redirectUri,
            'grant_type'    => 'authorization_code',
        ]);

        if (empty($token['id_token'])) {
            throw new OAuthException('Google did not return an identity token. Please try again.');
        }

        $claims = $this->decodeJwtClaims((string) $token['id_token']);
        $this->assertValidClaims($claims);

        return new OAuthUserProfile(
            provider: 'google',
            providerId: (string) $claims['sub'],
            email: (string) ($claims['email'] ?? ''),
            name: (string) ($claims['name'] ?? ($claims['email'] ?? 'Google User')),
            picture: isset($claims['picture']) ? (string) $claims['picture'] : null,
            emailVerified: ! empty($claims['email_verified']) && $claims['email_verified'] !== 'false',
        );
    }

    /**
     * Verify a Google ID token supplied by an UNTRUSTED client (the mobile app's
     * native / GIS sign-in) and return the profile.
     *
     * Unlike fetchUser() — where the token comes to us directly from Google over
     * TLS and its signature is implicitly trusted — a client-supplied token could
     * be forged, so its SIGNATURE must be checked. We hand it to Google's
     * tokeninfo endpoint, which validates the signature and freshness and returns
     * the decoded claims; we then enforce issuer / audience / expiry ourselves.
     *
     * @throws OAuthException
     */
    public function verifyIdToken(string $idToken): OAuthUserProfile
    {
        $idToken = trim($idToken);
        if ($idToken === '') {
            throw new OAuthException('No Google identity token was provided.');
        }

        $claims = $this->getJson(self::TOKENINFO_URL, ['id_token' => $idToken]);
        $this->assertValidClaims($claims);

        return new OAuthUserProfile(
            provider: 'google',
            providerId: (string) $claims['sub'],
            email: (string) ($claims['email'] ?? ''),
            name: (string) ($claims['name'] ?? ($claims['email'] ?? 'Google User')),
            picture: isset($claims['picture']) ? (string) $claims['picture'] : null,
            emailVerified: ! empty($claims['email_verified']) && $claims['email_verified'] !== 'false',
        );
    }

    /**
     * Enforce the security-critical ID token claims: issuer, audience (one of our
     * accepted client ids), and expiry. This is what prevents accepting a token
     * minted for a different app or a replayed/expired one.
     *
     * @throws OAuthException
     */
    private function assertValidClaims(array $claims): void
    {
        if (empty($claims['sub'])) {
            throw new OAuthException('Google identity token is missing the account id.');
        }
        if (! in_array($claims['iss'] ?? '', self::VALID_ISSUERS, true)) {
            throw new OAuthException('Google identity token has an unexpected issuer.');
        }
        if (! in_array((string) ($claims['aud'] ?? ''), $this->allowedAudiences, true)) {
            throw new OAuthException('Google identity token was issued for a different application.');
        }
        if (! isset($claims['exp']) || (int) $claims['exp'] < time()) {
            throw new OAuthException('Google identity token has expired. Please sign in again.');
        }
    }
}
