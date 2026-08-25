<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/**
 * Sales / Purchase invoices (firm portal). Mirrors the mobile Sale/Purchase
 * flow: a sale issues stock + posts a Jama entry, a purchase receives stock +
 * posts a Naam entry — sharing the same invoices / invoice_items / products /
 * stock_movements / transactions tables as the mobile app.
 */
$routes->group('invoices', ['namespace' => 'Modules\Invoices\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'InvoiceController::index');
    $routes->get('new/(:segment)', 'InvoiceController::create/$1');
    $routes->post('store', 'InvoiceController::store');
    $routes->get('view/(:num)', 'InvoiceController::show/$1');
});
