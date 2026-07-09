<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('', ['namespace' => 'Modules\Logs\Controllers'], static function (RouteCollection $routes) {
    $routes->get('activity-logs', 'LogController::activity', ['filter' => 'superadmin']);
    $routes->get('login-logs', 'LogController::logins', ['filter' => 'superadmin']);
    $routes->get('login-logs/export/(:segment)', 'LogController::exportLogins/$1', ['filter' => 'superadmin']);
    $routes->get('my-login-history', 'LogController::myLogins', ['filter' => 'auth']);
    $routes->get('my-login-history/export/(:segment)', 'LogController::exportMyLogins/$1', ['filter' => 'auth']);
});
