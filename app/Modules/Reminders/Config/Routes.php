<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

$routes->group('reminders', ['namespace' => 'Modules\Reminders\Controllers'], static function (RouteCollection $routes) {
    $routes->get('/', 'ReminderController::index', ['filter' => 'permission:reminders,view']);
    $routes->get('calendar', 'ReminderController::calendar', ['filter' => 'permission:reminders,view']);

    $routes->get('create', 'ReminderController::create', ['filter' => 'permission:reminders,add']);
    $routes->get('edit/(:num)', 'ReminderController::edit/$1', ['filter' => 'permission:reminders,edit']);
    $routes->post('save', 'ReminderController::save', ['filter' => 'permission:reminders,edit']);

    $routes->get('due', 'ReminderController::due', ['filter' => 'permission:reminders,view']);
    $routes->post('complete/(:num)', 'ReminderController::complete/$1', ['filter' => 'permission:reminders,edit']);
    $routes->post('snooze/(:num)', 'ReminderController::snooze/$1', ['filter' => 'permission:reminders,edit']);
    $routes->post('dismiss/(:num)', 'ReminderController::dismiss/$1', ['filter' => 'permission:reminders,edit']);
    $routes->post('delete/(:num)', 'ReminderController::delete/$1', ['filter' => 'permission:reminders,delete']);
});
