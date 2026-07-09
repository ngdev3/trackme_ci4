<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('accounting', ['namespace' => 'Modules\Accounting\Controllers', 'filter' => 'firmperm:accounting'], static function (RouteCollection $routes) {
    // Overview + groups
    $routes->get('/', 'AccountingController::index');
    $routes->post('groups/store', 'AccountingController::storeGroup');
    $routes->post('groups/delete/(:num)', 'AccountingController::deleteGroup/$1');

    // Ledgers
    $routes->get('ledgers', 'LedgerController::index');
    $routes->get('ledgers/create', 'LedgerController::create');
    $routes->post('ledgers/store', 'LedgerController::store');
    $routes->get('ledgers/edit/(:num)', 'LedgerController::edit/$1');
    $routes->post('ledgers/update/(:num)', 'LedgerController::update/$1');
    $routes->post('ledgers/delete/(:num)', 'LedgerController::delete/$1');
    $routes->get('ledgers/statement/(:num)', 'LedgerController::statement/$1');

    // Vouchers (day book)
    $routes->get('vouchers', 'VoucherController::index');
    $routes->get('vouchers/create', 'VoucherController::create');
    $routes->post('vouchers/store', 'VoucherController::store');
    $routes->get('vouchers/view/(:num)', 'VoucherController::view/$1');
    $routes->post('vouchers/delete/(:num)', 'VoucherController::delete/$1');
});
