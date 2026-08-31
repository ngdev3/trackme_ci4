<?php

/**
 * Task module routes — CI3 top-level `task` module.
 * The task/* group carries adminAuth + fyContext + rbac (Config\Filters).
 * URLs preserved 1:1 from CI3 (task/task[/method]).
 */

use App\Modules\Task\Controllers\Task;

// Bare `task` = the CI3 module default landing (base_url('task') in left_menu).
$routes->get('task', [Task::class, 'index']);
$routes->get('task/task', [Task::class, 'index']);
$routes->get('task/task/index', [Task::class, 'index']);
$routes->post('task/task/view_all', [Task::class, 'view_all']);
$routes->match(['get', 'post'], 'task/task/add', [Task::class, 'add']);
$routes->match(['get', 'post'], 'task/task/edit/(:segment)', [Task::class, 'edit']);
$routes->get('task/task/view/(:segment)', [Task::class, 'view']);
$routes->post('task/task/delete', [Task::class, 'delete']);
$routes->post('task/task/set_status', [Task::class, 'set_status']);
$routes->post('task/task/comment_add', [Task::class, 'comment_add']);
$routes->post('task/task/comment_delete', [Task::class, 'comment_delete']);
$routes->match(['get', 'post'], 'task/task/get_comments/(:segment)', [Task::class, 'get_comments']);
$routes->post('task/task/get_comments', [Task::class, 'get_comments']);
$routes->get('task/task/notifications', [Task::class, 'notifications']);
$routes->post('task/task/mark_read', [Task::class, 'mark_read']);
