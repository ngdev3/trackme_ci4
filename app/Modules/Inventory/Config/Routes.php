<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/*
 * Mandi Inventory.
 *
 * The worker home is the simple daily IN/OUT screen (StockController) — big,
 * fast, minimal typing. The fuller, task-by-task screens (detailed inward /
 * outward, search, voice, verify + corrections, proof attachments, daily
 * closing and the owner dashboard + reports) live alongside it and are reached
 * from the More / Owner menu. Every entry flows through InventoryService, so the
 * ledger and stock balance stay correct whichever screen is used.
 */
$routes->group('inventory', ['namespace' => 'Modules\Inventory\Controllers'], static function (RouteCollection $routes) {

    // ---- Simple daily entry (worker home) ----
    $routes->get('/', 'StockController::index', ['filter' => 'permission:inventory,view']);
    $routes->post('save', 'StockController::save', ['filter' => 'permission:inventory,add']);
    $routes->get('products', 'StockController::products', ['filter' => 'permission:inventory,view']);
    $routes->post('products', 'StockController::storeProduct', ['filter' => 'permission:inventory,add']);
    $routes->post('products/delete/(:num)', 'StockController::deleteProduct/$1', ['filter' => 'permission:inventory,delete']);
    $routes->get('position', 'StockController::position', ['filter' => 'permission:inventory,view']);

    // ---- Task 1 / 2 — detailed Inward / Outward ----
    $routes->get('inward', 'InventoryController::inward', ['filter' => 'permission:inventory,add']);
    $routes->post('inward', 'InventoryController::storeInward', ['filter' => 'permission:inventory,add']);
    $routes->get('outward', 'InventoryController::outward', ['filter' => 'permission:inventory,add']);
    $routes->post('outward', 'InventoryController::storeOutward', ['filter' => 'permission:inventory,add']);
    $routes->get('receipt/(:num)', 'InventoryController::receipt/$1', ['filter' => 'permission:inventory,view']);

    // ---- Task 3 — Search ----
    $routes->get('search', 'InventoryController::search', ['filter' => 'permission:inventory,view']);

    // ---- Task 5 — Voice entry ----
    $routes->get('voice', 'InventoryController::voice', ['filter' => 'permission:inventory,add']);
    $routes->post('voice/parse', 'InventoryController::voiceParse', ['filter' => 'permission:inventory,add']);

    // ---- Task 4 — Physical verification + corrections ----
    $routes->get('verify', 'InventoryController::verify', ['filter' => 'permission:inventory,add']);
    $routes->post('verify', 'InventoryController::storeVerification', ['filter' => 'permission:inventory,add']);
    $routes->get('corrections', 'InventoryController::corrections', ['filter' => 'permission:inventory,view']);
    $routes->post('corrections/approve/(:num)', 'InventoryController::approveCorrection/$1', ['filter' => 'permission:inventory,approve']);
    $routes->post('corrections/reject/(:num)', 'InventoryController::rejectCorrection/$1', ['filter' => 'permission:inventory,approve']);

    // ---- Task 6 — Entry detail + proof attachments ----
    $routes->get('entry/(:num)', 'InventoryController::entry/$1', ['filter' => 'permission:inventory,view']);
    $routes->post('entry/(:num)/attach', 'InventoryController::attachToEntry/$1', ['filter' => 'permission:inventory,add']);
    $routes->post('attachment/(:num)/delete', 'InventoryController::deleteAttachment/$1', ['filter' => 'permission:inventory,delete']);
    $routes->get('attachment/(:num)', 'InventoryController::attachment/$1', ['filter' => 'permission:inventory,view']);

    // ---- Detailed masters (products / godowns / parties) ----
    $routes->get('masters', 'InventoryController::masters', ['filter' => 'permission:inventory,edit']);
    $routes->post('masters/product', 'InventoryController::storeProduct', ['filter' => 'permission:inventory,edit']);
    $routes->post('masters/warehouse', 'InventoryController::storeWarehouse', ['filter' => 'permission:inventory,edit']);
    $routes->post('masters/party', 'InventoryController::storeParty', ['filter' => 'permission:inventory,edit']);
    $routes->post('masters/delete/(:alpha)/(:num)', 'InventoryController::deleteMaster/$1/$2', ['filter' => 'permission:inventory,delete']);

    // ---- Task 7 — Daily Closing ----
    $routes->get('closing', 'ClosingController::index', ['filter' => 'permission:inventory,view']);
    $routes->post('closing/close', 'ClosingController::close', ['filter' => 'permission:inventory,add']);
    $routes->post('closing/reopen', 'ClosingController::reopen', ['filter' => 'permission:inventory,approve']);
    $routes->get('closing/report/(:alpha)', 'ClosingController::report/$1', ['filter' => 'permission:inventory,export']);
    $routes->get('closing/print', 'ClosingController::printReport', ['filter' => 'permission:inventory,view']);

    // ---- Task 8 — Owner dashboard + reports ----
    $routes->get('dashboard', 'OwnerController::dashboard', ['filter' => 'permission:inventory,view']);
    $routes->get('reports', 'OwnerController::reports', ['filter' => 'permission:inventory,view']);
    $routes->get('reports/(:segment)/export/(:alpha)', 'OwnerController::export/$1/$2', ['filter' => 'permission:inventory,export']);
    $routes->get('reports/(:segment)/print', 'OwnerController::printReport/$1', ['filter' => 'permission:inventory,view']);
    $routes->get('reports/(:segment)', 'OwnerController::report/$1', ['filter' => 'permission:inventory,view']);
});
