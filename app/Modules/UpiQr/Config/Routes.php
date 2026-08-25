<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/**
 * UPI QR Codes — saved payee directory + QR generator (firm portal). Any
 * signed-in firm user can manage their company's payees; the QR itself is
 * rendered client-side from the saved details.
 */
$routes->group('upi-qr', ['namespace' => 'Modules\UpiQr\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'UpiQrController::index');
    $routes->post('save', 'UpiQrController::save');
    $routes->post('delete/(:num)', 'UpiQrController::delete/$1');
});
