<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;

/**
 * Setting — CI4 port slice: the Change Firm / Financial-Year switch used by the
 * top-nav modal (elements/setting.php). Mirrors CI3 Setting::change_fy_id:
 * persist users.default_firm, reload the firm/FY context, log the switch.
 */
class Setting extends BaseController
{
    protected $helpers = ['url', 'app'];

    /** AJAX: switch the active firm/FY. POST template_fy. Returns { status }. */
    public function change_fy_id()
    {
        $tid = (int) $this->request->getPost('template_fy');
        if (! $tid) {
            session()->setFlashdata('error', 'Please select a valid Financial Year');
            return $this->response->setJSON(['status' => 'error']);
        }

        $db  = \Config\Database::connect();
        $tpl = $db->table('aa_template')->where('template_id', $tid)->get()->getRow();
        if (! $tpl) {
            return $this->response->setJSON(['status' => 'error']);
        }

        $uid = (int) (currentuserinfo()->id ?? 0);

        // 1) Persist the selection on the user (CI3 Setting_mod::add_fy).
        $db->table('users')->where('id', $uid)->update(['default_firm' => $tid]);

        // 2) Update the in-session user + reload the firm/FY row (CI3 getUserDetail).
        $info = currentuserinfo();
        if (is_object($info)) {
            $info->default_firm = $tid;
            session()->set('userinfo', $info);
        }
        service('fyContext')->loadFirmContext();

        // 3) Switch audit log (CI3 log_template_switch) — lazy table, best-effort.
        try {
            $db->query("CREATE TABLE IF NOT EXISTS `aa_template_switch_log` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `user_id` INT NULL, `template_id` INT NULL,
                `selected_at` DATETIME NULL, `ip_address` VARCHAR(45) NULL,
                `source` VARCHAR(10) NULL,
                KEY `idx_tsl_user` (`user_id`), KEY `idx_tsl_template` (`template_id`)) ENGINE=InnoDB");
            $db->table('aa_template_switch_log')->insert([
                'user_id' => $uid, 'template_id' => $tid,
                'selected_at' => date('Y-m-d H:i:s'),
                'ip_address' => $this->request->getIPAddress(), 'source' => 'Web',
            ]);
        } catch (\Throwable $e) {
            // audit is non-critical
        }

        session()->setFlashdata('success', 'Firm Loaded Successfully !!');
        return $this->response->setJSON(['status' => 'success']);
    }
}
