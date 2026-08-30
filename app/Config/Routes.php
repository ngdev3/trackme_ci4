<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */
$routes->get('/', 'Home::index');

// --- P0 foundation self-test (remove before go-live) ---------------------
$routes->get('health', 'Health::index');
$routes->get('health/json', 'Health::json');

// --- Module routes are auto-discovered from app/Modules/<Name>/Config/Routes.php
//     (Config\Modules discovery is enabled). As each business module is ported,
//     its own Routes.php registers the SAME public URLs the CI3 app used, e.g.:
//       $routes->group('admin', ['filter' => ['adminAuth','fyContext','rbac']], static function($routes){
//           $routes->get('invoice/listing', '\App\Modules\Admin\Controllers\Invoice::listing');
//       });
