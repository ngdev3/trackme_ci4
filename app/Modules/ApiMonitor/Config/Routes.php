<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
 * Mobile API Monitor — SUPER ADMIN ONLY. The entire group is gated by the
 * `superadmin` filter, so no customer / firm user (nor a regular admin role)
 * can reach it, and it is hidden from every non-super sidebar.
 */
$routes->group('api-monitor', ['namespace' => 'Modules\ApiMonitor\Controllers', 'filter' => 'superadmin'], static function (RouteCollection $routes) {
    $routes->get('/', 'ApiMonitorController::index');
    $routes->post('sync', 'ApiMonitorController::sync');
    $routes->post('check-all', 'ApiMonitorController::checkAll');
    $routes->post('check/(:num)', 'ApiMonitorController::check/$1');
    $routes->post('toggle/(:num)', 'ApiMonitorController::toggle/$1');
});
