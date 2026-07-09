<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('passwords', ['namespace' => 'Modules\Passwords\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'PasswordController::index', ['filter' => 'permission:passwords,view']);
    $routes->get('list', 'PasswordController::index', ['filter' => 'permission:passwords,view']);
    $routes->get('view/(:segment)', 'PasswordController::view/$1', ['filter' => 'permission:passwords,view']);
    $routes->get('reveal/(:segment)', 'PasswordController::reveal/$1', ['filter' => 'permission:passwords,view']);

    $routes->get('add', 'PasswordController::create', ['filter' => 'permission:passwords,add']);
    $routes->get('create', 'PasswordController::create', ['filter' => 'permission:passwords,add']);
    $routes->post('store', 'PasswordController::store', ['filter' => 'permission:passwords,add']);

    $routes->get('edit/(:segment)', 'PasswordController::edit/$1', ['filter' => 'permission:passwords,edit']);
    $routes->post('update/(:segment)', 'PasswordController::update/$1', ['filter' => 'permission:passwords,edit']);

    $routes->post('delete/(:segment)', 'PasswordController::delete/$1', ['filter' => 'permission:passwords,delete']);
});
