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
        $db = \Config\Database::connect();
        $users = $db->table('users')
            ->select('id, first_name, last_name, email, mobile, user_type, status, is_view_only')
            ->where('COALESCE(isSuperAdmin,0) != 1', null, false)
            ->where("COALESCE(status,'') != 'Delete'", null, false)
            ->orderBy('first_name', 'ASC')
            ->get()->getResult();

        $userTemplates = [];
        foreach ($users as $u) {
            $userTemplates[(int) $u->id] = erp_user_view_only_templates((int) $u->id);
        }
        $templates = $db->table('aa_template t')
            ->select('t.template_id, t.FY, t.track_name, f.name AS firm_name', false)
            ->join('firm_name f', 'f.id = t.firm_name_id', 'left')
            ->where('t.status', 'Active')
            ->orderBy('f.name', 'ASC')->orderBy('t.FY', 'DESC')
            ->get()->getResult();

        return _layout('\App\Modules\Admin\Views\setting\view_only', [
            'title'          => 'View-Only Users · C R Industries ERP',
            'users'          => $users,
            'templates'      => $templates,
            'user_templates' => $userTemplates,
        ]);
    }

    /** Persist the view-only scopes — global + per-firm (Super Admin ONLY). */
    public function save_view_only()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'denied']);
        }
        erp_ensure_view_only_column();
        $db     = \Config\Database::connect();
        $global = array_values(array_filter(array_map('intval', (array) $this->request->getPost('vo_global'))));
        $tpl    = (array) $this->request->getPost('vo_tpl');   // [user_id => [template_id, ...]]

        // 1) Global flag: reset all non-super-admin, then flag "all firms" users.
        $db->table('users')->where('COALESCE(isSuperAdmin,0) != 1', null, false)->update(['is_view_only' => 0]);
        if (! empty($global)) {
            $db->table('users')->where('COALESCE(isSuperAdmin,0) != 1', null, false)->whereIn('id', $global)->update(['is_view_only' => 1]);
        }

        // 2) Per-firm rows: rebuild (the form is authoritative for all listed users).
        $db->table('aa_view_only_template')->truncate();
        $rows = [];
        foreach ($tpl as $uid => $tids) {
            $uid = (int) $uid;
            if ($uid <= 0 || in_array($uid, $global, true)) { continue; }
            foreach ((array) $tids as $t) {
                $t = (int) $t;
                if ($t > 0) { $rows[] = ['user_id' => $uid, 'template_id' => $t]; }
            }
        }
        if (! empty($rows)) { $db->table('aa_view_only_template')->insertBatch($rows); }

        return redirect()->to(base_url('admin/setting/view_only'))
            ->with('success', 'View-only settings updated. "All firms" users are read-only everywhere; the rest are read-only only in the firms you picked.');
    }
}
