<?php

namespace App\Libraries\OAuth;

/**
 * Provider-agnostic user profile returned by every OAuth provider.
 * Downstream code (account find-or-create) only ever sees this shape, which is
 * what keeps the module easy to extend to new providers.
 */
final class OAuthUserProfile
{
    public function __construct(
        public readonly string $provider,       // 'google', 'apple', …
        public readonly string $providerId,     // stable provider user id (Google "sub")
        public readonly string $email,
        public readonly string $name,
        public readonly ?string $picture = null,
        public readonly bool $emailVerified = false,
    ) {
    }
}
