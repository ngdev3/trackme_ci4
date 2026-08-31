<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// Root → the public rice-mill marketing website (same as the CI3 default
// controller `ricemill`). Admin login is reached via the site's Admin Login link.
$routes->get('/', '\App\Modules\Ricemill\Controllers\Ricemill::index');

// --- P0 foundation self-test (remove before go-live) ---------------------
$routes->get('health', 'Health::index');
$routes->get('health/json', 'Health::json');

// Graceful 404: unported admin/master links render a "being migrated" page in
// the shell (see App\Modules\Admin\Controllers\Fallback); other 404s are normal.
$routes->set404Override('\App\Modules\Admin\Controllers\Fallback::index');

// --- Module routes are auto-discovered from app/Modules/<Name>/Config/Routes.php
//     (Config\Modules discovery is enabled). As each business module is ported,
//     its own Routes.php registers the SAME public URLs the CI3 app used, e.g.:
//       $routes->group('admin', ['filter' => ['adminAuth','fyContext','rbac']], static function($routes){
//           $routes->get('invoice/listing', '\App\Modules\Admin\Controllers\Invoice::listing');
//       });
