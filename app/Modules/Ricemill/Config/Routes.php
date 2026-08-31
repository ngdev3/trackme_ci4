<?php

/**
 * Ricemill module routes — the public marketing website (CI3 default_controller).
 * Public: no adminAuth/rbac filters. Preserves the CI3 URLs.
 */

use App\Modules\Ricemill\Controllers\Ricemill;

$routes->get('ricemill', [Ricemill::class, 'index']);
$routes->post('ricemill/inquiry', [Ricemill::class, 'inquiry']);
$routes->get('ricemill/inquiry', [Ricemill::class, 'inquiry']); // GET falls back to redirect
