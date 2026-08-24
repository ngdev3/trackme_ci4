<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
 * Stateless JSON API. CSRF is disabled for api/* (see Config\Filters), and
 * these routes authenticate with a bearer token rather than the web session.
 */
$routes->group('api/v1', ['namespace' => 'Modules\Api\Controllers'], static function (RouteCollection $routes) {
    $routes->post('auth/login', 'AuthApiController::login');
    $routes->post('auth/google', 'AuthApiController::google');
    $routes->post('auth/truecaller', 'AuthApiController::truecaller');
    $routes->post('auth/forgot-password', 'AuthApiController::forgotPassword');
    $routes->post('auth/change-password', 'AuthApiController::changePassword');
    // Self-service signup: create a pending account (emails an activation code).
    $routes->post('auth/register', 'AuthApiController::register');
    // Signup email verification: request a 6-digit code, then verify it.
    $routes->post('auth/request-email-otp', 'AuthApiController::requestEmailOtp');
    $routes->post('auth/verify-email-otp', 'AuthApiController::verifyEmailOtp');
    $routes->post('auth/logout', 'AuthApiController::logout');

    // App metadata — latest-release info for the in-app "App Update" screen.
    // Public (no token) so it works pre-login and on cold start.
    $routes->get('app/version', 'AppApiController::version');

    // Session context: user, companies, active company + role, package features,
    // and the module→actions permission map. Powers the app's menu/route gating.
    $routes->get('me', 'MeApiController::me');
    $routes->put('me', 'MeApiController::updateProfile');
    // Raise (file) an account-deletion request for a super admin to review.
    $routes->post('me/deletion-request', 'MeApiController::deletionRequest');
    // Support: ONE ongoing conversation per user. GET returns the whole thread
    // (with an unread flag); POST appends a message (raising a request or replying
    // — the same single thread either way), creating the conversation on first use.
    $routes->get('me/support', 'MeApiController::support');
    $routes->post('me/support', 'MeApiController::supportSend');
    $routes->post('company/switch', 'MeApiController::switchCompany');

    // Attach the device's precise GPS location to the current session's login
    // record (sent by the app after the user grants location access). Optional —
    // a coarse IP location is stored at login regardless.
    $routes->post('location', 'LocationApiController::update');

    // Firm (company) management for the mobile app. Create mirrors web store();
    // delete is a soft-delete (Trash), with owner-only restore / permanent purge.
    $routes->get('companies', 'CompanyApiController::index');
    $routes->post('companies', 'CompanyApiController::create');
    $routes->get('companies/trash', 'CompanyApiController::trash');
    $routes->get('companies/(:num)/summary', 'CompanyApiController::summary/$1');
    $routes->get('companies/(:num)', 'CompanyApiController::show/$1');
    $routes->put('companies/(:num)', 'CompanyApiController::update/$1');
    $routes->post('companies/(:num)/restore', 'CompanyApiController::restore/$1');
    $routes->delete('companies/(:num)/purge', 'CompanyApiController::purge/$1');
    $routes->delete('companies/(:num)', 'CompanyApiController::destroy/$1');

    // Home dashboard aggregate: company + cash-book summaries + recent entries +
    // (feature-gated) inventory snapshot in one round-trip.
    $routes->get('dashboard', 'DashboardApiController::index');

    // Activity & Audit Monitor overview (SUPER ADMIN ONLY): aggregates every
    // user's activity/logins/IPs. Recording is app-wide; viewing is gated.
    $routes->get('monitor/overview', 'MonitorApiController::overview');

    // Register a browser/device push subscription for the authenticated user.
    // subscribe accepts either a Web-Push payload or an FCM device token.
    $routes->post('push/subscribe', 'PushApiController::subscribe');
    $routes->post('push/unsubscribe', 'PushApiController::unsubscribe');

    // In-app notification feed (per user): list + mark read.
    $routes->get('notifications', 'NotificationApiController::index');
    $routes->post('notifications/read-all', 'NotificationApiController::markAllRead');
    $routes->post('notifications/(:num)/read', 'NotificationApiController::markRead/$1');

    // Subscription / plan management for the mobile app.
    $routes->get('subscription', 'SubscriptionApiController::index');
    $routes->get('subscription/payments', 'SubscriptionApiController::payments');
    $routes->post('subscription/subscribe', 'SubscriptionApiController::subscribe');
    // Coupons: validate/preview a code, and redeem a free-time code (works on
    // Android where Google Play controls pricing and discounts cannot apply).
    $routes->post('subscription/coupon', 'SubscriptionApiController::validateCoupon');
    $routes->post('subscription/redeem', 'SubscriptionApiController::redeem');

    // Google Play Billing: verify an Android purchase (Bearer) and receive
    // Real-time Developer Notifications (public Pub/Sub push, gated by a secret).
    $routes->post('google-play/verify-purchase', 'GooglePlayApiController::verifyPurchase');
    $routes->post('google-play/rtdn/(:segment)', 'GooglePlayApiController::rtdn/$1');

    // Jama / Naam cash-book (Hisaab Kitaab Vahi) for the mobile app.
    // Offline-first sync: incremental pull + batch push (mobile SyncManager).
    $routes->get('transactions/changes', 'TransactionApiController::changes');
    $routes->post('transactions/sync', 'TransactionApiController::sync');
    $routes->get('transactions/list', 'TransactionApiController::list');
    $routes->get('transactions/report', 'TransactionApiController::report');
    $routes->get('transactions/parties', 'TransactionApiController::parties');
    $routes->get('transactions/statement', 'TransactionApiController::statement');
    $routes->get('transactions/opening', 'TransactionApiController::opening');
    $routes->post('transactions/opening', 'TransactionApiController::saveOpening');
    $routes->get('transactions/entry/(:num)', 'TransactionApiController::entry/$1');
    $routes->post('transactions/store', 'TransactionApiController::store');
    $routes->post('transactions/update/(:num)', 'TransactionApiController::update/$1');
    $routes->post('transactions/delete/(:num)', 'TransactionApiController::delete/$1');
    $routes->get('transactions/deleted', 'TransactionApiController::deleted');
    $routes->post('transactions/restore/(:num)', 'TransactionApiController::restore/$1');
    $routes->post('transactions/purge/(:num)', 'TransactionApiController::purge/$1');
    // Transaction attachments (photos / PDFs / audio on an entry).
    $routes->get('transactions/entry/(:num)/attachments', 'TransactionApiController::entryAttachments/$1');
    $routes->post('transactions/entry/(:num)/attach', 'TransactionApiController::attachToEntry/$1');
    $routes->get('transactions/attachment/(:num)', 'TransactionApiController::attachment/$1');
    $routes->delete('transactions/attachment/(:num)', 'TransactionApiController::deleteAttachment/$1');

    // Account logs: the caller's own login history (read-only).
    $routes->get('logs/logins', 'LogsApiController::logins');

    // Calculator saved history (feature: calculator). Per-user.
    $routes->get('calculator', 'CalculatorApiController::index');
    $routes->post('calculator', 'CalculatorApiController::save');
    $routes->delete('calculator/(:num)', 'CalculatorApiController::delete/$1');

    // Password Manager (feature-gated: password_manager). Company-scoped vault.
    $routes->get('passwords', 'PasswordsApiController::index');
    $routes->get('passwords/(:num)/reveal', 'PasswordsApiController::reveal/$1');
    $routes->post('passwords', 'PasswordsApiController::create');
    $routes->put('passwords/(:num)', 'PasswordsApiController::update/$1');
    $routes->delete('passwords/(:num)', 'PasswordsApiController::delete/$1');

    // Notes (feature-gated: notes). Company-scoped, shared across members.
    $routes->get('notes', 'NotesApiController::index');
    $routes->get('notes/trash', 'NotesApiController::trash');
    $routes->get('notes/categories', 'NotesApiController::categories');
    $routes->post('notes/categories', 'NotesApiController::createCategory');
    $routes->delete('notes/categories/(:num)', 'NotesApiController::deleteCategory/$1');
    $routes->post('notes', 'NotesApiController::create');
    $routes->put('notes/(:num)', 'NotesApiController::update/$1');
    $routes->post('notes/(:num)/pin', 'NotesApiController::togglePin/$1');
    $routes->post('notes/(:num)/important', 'NotesApiController::toggleImportant/$1');
    $routes->post('notes/(:num)/restore', 'NotesApiController::restore/$1');
    $routes->delete('notes/(:num)/purge', 'NotesApiController::purge/$1');
    $routes->delete('notes/(:num)', 'NotesApiController::delete/$1');

    // Reminders (feature-gated: reminder). Company-scoped.
    $routes->get('reminders', 'RemindersApiController::index');
    $routes->get('reminders/due', 'RemindersApiController::due');
    $routes->post('reminders', 'RemindersApiController::create');
    $routes->put('reminders/(:num)', 'RemindersApiController::update/$1');
    $routes->post('reminders/(:num)/complete', 'RemindersApiController::complete/$1');
    $routes->post('reminders/(:num)/dismiss', 'RemindersApiController::dismiss/$1');
    $routes->post('reminders/(:num)/snooze', 'RemindersApiController::snooze/$1');
    $routes->delete('reminders/(:num)', 'RemindersApiController::delete/$1');

    // Calendar (feature-gated: calendar). A month view over reminders.
    $routes->get('calendar', 'CalendarApiController::month');

    // Trash (feature-gated: trash). Soft-deleted rows across modules + restore.
    $routes->get('trash', 'TrashApiController::index');
    $routes->post('trash/restore', 'TrashApiController::restore');
});
