<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('', ['namespace' => 'Modules\Logs\Controllers'], static function (RouteCollection $routes) {
    $routes->get('activity-logs', 'LogController::activity', ['filter' => 'permission:activity_logs,view']);
    $routes->get('login-logs', 'LogController::logins', ['filter' => 'permission:login_logs,view']);
});
