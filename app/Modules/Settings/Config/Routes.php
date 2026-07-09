<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('settings', ['namespace' => 'Modules\Settings\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'SettingsController::index', ['filter' => 'permission:settings,view']);
    $routes->post('save', 'SettingsController::save', ['filter' => 'permission:settings,edit']);
    $routes->post('menu', 'SettingsController::saveMenu', ['filter' => 'permission:settings,edit']);
    $routes->post('generate-vapid', 'SettingsController::generateVapid', ['filter' => 'permission:settings,edit']);
});
