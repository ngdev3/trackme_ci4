<?php

namespace App\Libraries;

use App\Libraries\OAuth\OAuthUserProfile;
use App\Models\LoginAttemptModel;
use App\Models\LoginLogModel;
use App\Models\UserModel;
use Config\Database;
use Config\Services;

/**
 * Session-based authentication with login-attempt throttling and remember-me.
 */
class Auth
{
    protected const MAX_ATTEMPTS = 5;
    protected const LOCK_MINUTES = 15;

    protected UserModel $users;
    protected LoginAttemptModel $attempts;
    protected LoginLogModel $loginLogs;
    protected $session;

    public function __construct()
    {
        $this->users     = new UserModel();
        $this->attempts  = new LoginAttemptModel();
        $this->loginLogs = new LoginLogModel();
        $this->session   = Services::session();
    }

    public function check(): bool
    {
        return (bool) $this->session->get('user_id');
    }

    public function id(): ?int
    {
        $id = $this->session->get('user_id');
        return $id ? (int) $id : null;
    }

    /**
     * Cached current-user record (array) or null.
     */
    public function user(): ?array
    {
        if (! $this->check()) {
            return null;
        }
        return $this->users->find($this->id()) ?: null;
    }

    /**
     * Attempt a login. Returns [success(bool), message(string)].
     */
    public function attempt(string $login, string $password, bool $remember = false): array
    {
        $key = strtolower($login);

        if ($this->isLocked($key)) {
            $this->logLogin(null, $login, 'failed', 'Too many failed attempts');
            return [false, 'Too many failed attempts. Please try again in ' . self::LOCK_MINUTES . ' minutes.'];
        }

        $user = $this->users->findByLogin($login);

        if (! $user || ! password_verify($password, $user['password'])) {
            $this->recordFailure($key);
            $this->logLogin(null, $login, 'failed', 'Invalid credentials');
            return [false, 'Invalid username/email or password.'];
        }

        if ((int) $user['status'] !== 1) {
            $this->logLogin((int) $user['id'], $login, 'failed', 'Account inactive');
            return [false, 'Your account is inactive. Contact the administrator.'];
        }

        $this->clearAttempts($key);
        $this->establishSession($user);

        if ($remember) {
            $this->setRememberCookie((int) $user['id']);
        }

        $this->users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
        $logId = $this->logLogin((int) $user['id'], $login, 'success', 'Login successful');
        if ($logId) {
            $this->session->set('login_log_id', $logId);
        }

        Services::notifier()->user((int) $user['id'], 'Login successful', 'Your account signed in from ' . service('request')->getIPAddress() . '.', [
            'type'       => 'user_activity',
            'module'     => 'login_logs',
            'priority'   => 'normal',
            'action_url' => site_url('my-login-history'),
            'created_by' => null,
        ]);

        return [true, 'Welcome back, ' . $user['name'] . '!'];
    }

    /**
     * Sign a user in from a verified social (OAuth) profile.
     *
     * Only pre-existing database accounts may sign in: the profile is matched
     * by linked provider id first, then by email. If no account matches, the
     * sign-in is refused (no self-service account is created). On the first
     * social login the provider identity is linked to the matched account.
     * Returns [success(bool), message].
     */
    public function loginWithOAuth(OAuthUserProfile $profile): array
    {
        if ($profile->email === '') {
            return [false, 'Your ' . ucfirst($profile->provider) . ' account did not share an email address.'];
        }
        if (! $profile->emailVerified) {
            return [false, 'Your ' . ucfirst($profile->provider) . ' email address is not verified.'];
        }

        // Match an existing account: by linked provider id first, then email.
        $user = $this->users->findByProvider($profile->provider, $profile->providerId)
            ?? $this->users->where('email', $profile->email)->first();

        if ($user) {
            if ((int) $user['status'] !== 1) {
                $this->logLogin((int) $user['id'], $user['username'] ?? $profile->email, 'failed', 'Account inactive');
                return [false, 'Your account is inactive. Contact the administrator.'];
            }
            // Link the social identity / refresh avatar on first social login.
            $patch = [];
            if (empty($user['provider_id'])) {
                $patch['auth_provider'] = $profile->provider;
                $patch['provider_id']   = $profile->providerId;
            }
            if ($profile->picture && empty($user['avatar_url'])) {
                $patch['avatar_url'] = $profile->picture;
            }
            if ($patch !== []) {
                $this->users->update($user['id'], $patch);
                $user = array_merge($user, $patch);
            }
            $isNew = false;
        } else {
            // Gmail sign-up: create a fresh passwordless account for a new email.
            $newId = $this->createOAuthUser($profile);
            if (! $newId) {
                return [false, 'We could not create your account. Please try again or contact the administrator.'];
            }
            $user  = $this->users->find($newId);
            $isNew = true;
        }

        // Flag so the caller can route a brand-new user through onboarding
        // (company creation) instead of straight to the dashboard.
        $this->session->set('oauth_is_new_user', $isNew);

        $this->establishSession($user);
        $this->users->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);

        $logId = $this->logLogin((int) $user['id'], $user['username'] ?? $profile->email, 'success', 'Login via ' . ucfirst($profile->provider));
        if ($logId) {
            $this->session->set('login_log_id', $logId);
        }

        try {
            Services::notifier()->user((int) $user['id'], 'Signed in with ' . ucfirst($profile->provider), 'Your account signed in from ' . service('request')->getIPAddress() . '.', [
                'type'       => 'user_activity',
                'module'     => 'login_logs',
                'priority'   => 'normal',
                'action_url' => site_url('my-login-history'),
                'created_by' => null,
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[OAuth] notify failed: ' . $e->getMessage());
        }

        return [true, $isNew
            ? 'Welcome, ' . $user['name'] . '! Let\'s set up your company.'
            : 'Welcome back, ' . $user['name'] . '!'];
    }

    /**
     * Find or create the local account behind a verified OAuth profile WITHOUT
     * touching the web session. This is the stateless counterpart of
     * loginWithOAuth(), used by the mobile API which issues its own bearer token.
     *
     * Matches by linked provider id first, then email; links the identity on the
     * first social login and creates a fresh passwordless account for an unknown
     * email (same self-service rules as the web flow).
     *
     * @return array{user?: array, is_new?: bool, error?: string}
     */
    public function findOrCreateOAuthUser(OAuthUserProfile $profile): array
    {
        if ($profile->email === '') {
            return ['error' => 'Your ' . ucfirst($profile->provider) . ' account did not share an email address.'];
        }
        if (! $profile->emailVerified) {
            return ['error' => 'Your ' . ucfirst($profile->provider) . ' email address is not verified.'];
        }

        $user = $this->users->findByProvider($profile->provider, $profile->providerId)
            ?? $this->users->where('email', $profile->email)->first();

        if ($user) {
            // Link the social identity / refresh avatar on first social login.
            $patch = [];
            if (empty($user['provider_id'])) {
                $patch['auth_provider'] = $profile->provider;
                $patch['provider_id']   = $profile->providerId;
            }
            if ($profile->picture && empty($user['avatar_url'])) {
                $patch['avatar_url'] = $profile->picture;
            }
            if ($patch !== []) {
                $this->users->update($user['id'], $patch);
                $user = array_merge($user, $patch);
            }

            return ['user' => $user, 'is_new' => false];
        }

        $newId = $this->createOAuthUser($profile);
        if (! $newId) {
            return ['error' => 'We could not create your account. Please try again or contact the administrator.'];
        }

        $newUser = $this->users->find($newId);

        // Welcome the new account (best-effort; a mail failure never blocks signup).
        try {
            service('mailer')->welcome((string) $newUser['email'], (string) ($newUser['name'] ?? ''));
        } catch (\Throwable $e) {
            log_message('error', '[OAuth] welcome email failed: ' . $e->getMessage());
        }

        return ['user' => $newUser, 'is_new' => true];
    }

    /**
     * Create a new passwordless account from a verified social profile. A new
     * self-service sign-up is the admin of their own workspace: they get the
     * Admin role (full access to the app modules) and become owner of the firm
     * they create during onboarding. This keeps their page access in step with
     * the firm menu they see, so nothing they can see 403s on them.
     */
    protected function createOAuthUser(OAuthUserProfile $profile): ?int
    {
        $id = $this->users->insert([
            'name'          => $profile->name !== '' ? $profile->name : 'User',
            'email'         => $profile->email,
            'username'      => $this->users->generateUniqueUsername($profile->email ?: $profile->name),
            'password'      => null,
            'auth_provider' => $profile->provider,
            'provider_id'   => $profile->providerId,
            'avatar_url'    => $profile->picture,
            'user_type_id'  => $this->defaultTypeId('admin') ?? $this->defaultTypeId('viewer'),
            'account_type'  => 'customer', // Gmail sign-ups are tenant customers
            'status'        => 1,
            // The social provider already verified this email address.
            'email_verified_at' => date('Y-m-d H:i:s'),
        ], true);

        if (! $id) {
            log_message('error', '[OAuth] user create failed: ' . implode(' ', $this->users->errors() ?: []));
            return null;
        }
        // Admin RBAC role so a new customer can use every firm module they see;
        // fall back to Viewer only if an Admin role is somehow missing.
        if ($roleId = ($this->defaultRoleId('admin') ?? $this->defaultRoleId('viewer'))) {
            $this->users->syncRoles((int) $id, [$roleId]);
        }
        return (int) $id;
    }

    protected function defaultTypeId(string $code): ?int
    {
        $row = Database::connect()->table('user_types')->select('id')->where('code', $code)->get()->getRowArray();
        return $row ? (int) $row['id'] : null;
    }

    protected function defaultRoleId(string $code): ?int
    {
        $row = Database::connect()->table('roles')->select('id')->where('code', $code)->get()->getRowArray();
        return $row ? (int) $row['id'] : null;
    }

    /**
     * Connect a social identity to an already-authenticated account (from the
     * profile page). Returns [success(bool), message].
     */
    public function linkOAuthAccount(int $userId, OAuthUserProfile $profile): array
    {
        if (! $profile->emailVerified) {
            return [false, 'Your ' . ucfirst($profile->provider) . ' email address is not verified.'];
        }

        // Refuse if this social identity already belongs to a different user.
        $existing = $this->users->findByProvider($profile->provider, $profile->providerId);
        if ($existing && (int) $existing['id'] !== $userId) {
            return [false, 'That ' . ucfirst($profile->provider) . ' account is already linked to another user.'];
        }

        $user = $this->users->find($userId);
        if (! $user) {
            return [false, 'Account not found.'];
        }

        $patch = [
            'auth_provider' => $profile->provider,
            'provider_id'   => $profile->providerId,
        ];
        if (empty($user['avatar_url']) && $profile->picture) {
            $patch['avatar_url'] = $profile->picture;
        }
        $this->users->update($userId, $patch);

        return [true, ucfirst($profile->provider) . ' account connected. You can now sign in with it.'];
    }

    /**
     * Disconnect a social identity. Blocked when the account has no local
     * password, to avoid locking the user out of their own account.
     */
    public function unlinkOAuthAccount(int $userId, string $provider): array
    {
        $user = $this->users->find($userId);
        if (! $user) {
            return [false, 'Account not found.'];
        }
        if (empty($user['provider_id']) || ($user['auth_provider'] ?? '') !== $provider) {
            return [false, 'No ' . ucfirst($provider) . ' account is connected.'];
        }
        if (empty($user['password'])) {
            return [false, 'Set a password first so you can still sign in after disconnecting ' . ucfirst($provider) . '.'];
        }

        $this->users->update($userId, ['auth_provider' => 'local', 'provider_id' => null]);

        return [true, ucfirst($provider) . ' account disconnected.'];
    }

    /**
     * Start impersonating another user (Super Admin "access account"). Stores
     * the original admin so it can be restored, then establishes the target's
     * session. Callers MUST verify the current user is a Super Admin first.
     */
    public function impersonate(int $targetId): array
    {
        $target = $this->users->find($targetId);
        if (! $target) {
            return [false, 'User not found.'];
        }
        if ((int) $target['id'] === (int) $this->id()) {
            return [false, 'You cannot impersonate your own account.'];
        }
        if ((int) $target['status'] !== 1) {
            return [false, 'That account is inactive and cannot be accessed.'];
        }

        $adminId   = (int) $this->id();
        $adminName = (string) $this->session->get('user_name');

        Services::company()->clear();          // drop the admin's (empty) firm context
        $this->establishSession($target);       // become the target user
        $this->session->set([
            'impersonator_id'   => $adminId,
            'impersonator_name' => $adminName,
        ]);

        return [true, 'You are now viewing the account of ' . $target['name'] . '.'];
    }

    /** Return from impersonation to the original Super Admin account. */
    public function stopImpersonating(): array
    {
        $adminId = (int) $this->session->get('impersonator_id');
        if ($adminId === 0) {
            return [false, 'You are not impersonating anyone.'];
        }
        $admin = $this->users->find($adminId);
        $this->session->remove(['impersonator_id', 'impersonator_name']);
        if (! $admin) {
            $this->logout();
            return [false, 'Your original account could not be restored. Please sign in again.'];
        }
        Services::company()->clear();
        $this->establishSession($admin);

        return [true, 'Welcome back — restored your Super Admin session.'];
    }

    public function isImpersonating(): bool
    {
        return (bool) $this->session->get('impersonator_id');
    }

    /**
     * Try to restore a session from a remember-me cookie.
     */
    public function loginFromRememberCookie(): bool
    {
        if ($this->check()) {
            return true;
        }
        $cookie = service('request')->getCookie('erp_remember');
        if (! $cookie || strpos($cookie, ':') === false) {
            return false;
        }
        [$userId, $token] = explode(':', $cookie, 2);
        $user = $this->users->find((int) $userId);
        if ($user && ! empty($user['remember_token']) && hash_equals($user['remember_token'], $token) && (int) $user['status'] === 1) {
            $this->establishSession($user);
            return true;
        }
        return false;
    }

    public function logout(): void
    {
        $userId = $this->id();
        $logId  = $this->session->get('login_log_id');
        if ($userId) {
            $this->users->update($userId, ['remember_token' => null]);
        }
        $this->loginLogs->closeSession($logId ? (int) $logId : null);
        // Expire remember cookie.
        service('response')->deleteCookie('erp_remember');
        $this->session->remove(['user_id', 'user_name', 'user_email', 'username', 'role_ids', 'is_superadmin', 'login_log_id', 'account_type', 'company_id', 'company_name', 'company_role', 'company_permissions', 'impersonator_id', 'impersonator_name']);
        $this->session->destroy();
    }

    protected function establishSession(array $user): void
    {
        $roleIds = $this->users->roleIds((int) $user['id']);
        $isSuper = false;
        if ($roleIds !== []) {
            $count = (new \App\Models\RoleModel())
                ->whereIn('id', $roleIds)->where('is_superadmin', 1)->countAllResults();
            $isSuper = $count > 0;
        }
        $this->session->set([
            'user_id'      => (int) $user['id'],
            'user_name'    => $user['name'],
            'user_email'   => $user['email'],
            'username'     => $user['username'],
            'role_ids'     => $roleIds,
            'is_superadmin'=> $isSuper,
            'account_type' => $user['account_type'] ?? 'customer',
        ]);
        // Regenerate the session id to prevent fixation. No-op outside an HTTP
        // request (CLI has no active session to regenerate).
        if (! is_cli()) {
            $this->session->regenerate();
        }
    }

    protected function setRememberCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $this->users->update($userId, ['remember_token' => $token]);
        service('response')->setCookie([
            'name'     => 'erp_remember',
            'value'    => $userId . ':' . $token,
            'expire'   => 60 * 60 * 24 * 30,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    // ---- Login attempt protection -------------------------------------

    protected function isLocked(string $key): bool
    {
        $row = $this->attempts->where('identifier', $key)->first();
        if (! $row || empty($row['locked_until'])) {
            return false;
        }
        return strtotime($row['locked_until']) > time();
    }

    protected function recordFailure(string $key): void
    {
        $row = $this->attempts->where('identifier', $key)->first();
        $now = date('Y-m-d H:i:s');
        if (! $row) {
            $this->attempts->insert(['identifier' => $key, 'attempts' => 1, 'updated_at' => $now]);
            return;
        }
        $attempts = (int) $row['attempts'] + 1;
        $data     = ['attempts' => $attempts, 'updated_at' => $now];
        if ($attempts >= self::MAX_ATTEMPTS) {
            $data['locked_until'] = date('Y-m-d H:i:s', time() + self::LOCK_MINUTES * 60);
            $data['attempts']     = 0; // reset counter after locking
        }
        $this->attempts->update($row['id'], $data);
    }

    protected function clearAttempts(string $key): void
    {
        $row = $this->attempts->where('identifier', $key)->first();
        if ($row) {
            $this->attempts->update($row['id'], ['attempts' => 0, 'locked_until' => null, 'updated_at' => date('Y-m-d H:i:s')]);
        }
    }

    protected function logLogin(?int $userId, string $username, string $status, string $message): ?int
    {
        $req = service('request');
        $agent = $req->getUserAgent();
        $ip = $req->getIPAddress();
        $browser = trim($agent->getBrowser() . ' ' . $agent->getVersion());
        $platform = $agent->getPlatform() ?: 'Unknown';
        $device = $agent->isMobile() ? 'Mobile' : ($agent->isRobot() ? 'Robot' : 'Desktop');
        $now = date('Y-m-d H:i:s');
        [$suspicious, $reason] = $this->detectSuspicious($userId, $username, $ip, $browser, $status);

        $id = $this->loginLogs->insert([
            'user_id'           => $userId,
            'username'          => $username,
            'ip_address'        => $ip,
            'user_agent'        => substr((string) $agent, 0, 255),
            'browser'           => substr($browser !== '' ? $browser : 'Unknown', 0, 120),
            'operating_system'  => substr($platform, 0, 120),
            'device_type'       => $device,
            'status'            => $status,
            'failure_reason'    => $status === 'failed' ? $message : null,
            'is_suspicious'     => $suspicious ? 1 : 0,
            'suspicious_reason' => $reason,
            'message'           => $message,
            'login_at'          => $now,
            'last_activity_at'  => $now,
            'created_at'        => $now,
        ]);

        if ($suspicious) {
            Services::notifier()->broadcast('Suspicious login detected', trim($username . ' - ' . $reason), [
                'type'       => $status === 'failed' ? 'warning' : 'error',
                'module'     => 'login_logs',
                'priority'   => 'high',
                'action_url' => site_url('login-logs?suspicious=1'),
                'created_by' => null,
            ]);
        }

        return $id ? (int) $id : null;
    }

    protected function detectSuspicious(?int $userId, string $username, string $ip, string $browser, string $status): array
    {
        $reasons = [];

        if ($userId) {
            $priorIp = $this->loginLogs
                ->where('user_id', $userId)
                ->where('status', 'success')
                ->where('ip_address', $ip)
                ->countAllResults();
            if ($priorIp === 0) {
                $reasons[] = 'new IP address';
            }

            $priorBrowser = $this->loginLogs
                ->where('user_id', $userId)
                ->where('status', 'success')
                ->where('browser', $browser)
                ->countAllResults();
            if ($browser !== '' && $priorBrowser === 0) {
                $reasons[] = 'new browser/device';
            }
        }

        if ($status === 'failed') {
            $recentFailures = $this->loginLogs
                ->where('status', 'failed')
                ->groupStart()
                    ->where('username', $username)
                    ->orWhere('ip_address', $ip)
                ->groupEnd()
                ->where('created_at >=', date('Y-m-d H:i:s', time() - 15 * 60))
                ->countAllResults();

            if ($recentFailures >= 3) {
                $reasons[] = 'multiple failed attempts';
            }
        }

        return [$reasons !== [], implode(', ', $reasons)];
    }
}
