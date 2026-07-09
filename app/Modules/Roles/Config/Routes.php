<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('roles', ['namespace' => 'Modules\Roles\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'RoleController::index', ['filter' => 'superadmin']);
    $routes->get('create', 'RoleController::create', ['filter' => 'superadmin']);
    $routes->post('store', 'RoleController::store', ['filter' => 'superadmin']);
    $routes->get('edit/(:num)', 'RoleController::edit/$1', ['filter' => 'superadmin']);
    $routes->post('update/(:num)', 'RoleController::update/$1', ['filter' => 'superadmin']);
    $routes->post('delete/(:num)', 'RoleController::delete/$1', ['filter' => 'superadmin']);
    $routes->get('toggle/(:num)', 'RoleController::toggleStatus/$1', ['filter' => 'superadmin']);
});
