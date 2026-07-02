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
            'login'    => 'required',
            'password' => 'required',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', 'Please fill in all fields.');
        }

        $login    = (string) $this->request->getPost('login');
        $password = (string) $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');

        [$ok, $message] = auth()->attempt($login, $password, $remember);

        if (! $ok) {
            return redirect()->back()->withInput()->with('error', $message);
        }

        activity_log('Auth', 'Login', 'User logged in');
        return redirect()->to(site_url('dashboard'))->with('success', $message);
    }

    public function logout()
    {
        activity_log('Auth', 'Logout', 'User logged out');
        auth()->logout();
        return redirect()->to(site_url('login'))->with('success', 'You have been logged out.');
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

            // In production, email this link. For this build we surface it directly.
            $link = site_url('reset-password/' . $token);
            log_message('info', 'Password reset link for {email}: {link}', ['email' => $email, 'link' => $link]);
            session()->setFlashdata('reset_link', $link);
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
                'password'       => password_hash((string) $this->request->getPost('password'), PASSWORD_DEFAULT),
                'remember_token' => null,
            ]);
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
