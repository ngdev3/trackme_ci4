<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('admin', ['namespace' => 'Modules\SuperAdmin\Controllers', 'filter' => 'superadmin'], static function (RouteCollection $routes) {
    $routes->get('/', 'SuperAdminController::dashboard');
    $routes->get('customers', 'SuperAdminController::customers');
    // AJAX fragment (table + pager) for the customers list — powers live
    // search / page-size / sort / pagination without a full page reload.
    $routes->get('customers/data', 'SuperAdminController::customersData');
    $routes->get('activate', 'SuperAdminController::activate');
    $routes->post('activate', 'SuperAdminController::activateSave');
    $routes->get('customers/toggle/(:num)', 'SuperAdminController::toggleCustomer/$1');
    $routes->post('customers/reset/(:num)', 'SuperAdminController::resetAccess/$1');
    $routes->post('customers/set-password/(:num)', 'SuperAdminController::setPassword/$1');
    $routes->post('customers/send-reset/(:num)', 'SuperAdminController::sendResetLink/$1');
    $routes->post('customers/payment/(:num)', 'SuperAdminController::updatePayment/$1');
    // Reversible soft-delete → Trash → Restore, plus the permanent purge (only
    // reachable from Trash). Deleting no longer destroys data by default.
    $routes->post('customers/delete/(:num)', 'SuperAdminController::softDeleteCustomer/$1');
    $routes->get('customers/trash', 'SuperAdminController::customersTrash');
    $routes->post('customers/restore/(:num)', 'SuperAdminController::restoreCustomer/$1');
    // Permanent, irreversible delete of a customer + every dependency (firms,
    // transactions, subscriptions, payments, logs, firm-users, …). Trash only.
    $routes->post('customers/purge/(:num)', 'SuperAdminController::purgeCustomer/$1');
    $routes->get('impersonate/(:num)', 'SuperAdminController::impersonate/$1');

    // Per-customer subscription oversight: current plan, activation / deactivation,
    // full payment chain + subscription activity log.
    $routes->get('customers/subscription/(:num)', 'SuperAdminController::customerSubscription/$1');
    $routes->post('customers/subscription/(:num)/activate', 'SuperAdminController::customerActivate/$1');
    $routes->post('customers/subscription/(:num)/deactivate', 'SuperAdminController::customerDeactivate/$1');
    $routes->post('customers/subscription/(:num)/set-expiry', 'SuperAdminController::setExpiry/$1');
    $routes->get('firms', 'SuperAdminController::firms');
    $routes->get('firms/toggle/(:num)', 'SuperAdminController::toggleFirm/$1');
    $routes->get('locations', 'SuperAdminController::locations');
    $routes->get('plans', 'SuperAdminController::plans');
    $routes->post('plans/save', 'SuperAdminController::planSave');
    $routes->post('plans/save/(:num)', 'SuperAdminController::planSave/$1');
    $routes->get('plans/toggle/(:num)', 'SuperAdminController::planToggle/$1');
    $routes->post('plans/delete/(:num)', 'SuperAdminController::planDelete/$1');
    $routes->post('plans/trial', 'SuperAdminController::saveTrial');

    // Coupons (discount + redeem codes).
    $routes->get('coupons', 'SuperAdminController::coupons');
    $routes->get('coupons/log', 'SuperAdminController::couponLog');
    $routes->post('coupons/save', 'SuperAdminController::couponSave');
    $routes->post('coupons/save/(:num)', 'SuperAdminController::couponSave/$1');
    $routes->get('coupons/toggle/(:num)', 'SuperAdminController::couponToggle/$1');
    $routes->post('coupons/delete/(:num)', 'SuperAdminController::couponDelete/$1');
    $routes->post('plans/payment', 'SuperAdminController::savePayment');
    $routes->post('plans/invoice', 'SuperAdminController::saveInvoice');

    // Public inquiry / contact-form submissions + two-way reply thread.
    $routes->get('inquiries', 'SuperAdminController::inquiries');
    $routes->get('inquiries/(:num)', 'SuperAdminController::inquiry/$1');
    $routes->post('inquiries/(:num)/reply', 'SuperAdminController::inquiryReply/$1');
    $routes->post('inquiries/status/(:num)', 'SuperAdminController::inquiryStatus/$1');

    // App usage analytics — which menus/screens users tap and when.
    $routes->get('app-events', 'SuperAdminController::appEvents');

    // Self-service account-deletion requests (raised from the app or web portal).
    // Approve = permanent purge (AccountPurgeService); reject keeps the account.
    $routes->get('deletion-requests', 'SuperAdminController::deletionRequests');
    $routes->post('deletion-requests/approve/(:num)', 'SuperAdminController::approveDeletion/$1');
    $routes->post('deletion-requests/reject/(:num)', 'SuperAdminController::rejectDeletion/$1');

    // Transactions / payments oversight.
    $routes->get('transactions', 'SuperAdminController::transactions');
    $routes->post('transactions/refund/(:num)', 'SuperAdminController::refundTransaction/$1');
    $routes->post('customers/cancel/(:num)', 'SuperAdminController::cancelSubscription/$1');
});
