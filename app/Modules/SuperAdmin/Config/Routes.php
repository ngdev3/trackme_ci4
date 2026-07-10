<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('admin', ['namespace' => 'Modules\SuperAdmin\Controllers', 'filter' => 'superadmin'], static function (RouteCollection $routes) {
    $routes->get('/', 'SuperAdminController::dashboard');
    $routes->get('customers', 'SuperAdminController::customers');
    $routes->get('activate', 'SuperAdminController::activate');
    $routes->post('activate', 'SuperAdminController::activateSave');
    $routes->get('customers/toggle/(:num)', 'SuperAdminController::toggleCustomer/$1');
    $routes->post('customers/reset/(:num)', 'SuperAdminController::resetAccess/$1');
    $routes->post('customers/payment/(:num)', 'SuperAdminController::updatePayment/$1');
    $routes->get('impersonate/(:num)', 'SuperAdminController::impersonate/$1');

    // Per-customer subscription oversight: current plan, activation / deactivation,
    // full payment chain + subscription activity log.
    $routes->get('customers/subscription/(:num)', 'SuperAdminController::customerSubscription/$1');
    $routes->post('customers/subscription/(:num)/activate', 'SuperAdminController::customerActivate/$1');
    $routes->post('customers/subscription/(:num)/deactivate', 'SuperAdminController::customerDeactivate/$1');
    $routes->get('firms', 'SuperAdminController::firms');
    $routes->get('firms/toggle/(:num)', 'SuperAdminController::toggleFirm/$1');
    $routes->get('plans', 'SuperAdminController::plans');
    $routes->post('plans/save', 'SuperAdminController::planSave');
    $routes->post('plans/save/(:num)', 'SuperAdminController::planSave/$1');
    $routes->get('plans/toggle/(:num)', 'SuperAdminController::planToggle/$1');
    $routes->post('plans/delete/(:num)', 'SuperAdminController::planDelete/$1');
    $routes->post('plans/trial', 'SuperAdminController::saveTrial');
    $routes->post('plans/payment', 'SuperAdminController::savePayment');
    $routes->post('plans/invoice', 'SuperAdminController::saveInvoice');

    // Transactions / payments oversight.
    $routes->get('transactions', 'SuperAdminController::transactions');
    $routes->post('transactions/refund/(:num)', 'SuperAdminController::refundTransaction/$1');
    $routes->post('customers/cancel/(:num)', 'SuperAdminController::cancelSubscription/$1');
});
