<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('calculator', ['namespace' => 'Modules\Calculator\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'CalculatorController::index', ['filter' => 'permission:calculator,view']);
    $routes->post('save', 'CalculatorController::save', ['filter' => 'permission:calculator,add']);
    $routes->post('delete/(:num)', 'CalculatorController::delete/$1', ['filter' => 'permission:calculator,delete']);
});
