<?php

/**
 * Auth module routes — admin web login. Preserves CI3 URLs.
 * Public (no adminAuth filter — this IS the login).
 */

use App\Modules\Auth\Controllers\Auth;

$routes->get('admin/auth', [Auth::class, 'index']);
$routes->get('admin/auth/login', [Auth::class, 'index']);   // GET shows the form
$routes->post('admin/auth/login', [Auth::class, 'login']);  // POST authenticates
$routes->get('admin/auth/forgot', [Auth::class, 'forgot']);   // GET shows the form
$routes->post('admin/auth/forgot', [Auth::class, 'forgot']);  // POST issues temp password
$routes->get('admin/auth/logout', [Auth::class, 'logout']);
$routes->post('admin/auth/renew_session', [Auth::class, 'renew_session']); // top-nav session meter
$routes->post('admin/auth/change_fy', [Auth::class, 'change_fy']);         // top-nav Change Firm switcher
$routes->post('admin/auth/unlock_web_lock', [Auth::class, 'unlock_web_lock']); // web-panel unlock
