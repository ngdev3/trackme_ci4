<?php

namespace App\Libraries\OAuth;

use Config\Services;

/**
 * Shared plumbing for OAuth providers: credential storage, HTTPS calls via the
 * framework CURLRequest, and JWT (ID token) claim decoding. Concrete providers
 * supply only the endpoint URLs and how to map claims to an OAuthUserProfile.
 */
abstract class AbstractProvider implements ProviderInterface
{
    protected string $clientId;
    protected string $clientSecret;
    protected string $redirectUri;

    /**
     * Every OAuth client id whose ID tokens this app will accept. The web
     * clientId plus any native (Android/iOS) client ids, since a mobile-minted
     * token's `aud` is the platform client id, not the web one.
     *
     * @var list<string>
     */
    protected array $allowedAudiences = [];

    public function __construct(array $config)
    {
        $this->clientId     = (string) ($config['clientId'] ?? '');
        $this->clientSecret = (string) ($config['clientSecret'] ?? '');
        $this->redirectUri  = (string) ($config['redirectUri'] ?? '');

        $this->allowedAudiences = array_values(array_unique(array_filter(array_merge(
            [$this->clientId],
            array_map('strval', (array) ($config['allowedAudiences'] ?? [])),
            [(string) ($config['androidClientId'] ?? ''), (string) ($config['iosClientId'] ?? '')],
        ))));
    }

    /**
     * POST an application/x-www-form-urlencoded body and decode the JSON reply.
     *
     * @throws OAuthException on transport failure or a non-2xx response.
     */
    protected function postForm(string $url, array $params): array
    {
        try {
            $client   = Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
            $response = $client->post($url, [
                'form_params' => $params,
                'headers'     => ['Accept' => 'application/json'],
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[OAuth] HTTP error calling {url}: {msg}', ['url' => $url, 'msg' => $e->getMessage()]);
            throw new OAuthException('Could not reach the sign-in provider. Please check your connection and try again.');
        }

        $status = $response->getStatusCode();
        $body   = json_decode((string) $response->getBody(), true) ?: [];

        if ($status < 200 || $status >= 300) {
            $detail = $body['error_description'] ?? ($body['error'] ?? ('HTTP ' . $status));
            log_message('error', '[OAuth] token endpoint {url} failed: {detail}', ['url' => $url, 'detail' => $detail]);
            throw new OAuthException('Sign-in failed while contacting the provider. Please try again.');
        }

        return $body;
    }

    /**
     * Decode a JWT payload WITHOUT signature verification. Safe here because the
     * ID token is obtained directly from the provider's token endpoint over TLS
     * (server-to-server), so its integrity is already guaranteed by the channel.
     * Claim validation (iss/aud/exp) is still enforced by the caller.
     *
     * @throws OAuthException if the token is malformed.
     */
    protected function decodeJwtClaims(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new OAuthException('Received an invalid identity token from the provider.');
        }
        $payload = json_decode($this->base64UrlDecode($parts[1]), true);
        if (! is_array($payload)) {
            throw new OAuthException('Received an unreadable identity token from the provider.');
        }

        return $payload;
    }

    /**
     * GET a URL and decode the JSON reply. Used for provider endpoints that
     * verify a token server-side (e.g. Google's tokeninfo).
     *
     * @throws OAuthException on transport failure or a non-2xx response.
     */
    protected function getJson(string $url, array $query = []): array
    {
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . $this->buildQuery($query);
        }

        try {
            $client   = Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
            $response = $client->get($url, ['headers' => ['Accept' => 'application/json']]);
        } catch (\Throwable $e) {
            log_message('error', '[OAuth] HTTP error calling {url}: {msg}', ['url' => $url, 'msg' => $e->getMessage()]);
            throw new OAuthException('Could not reach the sign-in provider. Please check your connection and try again.');
        }

        $status = $response->getStatusCode();
        $body   = json_decode((string) $response->getBody(), true) ?: [];

        if ($status < 200 || $status >= 300) {
            $detail = $body['error_description'] ?? ($body['error'] ?? ('HTTP ' . $status));
            log_message('error', '[OAuth] endpoint {url} failed: {detail}', ['url' => $url, 'detail' => $detail]);
            throw new OAuthException('Sign-in failed while verifying your identity. Please try again.');
        }

        return $body;
    }

    protected function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'), true);
    }

    protected function buildQuery(array $params): string
    {
        return http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
}
