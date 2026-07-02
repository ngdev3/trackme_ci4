<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('permissions', ['namespace' => 'Modules\Permissions\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'PermissionController::index', ['filter' => 'permission:permissions,view']);
    $routes->get('matrix/(:num)', 'PermissionController::matrix/$1', ['filter' => 'permission:permissions,view']);
    $routes->post('save/(:num)', 'PermissionController::save/$1', ['filter' => 'permission:permissions,edit']);
});
