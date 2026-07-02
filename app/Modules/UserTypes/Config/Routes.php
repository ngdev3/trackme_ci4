<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('user-types', ['namespace' => 'Modules\UserTypes\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'UserTypeController::index', ['filter' => 'permission:user_types,view']);
    $routes->get('create', 'UserTypeController::create', ['filter' => 'permission:user_types,add']);
    $routes->post('store', 'UserTypeController::store', ['filter' => 'permission:user_types,add']);
    $routes->get('edit/(:num)', 'UserTypeController::edit/$1', ['filter' => 'permission:user_types,edit']);
    $routes->post('update/(:num)', 'UserTypeController::update/$1', ['filter' => 'permission:user_types,edit']);
    $routes->post('delete/(:num)', 'UserTypeController::delete/$1', ['filter' => 'permission:user_types,delete']);
    $routes->get('toggle/(:num)', 'UserTypeController::toggleStatus/$1', ['filter' => 'permission:user_types,edit']);
});
