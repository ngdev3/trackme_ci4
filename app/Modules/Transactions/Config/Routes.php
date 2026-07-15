<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('transactions', ['namespace' => 'Modules\Transactions\Controllers'], static function (RouteCollection $routes) {
    // Module home = the Rokad Parcha daily cash register. The searchable ledger
    // table lives at /transactions/list.
    $routes->get('/', 'ReportController::index', ['filter' => 'permission:transactions,view']);
    $routes->get('list', 'TransactionController::index', ['filter' => 'permission:transactions,view']);

    // Type-ahead account search for the entry forms (JSON).
    $routes->get('accounts/search', 'TransactionController::accountsSearch', ['filter' => 'permission:transactions,view']);

    // Account (party) statement — searchable per-account ledger + print
    $routes->get('statement', 'TransactionController::statement', ['filter' => 'permission:transactions,view']);
    $routes->get('statement/print', 'TransactionController::statementPrint', ['filter' => 'permission:transactions,view']);
    $routes->get('statement/pdf', 'TransactionController::statementPdf', ['filter' => 'permission:transactions,view']);

    $routes->get('create', 'TransactionController::create', ['filter' => 'permission:transactions,add']);
    // Menu shortcuts: open the add form pre-set to Jama or Naam.
    $routes->get('add/(jama|naam)', 'TransactionController::create/$1', ['filter' => 'permission:transactions,add']);
    $routes->post('store', 'TransactionController::store', ['filter' => 'permission:transactions,add']);
    // Inline "Add Entry +" on the Rokadh Parcha (AJAX, no page reload).
    $routes->post('quick-store', 'TransactionController::quickStore', ['filter' => 'permission:transactions,add']);
    $routes->get('view/(:segment)', 'TransactionController::view/$1', ['filter' => 'permission:transactions,view']);
    $routes->get('edit/(:segment)', 'TransactionController::edit/$1', ['filter' => 'permission:transactions,edit']);
    $routes->post('update/(:segment)', 'TransactionController::update/$1', ['filter' => 'permission:transactions,edit']);
    $routes->post('delete/(:segment)', 'TransactionController::delete/$1', ['filter' => 'permission:transactions,delete']);

    // Entry modal (view details + attachments) + per-entry reminder
    $routes->get('entry/(:segment)', 'TransactionController::entryModal/$1', ['filter' => 'permission:transactions,view']);
    $routes->post('reminder/(:segment)', 'TransactionController::setReminder/$1', ['filter' => 'permission:transactions,edit']);

    // Attachments
    $routes->post('attach/(:segment)', 'TransactionController::attach/$1', ['filter' => 'permission:transactions,edit']);
    $routes->get('file/(:segment)/download', 'TransactionController::download/$1', ['filter' => 'permission:transactions,view']);
    $routes->get('file/(:segment)/preview', 'TransactionController::preview/$1', ['filter' => 'permission:transactions,view']);
    $routes->post('file/(:segment)/replace', 'TransactionController::replaceAttachment/$1', ['filter' => 'permission:transactions,edit']);
    $routes->post('file/(:segment)/delete', 'TransactionController::deleteAttachment/$1', ['filter' => 'permission:transactions,delete']);

    // Reports (Rokad Parcha) + exports.
    $routes->get('report', 'ReportController::index', ['filter' => 'permission:transactions,view']);
    $routes->get('report/print', 'ReportController::printReport', ['filter' => 'permission:transactions,view']);
    $routes->get('report/deleted', 'ReportController::deleted', ['filter' => 'permission:transactions,view']);

    // Breakdown — Jama/Naam totals grouped by tag, party type and payment mode.
    $routes->get('report/breakdown', 'ReportController::breakdown', ['filter' => 'permission:transactions,view']);
    $routes->get('report/breakdown/print', 'ReportController::breakdownPrint', ['filter' => 'permission:transactions,view']);
    $routes->get('report/breakdown/export/(csv|xlsx|pdf)', 'ExportController::breakdown/$1', ['filter' => 'permission:transactions,export']);
    $routes->post('report/restore/(:segment)', 'ReportController::restore/$1', ['filter' => 'permission:transactions,edit']);
    $routes->post('report/force-delete/(:segment)', 'ReportController::forceDelete/$1', ['filter' => 'permission:transactions,delete']);
    $routes->post('report/force-delete-all', 'ReportController::forceDeleteAll', ['filter' => 'permission:transactions,delete']);

    // Shri Rokad Nagad — per-financial-year opening cash
    $routes->get('opening', 'ReportController::opening', ['filter' => 'permission:transactions,view']);
    $routes->post('opening', 'ReportController::saveOpening', ['filter' => 'permission:transactions,edit']);
    $routes->get('export/(csv|xlsx|pdf)', 'ExportController::ledger/$1', ['filter' => 'permission:transactions,export']);
    $routes->get('report/export/(csv|xlsx|pdf)', 'ExportController::report/$1', ['filter' => 'permission:transactions,export']);
});
