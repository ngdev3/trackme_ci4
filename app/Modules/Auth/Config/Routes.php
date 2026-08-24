<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('', ['namespace' => 'Modules\Auth\Controllers'], static function (RouteCollection $routes) {
    // Guest-only
    $routes->get('login', 'AuthController::login', ['filter' => 'guest']);
    $routes->post('login', 'AuthController::attemptLogin', ['filter' => 'guest']);
    // Self-service signup: creates a pending account and emails a one-click
    // activation link (handled by the existing activate/{token} route below).
    $routes->post('register', 'AuthController::register', ['filter' => 'guest']);
    $routes->get('forgot-password', 'AuthController::forgotPassword', ['filter' => 'guest']);
    $routes->post('forgot-password', 'AuthController::sendResetLink', ['filter' => 'guest']);
    $routes->get('reset-password/(:segment)', 'AuthController::resetPassword/$1', ['filter' => 'guest']);
    $routes->post('reset-password', 'AuthController::updatePassword', ['filter' => 'guest']);
    // One-click account activation from the signup email link.
    $routes->get('activate/(:segment)', 'AuthController::activate/$1', ['filter' => 'guest']);
    // Re-send the email-validation link (for a signed-in, not-yet-verified user).
    $routes->post('verify-email/resend', 'AuthController::resendVerification', ['filter' => 'auth']);

    // Social sign-in (OAuth 2.0) — {provider} = google, and future apple/github/…
    // Callback serves BOTH the guest login flow and the authenticated link flow;
    // it validates the anti-CSRF state itself, so no guest/auth filter is applied.
    $routes->get('auth/(:segment)/callback', 'SocialAuthController::callback/$1');
    $routes->get('auth/(:segment)', 'SocialAuthController::redirect/$1', ['filter' => 'guest']);

    // Authenticated
    $routes->get('logout', 'AuthController::logout', ['filter' => 'auth']);
    // Return from Super Admin impersonation (reachable while impersonating any user).
    $routes->get('impersonate/stop', 'AuthController::stopImpersonating', ['filter' => 'auth']);
    // Forced / self-service password change (the mustchange filter lets these through).
    $routes->get('account/change-password', 'AuthController::changePassword', ['filter' => 'auth']);
    $routes->post('account/change-password', 'AuthController::updateForcedPassword', ['filter' => 'auth']);
    // Connect / disconnect a social account from the profile page.
    $routes->get('account/link/(:segment)', 'SocialAuthController::link/$1', ['filter' => 'auth']);
    $routes->post('account/unlink/(:segment)', 'SocialAuthController::unlink/$1', ['filter' => 'auth']);
});
