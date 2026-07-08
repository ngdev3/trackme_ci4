<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('company', ['namespace' => 'Modules\Company\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('create', 'CompanyController::create');
    $routes->post('store', 'CompanyController::store');
    $routes->get('profile', 'CompanyController::profile');
    $routes->post('update', 'CompanyController::update');
    $routes->get('switch/(:num)', 'CompanyController::switchTo/$1');
});
