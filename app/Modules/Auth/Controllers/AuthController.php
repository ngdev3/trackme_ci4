<?php

namespace Modules\Auth\Controllers;

use App\Controllers\BaseController;
use App\Models\PasswordResetModel;
use App\Models\UserModel;

class AuthController extends BaseController
{
    protected string $moduleView = 'Modules\Auth\Views\\';

    // ---------------------------------------------------------------
    // Login
    // ---------------------------------------------------------------
    public function login()
    {
        return view($this->moduleView . 'login');
    }

    public function attemptLogin()
    {
        $rules = [
            'login'    => 'required|valid_email',
            'password' => 'required',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please enter your email address and password.');
        }

        $login    = (string) $this->request->getPost('login');
        $password = (string) $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');

        [$ok, $message] = auth()->attempt($login, $password, $remember);

        if (! $ok) {
            return redirect()->back()->withInput()->with('error', $message);
        }

        activity_log('Auth', 'Login', 'User logged in');
        helper('company');
        return redirect()->to(post_login_url())->with('success', $message);
    }

    public function logout()
    {
        activity_log('Auth', 'Logout', 'User logged out');
        auth()->logout();
        return redirect()->to(site_url('login'))->with('success', 'You have been logged out.');
    }

    /** Return from Super Admin impersonation to the admin page it was launched from. */
    public function stopImpersonating()
    {
        // Captured when impersonation started (SuperAdminController::impersonate).
        $return = (string) (session('impersonator_return') ?? '');
        session()->remove('impersonator_return');

        [$ok, $msg] = auth()->stopImpersonating();

        // Land back exactly where the admin came from, when it's a safe same-site URL.
        if ($ok && $return !== '' && str_starts_with($return, base_url())) {
            return redirect()->to($return)->with('success', $msg);
        }
        return redirect()->to(site_url($ok ? 'admin' : 'login'))->with($ok ? 'success' : 'error', $msg);
    }

    // ---------------------------------------------------------------
    // Forced password change (must_change_password flag)
    // ---------------------------------------------------------------
    public function changePassword()
    {
        return view($this->moduleView . 'change_password', [
            'forced' => (bool) (current_user()['must_change_password'] ?? false),
            'errors' => session()->getFlashdata('errors') ?? [],
        ]);
    }

    public function updateForcedPassword()
    {
        $rules = [
            'current_password' => 'required',
            'new_password'     => 'required|min_length[8]',
            'confirm_password' => 'required|matches[new_password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $user = current_user();
        if (! $user || ! password_verify((string) $this->request->getPost('current_password'), (string) $user['password'])) {
            return redirect()->back()->with('error', 'Your current password is incorrect.');
        }

        $new = (string) $this->request->getPost('new_password');
        if (password_verify($new, (string) $user['password'])) {
            return redirect()->back()->with('error', 'Please choose a password different from your current one.');
        }

        (new UserModel())->update((int) $user['id'], [
            'password'             => password_hash($new, PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);

        service('mailer')->passwordChanged((string) $user['email'], (string) ($user['name'] ?? ''));

        activity_log('Auth', 'Edit', 'Completed a required password change');
        return redirect()->to(site_url('dashboard'))->with('success', 'Your password has been updated.');
    }

    // ---------------------------------------------------------------
    // Forgot password
    // ---------------------------------------------------------------
    public function forgotPassword()
    {
        return view($this->moduleView . 'forgot_password');
    }

    public function sendResetLink()
    {
        if (! $this->validate(['email' => 'required|valid_email'])) {
            return redirect()->back()->withInput()->with('error', 'Enter a valid email address.');
        }

        $email = (string) $this->request->getPost('email');
        $user  = (new UserModel())->where('email', $email)->first();

        // Always respond the same way to avoid account enumeration.
        if ($user) {
            $token = bin2hex(random_bytes(32));
            (new PasswordResetModel())->insert([
                'email'      => $email,
                'token'      => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            // Email the reset link; also surface it in-session as a fallback for
            // environments where SMTP isn't configured yet.
            helper('reset_email');
            $sent = send_password_reset_email($email, $token);
            $link = site_url('reset-password/' . $token);
            // NEVER log the link/token (it grants a reset). Log the event only.
            log_message('info', 'Password reset requested for {email} (mailed: {sent})', ['email' => $email, 'sent' => $sent ? 'yes' : 'no']);
            if (! $sent) {
                session()->setFlashdata('reset_link', $link);
            }
        }

        return redirect()->to(site_url('forgot-password'))
            ->with('success', 'If that email exists, a password reset link has been generated.');
    }

    // ---------------------------------------------------------------
    // Reset password
    // ---------------------------------------------------------------
    public function resetPassword(string $token)
    {
        $row = $this->validToken($token);
        if (! $row) {
            return redirect()->to(site_url('login'))->with('error', 'This reset link is invalid or has expired.');
        }
        return view($this->moduleView . 'reset_password', ['token' => $token, 'email' => $row['email']]);
    }

    /**
     * One-click account activation from the emailed link (/activate/{token}).
     * Validates the token, flips the pending signup account active + email-
     * verified, and shows a success page telling the user to sign in on the app.
     */
    public function activate(string $token)
    {
        $activations = new \App\Models\AccountActivationModel();
        $row         = $activations->findLive($token);

        $users = new UserModel();
        $user  = $row ? $users->where('email', $row['email'])->first() : null;

        if (! $row || ! $user) {
            return view($this->moduleView . 'activated', [
                'ok'      => false,
                'appName' => service('mailer')->appName(),
            ]);
        }

        $patch = [];
        if ((int) $user['status'] !== 1) {
            $patch['status'] = 1;
        }
        if (empty($user['email_verified_at'])) {
            $patch['email_verified_at'] = date('Y-m-d H:i:s');
        }
        if ($patch !== []) {
            $users->update((int) $user['id'], $patch);
        }
        $activations->clearFor($row['email']);

        return view($this->moduleView . 'activated', [
            'ok'      => true,
            'appName' => service('mailer')->appName(),
            'email'   => $user['email'],
        ]);
    }

    public function updatePassword()
    {
        $rules = [
            'token'                 => 'required',
            'password'              => 'required|min_length[8]',
            'password_confirm'      => 'required|matches[password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $token = (string) $this->request->getPost('token');
        $row   = $this->validToken($token);
        if (! $row) {
            return redirect()->to(site_url('login'))->with('error', 'This reset link is invalid or has expired.');
        }

        $users = new UserModel();
        $user  = $users->where('email', $row['email'])->first();
        if ($user) {
            $users->update($user['id'], [
                'password'             => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                'remember_token'       => null,
                'must_change_password' => 0,
            ]);
            service('mailer')->passwordChanged((string) $user['email'], (string) ($user['name'] ?? ''));
        }

        // Invalidate all tokens for this email.
        (new PasswordResetModel())->where('email', $row['email'])->delete();

        return redirect()->to(site_url('login'))->with('success', 'Password updated. Please sign in.');
    }

    /**
     * Returns the reset row if the token is valid & unexpired, else null.
     */
    private function validToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $row  = (new PasswordResetModel())
            ->where('token', $hash)
            ->where('expires_at >=', date('Y-m-d H:i:s'))
            ->orderBy('id', 'DESC')
            ->first();
        return $row ?: null;
    }
}
