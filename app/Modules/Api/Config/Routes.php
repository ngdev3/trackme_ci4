<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
 * Stateless JSON API. CSRF is disabled for api/* (see Config\Filters), and
 * these routes authenticate with a bearer token rather than the web session.
 */
$routes->group('api/v1', ['namespace' => 'Modules\Api\Controllers'], static function (RouteCollection $routes) {
    $routes->post('auth/login', 'AuthApiController::login');
    $routes->post('auth/forgot-password', 'AuthApiController::forgotPassword');
    $routes->post('auth/change-password', 'AuthApiController::changePassword');
    $routes->post('auth/logout', 'AuthApiController::logout');

    // Register a browser/device push subscription for the authenticated user.
    $routes->post('push/subscribe', 'PushApiController::subscribe');
    $routes->post('push/unsubscribe', 'PushApiController::unsubscribe');

    // Mandi Inventory (mobile app). Bearer-token auth; company resolved per user.
    $routes->get('inventory/masters', 'InventoryApiController::masters');
    $routes->get('inventory/stock', 'InventoryApiController::stock');
    $routes->post('inventory/inward', 'InventoryApiController::inward');
});
