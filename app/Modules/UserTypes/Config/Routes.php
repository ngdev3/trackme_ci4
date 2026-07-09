<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('user-types', ['namespace' => 'Modules\UserTypes\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'UserTypeController::index', ['filter' => 'superadmin']);
    $routes->get('create', 'UserTypeController::create', ['filter' => 'superadmin']);
    $routes->post('store', 'UserTypeController::store', ['filter' => 'superadmin']);
    $routes->get('edit/(:num)', 'UserTypeController::edit/$1', ['filter' => 'superadmin']);
    $routes->post('update/(:num)', 'UserTypeController::update/$1', ['filter' => 'superadmin']);
    $routes->post('delete/(:num)', 'UserTypeController::delete/$1', ['filter' => 'superadmin']);
    $routes->get('toggle/(:num)', 'UserTypeController::toggleStatus/$1', ['filter' => 'superadmin']);
});
