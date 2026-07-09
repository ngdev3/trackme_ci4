<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('inventory', ['namespace' => 'Modules\Inventory\Controllers'], static function (RouteCollection $routes) {
    // Worker hub (big buttons).
    $routes->get('/', 'InventoryController::index', ['filter' => 'permission:inventory,view']);

    // Task 1 — Stock Inward.
    $routes->get('inward', 'InventoryController::inward', ['filter' => 'permission:inventory,add']);
    $routes->post('inward', 'InventoryController::storeInward', ['filter' => 'permission:inventory,add']);
    $routes->get('receipt/(:num)', 'InventoryController::receipt/$1', ['filter' => 'permission:inventory,view']);

    // Task 2 — Stock Outward.
    $routes->get('outward', 'InventoryController::outward', ['filter' => 'permission:inventory,add']);
    $routes->post('outward', 'InventoryController::storeOutward', ['filter' => 'permission:inventory,add']);

    // Task 3 — Stock Search.
    $routes->get('search', 'InventoryController::search', ['filter' => 'permission:inventory,view']);

    // Task 4 — Physical Verification + correction requests.
    $routes->get('verify', 'InventoryController::verify', ['filter' => 'permission:inventory,add']);
    $routes->post('verify', 'InventoryController::storeVerification', ['filter' => 'permission:inventory,add']);
    $routes->get('corrections', 'InventoryController::corrections', ['filter' => 'permission:inventory,view']);
    $routes->post('corrections/approve/(:num)', 'InventoryController::approveCorrection/$1', ['filter' => 'permission:inventory,approve']);
    $routes->post('corrections/reject/(:num)', 'InventoryController::rejectCorrection/$1', ['filter' => 'permission:inventory,approve']);

    // Masters (owner/admin): products, godowns, parties.
    $routes->get('masters', 'InventoryController::masters', ['filter' => 'permission:inventory,edit']);
    $routes->post('masters/product', 'InventoryController::storeProduct', ['filter' => 'permission:inventory,edit']);
    $routes->post('masters/warehouse', 'InventoryController::storeWarehouse', ['filter' => 'permission:inventory,edit']);
    $routes->post('masters/party', 'InventoryController::storeParty', ['filter' => 'permission:inventory,edit']);
    $routes->post('masters/delete/(:alpha)/(:num)', 'InventoryController::deleteMaster/$1/$2', ['filter' => 'permission:inventory,delete']);

    // Serve an attachment file.
    $routes->get('attachment/(:num)', 'InventoryController::attachment/$1', ['filter' => 'permission:inventory,view']);
});
