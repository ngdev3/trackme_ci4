<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('modules', ['namespace' => 'Modules\ModuleMaster\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'ModuleController::index', ['filter' => 'permission:modules,view']);
    $routes->get('create', 'ModuleController::create', ['filter' => 'permission:modules,add']);
    $routes->post('store', 'ModuleController::store', ['filter' => 'permission:modules,add']);
    $routes->get('edit/(:num)', 'ModuleController::edit/$1', ['filter' => 'permission:modules,edit']);
    $routes->post('update/(:num)', 'ModuleController::update/$1', ['filter' => 'permission:modules,edit']);
    $routes->post('delete/(:num)', 'ModuleController::delete/$1', ['filter' => 'permission:modules,delete']);
    $routes->get('toggle/(:num)', 'ModuleController::toggleStatus/$1', ['filter' => 'permission:modules,edit']);
});
