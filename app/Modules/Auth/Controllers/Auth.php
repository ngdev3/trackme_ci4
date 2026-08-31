<?php

namespace App\Modules\Auth\Controllers;

use App\Controllers\BaseController;
use App\Modules\Auth\Models\AuthModel;

/**
 * Auth — admin web login. CI4 port of admin/Auth::login + logout. Sets the same
 * session keys CI3 did (userinfo, isLogin, user_type, session_started_at,
 * session_expires_at) so FyContext + the ported RBAC helper work unchanged, and
 * loads the firm context immediately after login.
 *
 * URLs (preserve CI3): admin/auth, admin/auth/login (POST), admin/auth/logout.
 */
class Auth extends BaseController
{
    protected $helpers = ['url', 'form'];

    private const SESSION_TTL = 28800; // 8h (CI3 used session_timeout_seconds())

    /** Login form (already logged in -> dashboard). */
    public function index()
    {
        if (service('fyContext')->isLoggedIn()) {
            return redirect()->to(site_url('admin/dashboard'));
        }
        return view('\App\Modules\Auth\Views\login', [
            'error'    => session()->getFlashdata('error'),
            'redirect' => $this->request->getGet('redirect'),
        ]);
    }

    /** Authenticate (POST). */
    public function login()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return redirect()->to(site_url('admin/auth'));
        }

        $rules = [
            'email'    => 'trim|required|valid_email',
            'password' => 'trim|required',
        ];
        if (! $this->validate($rules)) {
            return redirect()->to(site_url('admin/auth'))
                ->with('error', implode(' ', $this->validator->getErrors()));
        }

        $email    = (string) $this->request->getPost('email');
        $password = (string) $this->request->getPost('password');

        $res = (new AuthModel())->loginAuthorize($email, $password);

        if ($res['status'] !== 'success') {
            return redirect()->to(site_url('admin/auth'))
                ->with('error', $res['error_msg'] ?? 'Login failed.');
        }

        $user = $res['result'];
        $now  = time();
        session()->set([
            'userinfo'           => $user,
            'isLogin'            => 'yes',
            'user_type'          => $user->user_type,
            'session_started_at' => $now,
            'session_expires_at' => $now + self::SESSION_TTL,
        ]);

        // Load the firm/FY context now (CI3 validate_admin_login()).
        service('fyContext')->loadFirmContext();

        $redirect = $this->request->getPost('redirect');
        return redirect()->to($redirect ? site_url($redirect) : site_url('admin/dashboard'));
    }

    /** Logout — clear the admin session. */
    public function logout()
    {
        session()->remove(['userinfo', 'fy', 'isLogin', 'user_type', 'session_started_at', 'session_expires_at']);
        session()->destroy();
        return redirect()->to(site_url('admin/auth'))->with('error', 'You have been signed out.');
    }

    /**
     * Renew the top-nav session meter's active window (AJAX).
     * Returns { status, expires_at } — the meter resets from expires_at.
     */
    public function renew_session()
    {
        if (session()->get('isLogin') !== 'yes') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not logged in.']);
        }
        $now = time();
        session()->set('session_started_at', $now);
        session()->set('session_expires_at', $now + self::SESSION_TTL);
        // The nav meter runs a 1-hour active window; hand it back a fresh one.
        return $this->response->setJSON(['status' => 'success', 'expires_at' => $now + 3600]);
    }

    /**
     * Change the active firm / financial year (top-nav Change Firm switcher, AJAX).
     * Mirrors CI3 Setting::change_fy_id: persist users.default_firm, reload the
     * firm/FY context, and log the switch. helper('app') gives currentuserinfo().
     */
    public function change_fy()
    {
        helper('app');
        if (session()->get('isLogin') !== 'yes') {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Not logged in.']);
        }
        $tid = (int) $this->request->getPost('template_fy');
        if (! $tid) {
            session()->setFlashdata('error', 'Please select a valid Financial Year');
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Please select a valid Financial Year']);
        }

        $db  = \Config\Database::connect();
        $tpl = $db->table('aa_template')->where('template_id', $tid)->get()->getRow();
        if (! $tpl) {
            return $this->response->setJSON(['status' => 'error', 'msg' => 'Firm not found.']);
        }

        $uid = (int) (currentuserinfo()->id ?? 0);

        // 1) Persist the selection on the user (CI3 add_fy).
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
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NULL, `template_id` INT(11) NULL,
                `selected_at` DATETIME NULL, `ip_address` VARCHAR(45) NULL,
                `source` VARCHAR(20) NULL, PRIMARY KEY (`id`)) ENGINE=InnoDB");
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

    /** Verify the login password to unlock the web panel (AJAX). */
    public function unlock_web_lock()
    {
        helper('app');
        if (session()->get('isLogin') !== 'yes') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Session expired']);
        }
        $password = (string) $this->request->getPost('password');
        if ($password === '') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Please enter your login password.']);
        }
        $uid = (int) (currentuserinfo()->id ?? 0);
        $row = \Config\Database::connect()->table('users')->select('id, password')->where('id', $uid)->get()->getRow();
        if ($row && md5($password) === $row->password) {
            session()->set('web_lock_last_unlocked_at', time());
            return $this->response->setJSON(['status' => 'success', 'message' => 'Unlocked']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid login password.']);
    }
}
