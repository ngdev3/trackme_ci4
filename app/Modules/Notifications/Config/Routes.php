<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('notifications', ['namespace' => 'Modules\Notifications\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'NotificationController::index', ['filter' => 'permission:notifications,view']);
    $routes->post('mark-read', 'NotificationController::markRead', ['filter' => 'auth']);
    $routes->post('mark-all-read', 'NotificationController::markAllRead', ['filter' => 'auth']);
    $routes->post('delete', 'NotificationController::delete', ['filter' => 'auth']);
});

$routes->group('api/notifications', ['namespace' => 'Modules\Notifications\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'NotificationController::apiIndex');
    $routes->post('mark-read', 'NotificationController::apiMarkRead');
    $routes->post('mark-all-read', 'NotificationController::apiMarkAllRead');
    $routes->post('delete', 'NotificationController::apiDelete');
});
