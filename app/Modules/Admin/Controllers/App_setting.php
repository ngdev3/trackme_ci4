<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AppSettingModel;

/**
 * App_setting — CI4 port of admin/App_setting. Per-user application preferences
 * (currently the personalised dashboard layout — section order + show/hide).
 * Open to any authenticated admin (personal prefs, not in the RBAC registry).
 */
class App_setting extends BaseController
{
    protected $helpers = ['url', 'app'];

    private function uid(): int
    {
        return (int) (currentuserinfo()->id ?? 0);
    }

    public function index()
    {
        return _layout('\App\Modules\Admin\Views\appsetting\index', [
            'title'    => 'App Settings · C R Industries ERP',
            'sections' => (new AppSettingModel())->resolveLayout($this->uid()),
        ]);
    }

    public function save_dashboard_layout()
    {
        $items = $this->readLayoutInput();
        if ($items === null) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid layout payload.']);
        }
        (new AppSettingModel())->saveLayout($this->uid(), $items);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Dashboard layout saved.']);
    }

    public function reset_dashboard_layout()
    {
        (new AppSettingModel())->resetLayout($this->uid());
        return $this->response->setJSON(['status' => 'success', 'message' => 'Dashboard layout reset to default.']);
    }

    /** Accept a JSON body or a form field `layout` = JSON array of {key, hidden}. */
    private function readLayoutInput()
    {
        $raw = $this->request->getPost('layout');
        if ($raw === null) {
            $json = $this->request->getJSON(true);
            if (is_array($json) && isset($json['layout'])) { $raw = $json['layout']; }
        }
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : null;
        }
        return is_array($raw) ? $raw : null;
    }
}
