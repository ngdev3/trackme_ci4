<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('profile', ['namespace' => 'Modules\Profile\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'ProfileController::index');
    $routes->post('update', 'ProfileController::update');
    $routes->post('avatar', 'ProfileController::uploadAvatar');
    $routes->post('avatar/remove', 'ProfileController::removeAvatar');
    $routes->post('password', 'ProfileController::changePassword');
});
