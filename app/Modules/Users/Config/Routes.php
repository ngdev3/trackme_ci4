<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('users', ['namespace' => 'Modules\Users\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'UserController::index', ['filter' => 'permission:users,view']);
    $routes->get('create', 'UserController::create', ['filter' => 'permission:users,add']);
    $routes->post('store', 'UserController::store', ['filter' => 'permission:users,add']);
    $routes->get('edit/(:num)', 'UserController::edit/$1', ['filter' => 'permission:users,edit']);
    $routes->post('update/(:num)', 'UserController::update/$1', ['filter' => 'permission:users,edit']);
    $routes->post('delete/(:num)', 'UserController::delete/$1', ['filter' => 'permission:users,delete']);
    $routes->get('toggle/(:num)', 'UserController::toggleStatus/$1', ['filter' => 'permission:users,edit']);
});
