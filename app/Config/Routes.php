<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Root — send visitors to the dashboard (auth filter will bounce guests to login).
$routes->get('/', static function () {
    return redirect()->to(site_url('dashboard'));
});

/*
 * Module routes (Auth, Users, Roles, UserTypes, ModuleMaster, Permissions,
 * Dashboard, Logs) live in app/Modules/<Name>/Config/Routes.php and are
 * loaded automatically via CI4 module auto-discovery.
 */
