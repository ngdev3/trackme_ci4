<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('', ['namespace' => 'Modules\Auth\Controllers'], static function (RouteCollection $routes) {
    // Guest-only
    $routes->get('login', 'AuthController::login', ['filter' => 'guest']);
    $routes->post('login', 'AuthController::attemptLogin', ['filter' => 'guest']);
    $routes->get('forgot-password', 'AuthController::forgotPassword', ['filter' => 'guest']);
    $routes->post('forgot-password', 'AuthController::sendResetLink', ['filter' => 'guest']);
    $routes->get('reset-password/(:segment)', 'AuthController::resetPassword/$1', ['filter' => 'guest']);
    $routes->post('reset-password', 'AuthController::updatePassword', ['filter' => 'guest']);

    // Authenticated
    $routes->get('logout', 'AuthController::logout', ['filter' => 'auth']);
});
