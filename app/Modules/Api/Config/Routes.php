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
    $routes->post('auth/forgot-password', 'AuthApiController::forgotPassword');
    $routes->post('auth/change-password', 'AuthApiController::changePassword');
    $routes->post('auth/logout', 'AuthApiController::logout');

    // Session context: user, companies, active company + role, package features,
    // and the module→actions permission map. Powers the app's menu/route gating.
    $routes->get('me', 'MeApiController::me');
    $routes->put('me', 'MeApiController::updateProfile');
    $routes->post('company/switch', 'MeApiController::switchCompany');

    // Firm (company) management for the mobile app. Create mirrors web store();
    // delete is a soft-delete (Trash), with owner-only restore / permanent purge.
    $routes->get('companies', 'CompanyApiController::index');
    $routes->post('companies', 'CompanyApiController::create');
    $routes->get('companies/trash', 'CompanyApiController::trash');
    $routes->post('companies/(:num)/restore', 'CompanyApiController::restore/$1');
    $routes->delete('companies/(:num)/purge', 'CompanyApiController::purge/$1');
    $routes->delete('companies/(:num)', 'CompanyApiController::destroy/$1');

    // Home dashboard aggregate: company + cash-book summaries + recent entries +
    // (feature-gated) inventory snapshot in one round-trip.
    $routes->get('dashboard', 'DashboardApiController::index');

    // Register a browser/device push subscription for the authenticated user.
    $routes->post('push/subscribe', 'PushApiController::subscribe');
    $routes->post('push/unsubscribe', 'PushApiController::unsubscribe');

    // Subscription / plan management for the mobile app.
    $routes->get('subscription', 'SubscriptionApiController::index');
    $routes->post('subscription/subscribe', 'SubscriptionApiController::subscribe');

    // Google Play Billing: verify an Android purchase (Bearer) and receive
    // Real-time Developer Notifications (public Pub/Sub push, gated by a secret).
    $routes->post('google-play/verify-purchase', 'GooglePlayApiController::verifyPurchase');
    $routes->post('google-play/rtdn/(:segment)', 'GooglePlayApiController::rtdn/$1');

    // Jama / Naam cash-book (Hisaab Kitaab Vahi) for the mobile app.
    $routes->get('transactions/list', 'TransactionApiController::list');
    $routes->get('transactions/report', 'TransactionApiController::report');
    $routes->get('transactions/entry/(:num)', 'TransactionApiController::entry/$1');
    $routes->post('transactions/store', 'TransactionApiController::store');
    $routes->post('transactions/update/(:num)', 'TransactionApiController::update/$1');
    $routes->post('transactions/delete/(:num)', 'TransactionApiController::delete/$1');

    // Password Manager (feature-gated: password_manager). Company-scoped vault.
    $routes->get('passwords', 'PasswordsApiController::index');
    $routes->get('passwords/(:num)/reveal', 'PasswordsApiController::reveal/$1');
    $routes->post('passwords', 'PasswordsApiController::create');
    $routes->put('passwords/(:num)', 'PasswordsApiController::update/$1');
    $routes->delete('passwords/(:num)', 'PasswordsApiController::delete/$1');

    // Notes (feature-gated: notes). Company-scoped, shared across members.
    $routes->get('notes', 'NotesApiController::index');
    $routes->post('notes', 'NotesApiController::create');
    $routes->put('notes/(:num)', 'NotesApiController::update/$1');
    $routes->post('notes/(:num)/pin', 'NotesApiController::togglePin/$1');
    $routes->delete('notes/(:num)', 'NotesApiController::delete/$1');

    // Reminders (feature-gated: reminder). Company-scoped.
    $routes->get('reminders', 'RemindersApiController::index');
    $routes->post('reminders', 'RemindersApiController::create');
    $routes->put('reminders/(:num)', 'RemindersApiController::update/$1');
    $routes->post('reminders/(:num)/complete', 'RemindersApiController::complete/$1');
    $routes->delete('reminders/(:num)', 'RemindersApiController::delete/$1');

    // Calendar (feature-gated: calendar). A month view over reminders.
    $routes->get('calendar', 'CalendarApiController::month');

    // Trash (feature-gated: trash). Soft-deleted rows across modules + restore.
    $routes->get('trash', 'TrashApiController::index');
    $routes->post('trash/restore', 'TrashApiController::restore');

    // Mandi Inventory (mobile app). Bearer-token auth; company resolved per user.
    $routes->get('inventory/masters', 'InventoryApiController::masters');
    $routes->get('inventory/stock', 'InventoryApiController::stock');
    $routes->get('inventory/search', 'InventoryApiController::search');
    $routes->post('inventory/voice/parse', 'InventoryApiController::voiceParse');
    $routes->post('inventory/inward', 'InventoryApiController::inward');
    $routes->post('inventory/outward', 'InventoryApiController::outward');
    $routes->post('inventory/verify', 'InventoryApiController::verify');
    $routes->get('inventory/entry/(:num)/attachments', 'InventoryApiController::entryAttachments/$1');
    $routes->post('inventory/entry/(:num)/attach', 'InventoryApiController::attachToEntry/$1');
    $routes->get('inventory/dashboard', 'InventoryApiController::dashboard');
    $routes->get('inventory/reports/(:alpha)', 'InventoryApiController::report/$1');
    $routes->get('inventory/closing', 'InventoryApiController::closingSummary');
    $routes->post('inventory/closing/close', 'InventoryApiController::closeDay');
    $routes->post('inventory/closing/reopen', 'InventoryApiController::reopenDay');
    $routes->get('inventory/corrections', 'InventoryApiController::corrections');
    $routes->post('inventory/corrections/approve/(:num)', 'InventoryApiController::approveCorrection/$1');

    // Inventory master CRUD (products / godowns / parties) for the mobile app.
    $routes->post('inventory/products', 'InvMasterApiController::createProduct');
    $routes->put('inventory/products/(:num)', 'InvMasterApiController::updateProduct/$1');
    $routes->delete('inventory/products/(:num)', 'InvMasterApiController::deleteProduct/$1');
    $routes->post('inventory/warehouses', 'InvMasterApiController::createWarehouse');
    $routes->put('inventory/warehouses/(:num)', 'InvMasterApiController::updateWarehouse/$1');
    $routes->delete('inventory/warehouses/(:num)', 'InvMasterApiController::deleteWarehouse/$1');
    $routes->post('inventory/parties', 'InvMasterApiController::createParty');
    $routes->put('inventory/parties/(:num)', 'InvMasterApiController::updateParty/$1');
    $routes->delete('inventory/parties/(:num)', 'InvMasterApiController::deleteParty/$1');
});
