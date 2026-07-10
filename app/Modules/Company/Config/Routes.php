<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('company', ['namespace' => 'Modules\Company\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('create', 'CompanyController::create');
    $routes->post('store', 'CompanyController::store');
    $routes->post('quick-start', 'CompanyController::quickStart');
    $routes->get('profile', 'CompanyController::profile');
    $routes->post('update', 'CompanyController::update');
    $routes->get('switch/(:num)', 'CompanyController::switchTo/$1');

    // Company recycle bin: soft-delete → Trash → restore or permanently delete.
    $routes->post('delete/(:num)', 'CompanyController::deleteCompany/$1');
    $routes->get('trash', 'CompanyController::trash');
    $routes->post('restore/(:num)', 'CompanyController::restoreCompany/$1');
    $routes->post('force-delete/(:num)', 'CompanyController::forceDeleteCompany/$1');
});
