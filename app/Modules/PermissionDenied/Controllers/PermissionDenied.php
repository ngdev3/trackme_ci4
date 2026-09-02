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
        // ?vo=1 marks a view-only (read-only mode) bounce — show a friendly page
        // rather than the accusatory "you don't have permission" one.
        $viewOnly = (string) $this->request->getGet('vo') === '1';

        return $this->response
            ->setStatusCode(403)
            ->setBody(view('\App\Modules\PermissionDenied\Views\denied', ['view_only' => $viewOnly]));
    }
}
