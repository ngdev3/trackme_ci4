<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Help & Support is available to every signed-in user (no module permission).
$routes->group('help', ['namespace' => 'Modules\Help\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'HelpController::index');
    $routes->get('faq', 'HelpController::index'); // alias
});
