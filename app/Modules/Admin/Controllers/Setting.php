<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\SettingModel;

/**
 * Setting — CI4 port of the firm/FY workspace switch (CI3 Setting::change_fy_id)
 * plus a Settings hub landing. change_fy_id is the endpoint the top-nav "Change
 * Firm" modal (elements/setting.php) posts to; hub is the sidebar "Setting" link.
 */
class Setting extends BaseController
{
    protected $helpers = ['url', 'app', 'permission'];

    /** Switch the active firm/FY workspace (AJAX, from the Change Firm modal). */
    public function change_fy_id()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error', 'message' => 'POST required.']);
        }

        $templateId = (int) $this->request->getPost('template_fy');
        $u = currentuserinfo();

        if (empty($templateId) || ! $u || empty($u->id)) {
            session()->setFlashdata('error', 'Please select a valid Financial Year');
            return $this->response->setJSON(['status' => 'error']);
        }

        $m = new SettingModel();
        $m->switchFirm((int) $u->id, $templateId);
        $m->logSwitch((int) $u->id, $templateId, 'Web', $this->request->getIPAddress());

        // Refresh the session user row (now carrying the new default_firm) and
        // drop the cached firm context so FyContext re-resolves it next request.
        $fresh = $m->reloadUser((string) $u->email);
        if ($fresh) {
            session()->set('userinfo', $fresh);
        }
        session()->remove('fy');

        session()->setFlashdata('cr_toast', ['type' => 'success', 'message' => 'Firm Loaded Successfully !!']);
        return $this->response->setJSON(['status' => 'success']);
    }

    /** Settings landing hub — current workspace + links to available settings. */
    public function hub()
    {
        return _layout('\App\Modules\Admin\Views\setting\hub', [
            'title'   => 'Settings · C R Industries ERP',
            'firm'    => service('fyContext')->fyRow(),
            'firms'   => (new SettingModel())->financialYears(),
        ]);
    }
}
