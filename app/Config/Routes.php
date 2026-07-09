<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Root — send visitors to the dashboard (auth filter will bounce guests to login).
$routes->get('/', static function () {
    return redirect()->to(site_url('dashboard'));
});

// Browser Web-Push subscription (session-authenticated + CSRF).
$routes->post('push/subscribe', 'PushController::subscribe', ['filter' => 'auth']);
$routes->post('push/unsubscribe', 'PushController::unsubscribe', ['filter' => 'auth']);

// Fresh CSRF token — lets JS refresh a form's token before an auto-submit so a
// long-open / stale page never fails CSRF validation.
$routes->get('csrf-token', static function () {
    return service('response')->setJSON(['token' => csrf_hash(), 'name' => csrf_token()]);
}, ['filter' => 'auth']);

/*
 * Module routes (Auth, Users, Roles, UserTypes, ModuleMaster, Permissions,
 * Dashboard, Logs) live in app/Modules/<Name>/Config/Routes.php and are
 * loaded automatically via CI4 module auto-discovery.
 */
