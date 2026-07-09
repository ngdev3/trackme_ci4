<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('permissions', ['namespace' => 'Modules\Permissions\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'PermissionController::index', ['filter' => 'superadmin']);
    $routes->get('matrix/(:num)', 'PermissionController::matrix/$1', ['filter' => 'superadmin']);
    $routes->post('save/(:num)', 'PermissionController::save/$1', ['filter' => 'superadmin']);
});
