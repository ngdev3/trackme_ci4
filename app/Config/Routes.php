<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
// Root → the ERP. Authenticated users land on the dashboard; the adminAuth
// filter bounces guests to admin/auth/login. (Replaces the default CI4 welcome.)
$routes->get('/', static function () {
    return redirect()->to('admin/dashboard');
});

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
