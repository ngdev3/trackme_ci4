<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('passwords', ['namespace' => 'Modules\Passwords\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'PasswordController::index', ['filter' => 'permission:passwords,view']);
    $routes->get('view/(:num)', 'PasswordController::view/$1', ['filter' => 'permission:passwords,view']);
    $routes->get('reveal/(:num)', 'PasswordController::reveal/$1', ['filter' => 'permission:passwords,view']);

    $routes->get('create', 'PasswordController::create', ['filter' => 'permission:passwords,add']);
    $routes->post('store', 'PasswordController::store', ['filter' => 'permission:passwords,add']);

    $routes->get('edit/(:num)', 'PasswordController::edit/$1', ['filter' => 'permission:passwords,edit']);
    $routes->post('update/(:num)', 'PasswordController::update/$1', ['filter' => 'permission:passwords,edit']);

    $routes->post('delete/(:num)', 'PasswordController::delete/$1', ['filter' => 'permission:passwords,delete']);
});
