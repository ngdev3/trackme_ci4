<?php

/**
 * Task module routes (top-level, URL task/task/*). The task/* group carries the
 * adminAuth+fyContext+rbac guard chain (configured in app/Config/Filters.php).
 */

use App\Modules\Task\Controllers\Task;

$routes->get('task/task', [Task::class, 'index']);
$routes->get('task', [Task::class, 'index']);
$routes->post('task/task/view_all', [Task::class, 'view_all']);
$routes->match(['GET', 'POST'], 'task/task/add', [Task::class, 'add']);
$routes->match(['GET', 'POST'], 'task/task/edit/(:segment)', [Task::class, 'edit']);
$routes->get('task/task/view/(:segment)', [Task::class, 'view']);
$routes->post('task/task/delete', [Task::class, 'delete']);
$routes->post('task/task/set_status', [Task::class, 'set_status']);
$routes->post('task/task/comment_add', [Task::class, 'comment_add']);
$routes->post('task/task/comment_delete', [Task::class, 'comment_delete']);
$routes->post('task/task/get_comments', [Task::class, 'get_comments']);
$routes->get('task/task/get_comments/(:segment)', [Task::class, 'get_comments']);
$routes->post('task/task/notifications', [Task::class, 'notifications']);
$routes->post('task/task/mark_read', [Task::class, 'mark_read']);
