<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('', ['namespace' => 'Modules\Logs\Controllers'], static function (RouteCollection $routes) {
    $routes->get('activity-logs', 'LogController::activity', ['filter' => 'permission:activity_logs,view']);
    $routes->get('login-logs', 'LogController::logins', ['filter' => 'permission:login_logs,view']);
    $routes->get('login-logs/export/(:segment)', 'LogController::exportLogins/$1', ['filter' => 'permission:login_logs,export']);
    $routes->get('my-login-history', 'LogController::myLogins', ['filter' => 'auth']);
    $routes->get('my-login-history/export/(:segment)', 'LogController::exportMyLogins/$1', ['filter' => 'auth']);
});
