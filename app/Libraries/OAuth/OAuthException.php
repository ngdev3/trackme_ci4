<?php

namespace App\Libraries\OAuth;

use RuntimeException;

/**
 * Raised for any recoverable OAuth failure (cancelled login, invalid token,
 * network issue, misconfiguration). The message is safe to show to the user.
 */
class OAuthException extends RuntimeException
{
}
