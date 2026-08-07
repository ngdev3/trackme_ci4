<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('push-notifications', ['namespace' => 'Modules\PushNotifications\Controllers', 'filter' => 'superadmin'], static function (RouteCollection $routes) {
    $routes->get('/', 'PushNotificationController::index');
    $routes->post('send', 'PushNotificationController::send');
});
