<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// Root — public marketing landing page for guests; signed-in users are sent to
// their dashboard by the controller.
$routes->get('/', 'LandingController::index');
$routes->get('home', 'LandingController::index');
$routes->get('terms', 'LandingController::terms');
$routes->get('refunds', 'LandingController::refunds');
$routes->get('about', 'LandingController::about');
$routes->get('careers', 'LandingController::careers');
$routes->get('contact', 'LandingController::contact');
// Public inquiry / contact form (AJAX). CSRF-protected by the global filter.
$routes->post('inquiry', 'LandingController::submitInquiry');

// Public privacy policy — no auth. Used as the Google Play "Privacy policy" URL
// and opened from the mobile app's Settings page.
$routes->get('privacy', static function () {
    return view('privacy');
});

// Browser Web-Push subscription (session-authenticated + CSRF).
$routes->post('push/subscribe', 'PushController::subscribe', ['filter' => 'auth']);
$routes->post('push/unsubscribe', 'PushController::unsubscribe', ['filter' => 'auth']);

// Subscription / upgrade page (where the feature gate sends restricted users).
$routes->get('subscription', 'SubscriptionController::index', ['filter' => 'auth']);
// Cashfree online payment: create order (AJAX) + gateway return URL.
$routes->post('subscription/pay/(:num)', 'SubscriptionController::pay/$1', ['filter' => 'auth']);
$routes->get('subscription/callback', 'SubscriptionController::callback', ['filter' => 'auth']);
// Cashfree server-to-server webhook — no auth/CSRF; guarded by HMAC signature.
$routes->post('subscription/webhook', 'SubscriptionController::webhook');
// Customer payment history + tax receipt (PDF).
$routes->get('subscription/transactions', 'SubscriptionController::transactions', ['filter' => 'auth']);
$routes->get('subscription/receipt/(:segment)', 'SubscriptionController::receipt/$1', ['filter' => 'auth']);

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
