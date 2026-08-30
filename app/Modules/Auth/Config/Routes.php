<?php

/**
 * Auth module routes — admin web login. Preserves CI3 URLs.
 * Public (no adminAuth filter — this IS the login).
 */

use App\Modules\Auth\Controllers\Auth;

$routes->get('admin/auth', [Auth::class, 'index']);
$routes->get('admin/auth/login', [Auth::class, 'index']);   // GET shows the form
$routes->post('admin/auth/login', [Auth::class, 'login']);  // POST authenticates
$routes->get('admin/auth/logout', [Auth::class, 'logout']);
