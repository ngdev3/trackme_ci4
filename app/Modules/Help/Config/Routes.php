<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Help & Support is available to every signed-in user (no module permission).
$routes->group('help', ['namespace' => 'Modules\Help\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'HelpController::index');
    $routes->get('faq', 'HelpController::index'); // alias
    // Support — ONE ongoing conversation per user (send a message = raise a
    // request or reply; it all stays in the single thread).
    $routes->get('support', 'HelpController::support');
    $routes->post('support/send', 'HelpController::supportSend');
});
