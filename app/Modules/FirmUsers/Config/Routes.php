<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('firm-users', ['namespace' => 'Modules\FirmUsers\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'FirmUserController::index');
    $routes->get('create', 'FirmUserController::create');
    $routes->post('store', 'FirmUserController::store');
    $routes->get('edit/(:num)', 'FirmUserController::edit/$1');
    $routes->post('update/(:num)', 'FirmUserController::update/$1');
    $routes->post('delete/(:num)', 'FirmUserController::delete/$1');
});
