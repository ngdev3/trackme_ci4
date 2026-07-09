<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('rokad', ['namespace' => 'Modules\Rokad\Controllers', 'filter' => 'firmperm:rokad'], static function (RouteCollection $routes) {
    $routes->get('/', 'RokadController::index');
    $routes->get('print', 'RokadController::printDay');
    $routes->post('store', 'RokadController::store');
    $routes->post('update/(:num)', 'RokadController::update/$1');
    $routes->post('delete/(:num)', 'RokadController::delete/$1');
    $routes->post('opening', 'RokadController::setOpening');
    $routes->post('carry-forward', 'RokadController::carryForward');
});
