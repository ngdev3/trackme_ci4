<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\MenuOrderModel;

/**
 * Menu_order — CI4 port of admin/Menu_order. Persists each user's personalised
 * left-menu order + hidden keys (aa_user_menu_order). JSON-only utility endpoints
 * (any logged-in admin can save their own preference).
 */
class Menu_order extends BaseController
{
    protected $helpers = ['url', 'app'];

    /** POST: order = JSON array of keys (required); hidden = JSON array (optional). */
    public function save()
    {
        $uid = (int) (currentuserinfo()->id ?? 0);

        $order = json_decode((string) $this->request->getPost('order'), true);
        if (! is_array($order)) { $order = []; }

        $hiddenRaw = $this->request->getPost('hidden');
        $hidden = ($hiddenRaw === null) ? null : json_decode((string) $hiddenRaw, true);
        if ($hidden !== null && ! is_array($hidden)) { $hidden = []; }

        $ok = (new MenuOrderModel())->save($uid, $order, $hidden);
        return $this->response->setJSON(['status' => $ok ? 'success' : 'error', 'count' => count($order)]);
    }

    /** POST/GET: clear the current user's saved order (back to default). */
    public function reset()
    {
        $uid = (int) (currentuserinfo()->id ?? 0);
        $ok  = (new MenuOrderModel())->reset($uid);
        return $this->response->setJSON(['status' => $ok ? 'success' : 'error']);
    }
}
