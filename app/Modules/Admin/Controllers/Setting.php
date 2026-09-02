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

    /**
     * View-Only Users manager (Super Admin ONLY). A GLOBAL read-only flag on the
     * shared users table (is_view_only): a selected user can VIEW every module
     * they already have access to, but Add / Edit / Update / Delete are blocked
     * app-wide (enforced in permission_helper::erp_current_user_can + RbacFilter).
     * The SAME flag governs the CI3 app (they share the users table).
     */
    public function view_only()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return redirect()->to(base_url('admin/dashboard'))->with('error', 'Only Super Admin can manage view-only users.');
        }
        erp_ensure_view_only_column();
        $users = \Config\Database::connect()->table('users')
            ->select('id, first_name, last_name, email, mobile, user_type, status, is_view_only')
            ->where('COALESCE(isSuperAdmin,0) != 1', null, false)
            ->where("COALESCE(status,'') != 'Delete'", null, false)
            ->orderBy('first_name', 'ASC')
            ->get()->getResult();

        return _layout('\App\Modules\Admin\Views\setting\view_only', [
            'title' => 'View-Only Users · C R Industries ERP',
            'users' => $users,
        ]);
    }

    /** Persist the view-only flags (Super Admin ONLY). */
    public function save_view_only()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'denied']);
        }
        erp_ensure_view_only_column();
        $db  = \Config\Database::connect();
        $ids = array_values(array_filter(array_map('intval', (array) $this->request->getPost('view_only_ids'))));

        // Reset every non-super-admin user to full access, then flag the checked ones.
        $db->table('users')->where('COALESCE(isSuperAdmin,0) != 1', null, false)->update(['is_view_only' => 0]);
        if (! empty($ids)) {
            $db->table('users')->where('COALESCE(isSuperAdmin,0) != 1', null, false)->whereIn('id', $ids)->update(['is_view_only' => 1]);
        }
        return redirect()->to(base_url('admin/setting/view_only'))
            ->with('success', 'View-only users updated. Selected users can now only VIEW data — add, edit, update and delete are disabled for them everywhere.');
    }
}
