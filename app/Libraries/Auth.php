<?php

namespace App\Libraries;

use App\Models\LoginAttemptModel;
use App\Models\LoginLogModel;
use App\Models\UserModel;
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
        $this->logLogin((int) $user['id'], $login, 'success', 'Login successful');

        return [true, 'Welcome back, ' . $user['name'] . '!'];
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
        if ($userId) {
            $this->users->update($userId, ['remember_token' => null]);
        }
        // Expire remember cookie.
        service('response')->deleteCookie('erp_remember');
        $this->session->remove(['user_id', 'user_name', 'user_email', 'username', 'role_ids', 'is_superadmin']);
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
        ]);
        $this->session->regenerate();
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

    protected function logLogin(?int $userId, string $username, string $status, string $message): void
    {
        $req = service('request');
        $this->loginLogs->insert([
            'user_id'    => $userId,
            'username'   => $username,
            'ip_address' => $req->getIPAddress(),
            'user_agent' => substr((string) $req->getUserAgent(), 0, 255),
            'status'     => $status,
            'message'    => $message,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
