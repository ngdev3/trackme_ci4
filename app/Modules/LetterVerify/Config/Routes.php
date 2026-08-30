<?php

/**
 * LetterVerify module routes — auto-discovered. Preserves the exact CI3 public
 * URLs (config/routes.php mapped letter_verify/check|api/<letter_no>/<token>).
 * Public by design — no auth filter.
 */

use App\Modules\LetterVerify\Controllers\LetterVerify;

$routes->get('letter_verify', [LetterVerify::class, 'index']);
$routes->get('letter_verify/check/(:segment)/(:segment)', [LetterVerify::class, 'check']);
$routes->get('letter_verify/check/(:segment)', [LetterVerify::class, 'check']);
$routes->get('letter_verify/api/(:segment)/(:segment)', [LetterVerify::class, 'api']);
