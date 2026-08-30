<?php

use App\Modules\PermissionDenied\Controllers\PermissionDenied;

$routes->get('permission_denied', [PermissionDenied::class, 'index']);
