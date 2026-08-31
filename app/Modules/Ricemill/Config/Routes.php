<?php

use CodeIgniter\Router\RouteCollection;
use App\Modules\Ricemill\Controllers\Ricemill;

/** @var RouteCollection $routes */
// Public marketing website (no adminAuth filter). Preserves CI3 URLs.
$routes->get('ricemill', [Ricemill::class, 'index']);
$routes->post('ricemill/inquiry', [Ricemill::class, 'inquiry']);
$routes->get('ricemill/inquiry', [Ricemill::class, 'index']); // GET → back to site
