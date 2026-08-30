<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Dashboard — P2/T-017 stub rendered inside the real Metronic admin shell.
 * Proves the ported layout (Metronic assets + RBAC sidebar + header context)
 * works end-to-end. Replaced by the full Dashboard module in P3.
 */
class Dashboard extends BaseController
{
    public function index()
    {
        return _layout('\App\Modules\Admin\Views\dashboard', [
            'title' => 'Dashboard · C R Industries ERP',
        ]);
    }
}
