<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('dashboard', ['namespace' => 'Modules\Dashboard\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'DashboardController::index', ['filter' => 'permission:dashboard,view']);
    // AJAX analytics feed (JSON) consumed by the dashboard widgets.
    $routes->get('analytics', 'DashboardController::analytics', ['filter' => 'permission:dashboard,view']);
});
