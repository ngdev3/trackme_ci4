<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

/**
 * Stock / Inventory (firm portal) — Product Master + Stock In/Out + reports.
 * Mirrors the mobile inventory suite and shares the same products /
 * stock_movements tables & models, so web and app stay in sync.
 */
$routes->group('inventory', ['namespace' => 'Modules\Inventory\Controllers', 'filter' => 'auth'], static function (RouteCollection $routes) {
    $routes->get('/', 'InventoryController::index');
    $routes->get('products', 'InventoryController::products');
    $routes->post('products/save', 'InventoryController::saveProduct');
    $routes->post('products/delete/(:num)', 'InventoryController::deleteProduct/$1');
    $routes->get('stock', 'InventoryController::stock');
    $routes->post('stock/move', 'InventoryController::moveStock');
    $routes->get('reports', 'InventoryController::reports');
});
