<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('roles', ['namespace' => 'Modules\Roles\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'RoleController::index', ['filter' => 'permission:roles,view']);
    $routes->get('create', 'RoleController::create', ['filter' => 'permission:roles,add']);
    $routes->post('store', 'RoleController::store', ['filter' => 'permission:roles,add']);
    $routes->get('edit/(:num)', 'RoleController::edit/$1', ['filter' => 'permission:roles,edit']);
    $routes->post('update/(:num)', 'RoleController::update/$1', ['filter' => 'permission:roles,edit']);
    $routes->post('delete/(:num)', 'RoleController::delete/$1', ['filter' => 'permission:roles,delete']);
    $routes->get('toggle/(:num)', 'RoleController::toggleStatus/$1', ['filter' => 'permission:roles,edit']);
});
