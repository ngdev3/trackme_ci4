<?php

namespace App\Modules\PermissionDenied\Controllers;

use App\Controllers\BaseController;

/**
 * Permission Denied — CI4 port. The CI3 version rendered the admin shell
 * (layout.php); here it is a self-contained access-denied page so it does not
 * depend on the admin Metronic layout (T-017). RBAC bounces land here.
 */
class PermissionDenied extends BaseController
{
    public function index()
    {
        return $this->response
            ->setStatusCode(403)
            ->setBody(view('\App\Modules\PermissionDenied\Views\denied'));
    }
}
