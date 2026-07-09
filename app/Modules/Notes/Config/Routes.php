<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('notes', ['namespace' => 'Modules\Notes\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'NoteController::index', ['filter' => 'permission:notes,view']);
    $routes->get('recycle-bin', 'NoteController::recycleBin', ['filter' => 'permission:notes,view']);

    $routes->get('create', 'NoteController::create', ['filter' => 'permission:notes,add']);
    $routes->get('edit/(:num)', 'NoteController::edit/$1', ['filter' => 'permission:notes,edit']);

    $routes->post('save', 'NoteController::save', ['filter' => 'permission:notes,edit']);
    $routes->post('autosave', 'NoteController::autosave', ['filter' => 'permission:notes,edit']);

    $routes->get('toggle-pin/(:num)', 'NoteController::togglePin/$1', ['filter' => 'permission:notes,edit']);
    $routes->get('toggle-important/(:num)', 'NoteController::toggleImportant/$1', ['filter' => 'permission:notes,edit']);

    $routes->post('delete/(:num)', 'NoteController::delete/$1', ['filter' => 'permission:notes,delete']);
    $routes->post('restore/(:num)', 'NoteController::restore/$1', ['filter' => 'permission:notes,delete']);
    $routes->post('force-delete/(:num)', 'NoteController::forceDelete/$1', ['filter' => 'permission:notes,delete']);

    $routes->post('category/store', 'NoteController::storeCategory', ['filter' => 'permission:notes,edit']);
    $routes->post('category/delete/(:num)', 'NoteController::deleteCategory/$1', ['filter' => 'permission:notes,edit']);
});
