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
}
