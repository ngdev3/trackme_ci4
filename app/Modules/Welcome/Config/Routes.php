<?php

use App\Modules\Welcome\Controllers\Welcome;

$routes->get('welcome', [Welcome::class, 'index']);
