<?php

namespace Modules\Api\Controllers;

use App\Libraries\OAuth\OAuthException;
use App\Libraries\OAuth\OAuthManager;
use App\Models\ApiTokenModel;
use App\Models\EmailOtpModel;
use App\Models\PasswordResetModel;
use App\Models\UserModel;
use Config\Services;

/**
 * Stateless auth API for the mobile app.
 *
 *   POST /api/v1/auth/login            {login, password}
 *   POST /api/v1/auth/google           {id_token}
 *   POST /api/v1/auth/forgot-password  {email}
 *   POST /api/v1/auth/change-password  (Bearer) {current_password, new_password}
 *   POST /api/v1/auth/request-email-otp {email}
 *   POST /api/v1/auth/verify-email-otp  {email, code}
 *   POST /api/v1/auth/logout           (Bearer)
 */
class AuthApiController extends BaseApiController
{
    /**
     * Authenticate and issue a bearer token. Enforces the per-user mobile-login
     * toggle and surfaces the must_change_password flag so the app can route the
     * user to a change-password screen.
     */
    public function login()
    {
        $login    = trim((string) $this->input('login', ''));
        $password = (string) $this->input('password', '');

        if ($login === '' || $password === '') {
            return $this->failValidationErrors('login and password are required.');
        }

        $user = (new UserModel())->findByLogin($login);
        if (! $user || ! $user['password'] || ! password_verify($password, $user['password'])) {
            return $this->failUnauthorized('Invalid credentials.');
        }
        if ((int) $user['status'] !== 1) {
            return $this->failForbidden('Your account is inactive.');
        }
        if ((int) ($user['mobile_login_enabled'] ?? 1) !== 1) {
            return $this->failForbidden('Mobile app access is disabled for this account.');
        }

        $token = (new ApiTokenModel())->issue((int) $user['id'], 'mobile');
        (new UserModel())->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return $this->respond([
            'status'                => 'success',
            'token'                 => $token,
            'token_type'            => 'Bearer',
            'must_change_password'  => (int) ($user['must_change_password'] ?? 0) === 1,
            'user'                  => $this->publicUser($user),
        ]);
    }

    /**
     * Sign in with a Google ID token obtained by the app (native sign-in / GIS).
     *
     * The token is verified against Google (signature + audience + expiry) before
     * we trust any of its claims, then mapped to a local account (matched by
     * linked provider id or email, created on first sign-in). Enforces the same
     * active + mobile-login gating as password login and issues a bearer token.
     */
    public function google()
    {
        $idToken = trim((string) $this->input('id_token', ''));
        if ($idToken === '') {
            return $this->failValidationErrors('id_token is required.');
        }

        try {
            $profile = (new OAuthManager())->provider('google')->verifyIdToken($idToken);
        } catch (OAuthException $e) {
            return $this->failUnauthorized($e->getMessage());
        } catch (\Throwable $e) {
            log_message('error', '[Api] Google sign-in fatal: ' . $e->getMessage());
            return $this->failServerError('Sign-in failed. Please try again.');
        }

        $result = auth()->findOrCreateOAuthUser($profile);
        if (isset($result['error'])) {
            return $this->failForbidden($result['error']);
        }
        $user = $result['user'];

        if ((int) $user['status'] !== 1) {
            return $this->failForbidden('Your account is inactive.');
        }
        if ((int) ($user['mobile_login_enabled'] ?? 1) !== 1) {
            return $this->failForbidden('Mobile app access is disabled for this account.');
        }

        $token = (new ApiTokenModel())->issue((int) $user['id'], 'mobile');
        (new UserModel())->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return $this->respond([
            'status'               => 'success',
            'token'                => $token,
            'token_type'           => 'Bearer',
            'must_change_password' => false,
            'is_new_user'          => (bool) ($result['is_new'] ?? false),
            'user'                 => $this->publicUser($user),
        ]);
    }

    /**
     * Sign in with Truecaller. The app obtains an OAuth authorization code via
     * the Truecaller SDK (PKCE); we exchange it server-side for the verified
     * phone number, then map it to a customer account (created on first use).
     * Config-gated by Config\Truecaller — returns 503 until a client id is set.
     *
     *   POST /api/v1/auth/truecaller  {authorization_code, code_verifier}
     */
    public function truecaller()
    {
        $code     = trim((string) $this->input('authorization_code', ''));
        $verifier = trim((string) $this->input('code_verifier', ''));
        if ($code === '' || $verifier === '') {
            return $this->failValidationErrors('authorization_code and code_verifier are required.');
        }

        $verifierLib = new \App\Libraries\TruecallerVerifier();
        if (! $verifierLib->isConfigured()) {
            return $this->fail('Truecaller sign-in is not configured on the server yet.', 503);
        }

        $res = $verifierLib->verify($code, $verifier);
        if (! $res['ok']) {
            $msg = $res['error'] === 'invalid_code'
                ? 'Truecaller verification failed or expired. Please try again.'
                : ($res['error'] === 'no_phone'
                    ? 'Truecaller did not share a phone number.'
                    : 'Could not verify Truecaller. Please try again.');
            return $this->failUnauthorized($msg);
        }

        $result = auth()->findOrCreatePhoneUser($res['phone'], $res['name'] ?? null, $res['email'] ?? null);
        if (isset($result['error'])) {
            return $this->failForbidden($result['error']);
        }
        $user = $result['user'];

        if ((int) $user['status'] !== 1) {
            return $this->failForbidden('Your account is inactive.');
        }
        if ((int) ($user['mobile_login_enabled'] ?? 1) !== 1) {
            return $this->failForbidden('Mobile app access is disabled for this account.');
        }

        $token = (new ApiTokenModel())->issue((int) $user['id'], 'mobile');
        (new UserModel())->update((int) $user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        return $this->respond([
            'status'               => 'success',
            'token'                => $token,
            'token_type'           => 'Bearer',
            'must_change_password' => false,
            'is_new_user'          => (bool) ($result['is_new'] ?? false),
            'user'                 => $this->publicUser($user),
        ]);
    }

    /**
     * Generate a password-reset token. Always responds the same way to avoid
     * account enumeration.
     */
    public function forgotPassword()
    {
        $email = trim((string) $this->input('email', ''));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors('A valid email is required.');
        }

        $user = (new UserModel())->where('email', $email)->first();
        if ($user) {
            $token = bin2hex(random_bytes(32));
            (new PasswordResetModel())->insert([
                'email'      => $email,
                'token'      => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            // Email the reset link (opens the web reset page). Logged as a fallback.
            helper('reset_email');
            send_password_reset_email($email, $token);
            log_message('info', 'API password reset for {email}: {token}', ['email' => $email, 'token' => $token]);
        }

        return $this->respond([
            'status'  => 'success',
            'message' => 'If that email exists, a password reset link has been sent.',
        ]);
    }

    /**
     * Change the authenticated user's password and clear must_change_password.
     */
    public function changePassword()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }

        $current = (string) $this->input('current_password', '');
        $new     = (string) $this->input('new_password', '');

        if (strlen($new) < 8) {
            return $this->failValidationErrors('New password must be at least 8 characters.');
        }
        if (! $user['password'] || ! password_verify($current, $user['password'])) {
            return $this->failValidationErrors('Current password is incorrect.');
        }
        if (password_verify($new, $user['password'])) {
            return $this->failValidationErrors('New password must differ from the current one.');
        }

        (new UserModel())->update((int) $user['id'], [
            'password'             => password_hash($new, PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);

        // Confirmation email (best-effort; never blocks the response).
        Services::mailer()->passwordChanged((string) $user['email'], (string) ($user['name'] ?? ''));

        return $this->respond(['status' => 'success', 'message' => 'Password updated.']);
    }

    /**
     * Send a 6-digit email-verification code. Used by the app's signup flow to
     * confirm an email address. Throttled to one code per minute and always
     * answers the same way to avoid revealing whether an email exists.
     */
    public function requestEmailOtp()
    {
        $email = strtolower(trim((string) $this->input('email', '')));
        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors('A valid email is required.');
        }

        $otps = new EmailOtpModel();
        $since = $otps->secondsSinceLast($email);
        if ($since !== null && $since < 60) {
            return $this->fail('Please wait a moment before requesting another code.', 429);
        }

        $code = $otps->issue($email, 'email_verify', 10);
        Services::mailer()->emailOtp($email, $code, 10);
        log_message('info', 'Email OTP issued for {email}', ['email' => $email]);

        return $this->respond([
            'status'  => 'success',
            'message' => 'A verification code has been sent to your email.',
            'expires_in' => 600,
        ]);
    }

    /**
     * Verify a 6-digit email-verification code. On success the address is marked
     * verified (and stamped on the user if an account exists).
     */
    public function verifyEmailOtp()
    {
        $email = strtolower(trim((string) $this->input('email', '')));
        $code  = trim((string) $this->input('code', ''));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failValidationErrors('A valid email is required.');
        }
        if (! preg_match('/^\d{6}$/', $code)) {
            return $this->failValidationErrors('Enter the 6-digit code from your email.');
        }

        $otps = new EmailOtpModel();
        $row  = $otps->latestLive($email, 'email_verify');
        if (! $row) {
            return $this->failValidationErrors('The code is invalid or has expired. Please request a new one.');
        }
        if ((int) $row['attempts'] >= EmailOtpModel::MAX_ATTEMPTS) {
            $otps->consume((int) $row['id']);
            return $this->fail('Too many attempts. Please request a new code.', 429);
        }
        if (! hash_equals((string) $row['code_hash'], EmailOtpModel::hash($code))) {
            $otps->bumpAttempts((int) $row['id'], (int) $row['attempts']);
            return $this->failValidationErrors('That code is incorrect. Please try again.');
        }

        // Correct code — burn it and stamp the account (if any) as verified.
        $otps->consume((int) $row['id']);
        $users = new UserModel();
        $user  = $users->where('email', $email)->first();
        if ($user && empty($user['email_verified_at'])) {
            $users->update((int) $user['id'], ['email_verified_at' => date('Y-m-d H:i:s')]);
        }

        return $this->respond([
            'status'   => 'success',
            'message'  => 'Email verified.',
            'verified' => true,
        ]);
    }

    /**
     * Revoke the presented token.
     */
    public function logout()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $header = (string) $this->request->getHeaderLine('Authorization');
        if (preg_match('/Bearer\s+([a-f0-9]{64})/i', $header, $m)) {
            (new ApiTokenModel())->where('token', $m[1])->delete();
        }
        return $this->respond(['status' => 'success', 'message' => 'Logged out.']);
    }
}
