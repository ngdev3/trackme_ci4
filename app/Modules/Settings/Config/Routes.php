<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('settings', ['namespace' => 'Modules\Settings\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'SettingsController::index', ['filter' => 'permission:settings,view']);
    $routes->post('save', 'SettingsController::save', ['filter' => 'permission:settings,edit']);
    $routes->post('appearance', 'SettingsController::saveAppearance', ['filter' => 'permission:settings,edit']);
    $routes->post('appearance/reset', 'SettingsController::resetAppearance', ['filter' => 'permission:settings,edit']);
});
