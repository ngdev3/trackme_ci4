<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('admin', ['namespace' => 'Modules\SuperAdmin\Controllers', 'filter' => 'superadmin'], static function (RouteCollection $routes) {
    $routes->get('/', 'SuperAdminController::dashboard');
    $routes->get('customers', 'SuperAdminController::customers');
    $routes->get('customers/toggle/(:num)', 'SuperAdminController::toggleCustomer/$1');
    $routes->post('customers/reset/(:num)', 'SuperAdminController::resetAccess/$1');
    $routes->post('customers/payment/(:num)', 'SuperAdminController::updatePayment/$1');
    $routes->get('impersonate/(:num)', 'SuperAdminController::impersonate/$1');
    $routes->get('firms', 'SuperAdminController::firms');
    $routes->get('firms/toggle/(:num)', 'SuperAdminController::toggleFirm/$1');
    $routes->get('plans', 'SuperAdminController::plans');
});
