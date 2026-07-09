<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
 * Simple Inventory — three easy screens only:
 *   /inventory            Daily stock IN (Purchase) / OUT (Sale)
 *   /inventory/products   Product master (quick add)
 *   /inventory/position   Stock position by day / month / year
 *
 * The earlier advanced screens (voice, verify, closing, dashboard, reports,
 * attachments) are retired from the web UI; their controllers remain on disk but
 * are no longer routed. The mobile REST API is unaffected.
 */
$routes->group('inventory', ['namespace' => 'Modules\Inventory\Controllers'], static function (RouteCollection $routes) {
    // Daily IN / OUT (home).
    $routes->get('/', 'StockController::index', ['filter' => 'permission:inventory,view']);
    $routes->post('save', 'StockController::save', ['filter' => 'permission:inventory,add']);

    // Product master.
    $routes->get('products', 'StockController::products', ['filter' => 'permission:inventory,view']);
    $routes->post('products', 'StockController::storeProduct', ['filter' => 'permission:inventory,add']);
    $routes->post('products/delete/(:num)', 'StockController::deleteProduct/$1', ['filter' => 'permission:inventory,delete']);

    // Stock position (day / month / year).
    $routes->get('position', 'StockController::position', ['filter' => 'permission:inventory,view']);
});
