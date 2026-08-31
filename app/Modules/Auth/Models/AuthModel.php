<?php

namespace App\Modules\Auth\Models;

use Config\Database;

/**
 * AuthModel — CI4 port of admin/models/Auth_mod::login_authorize().
 * Authenticates by email + md5(password), enforces user_type ∈ {1,2,3} and the
 * Active/Inactive/Delete status gates, updates last_login, and writes an
 * aa_login_detail audit row — identical behaviour to CI3.
 *
 * PASSWORD NOTE: passwords are md5 in the shared DB. We verify md5 and do NOT
 * rehash — CI3 is still live against the same DB and compares md5, so rehashing
 * here would lock users out of CI3. Migrate to password_hash only in P8, after
 * CI3 is retired (roadmap risk R-7).
 */
class AuthModel
{
    protected string $userTable = 'users';

    /**
     * @return array{status:string, result?:object, error_msg?:string}
     */
    public function loginAuthorize(string $email, string $password): array
    {
        $db  = Database::connect();
        $row = $db->table($this->userTable . ' u')->where('u.email', $email)->get()->getRow();

        if (! $row) {
            return ['status' => 'error', 'error_msg' => 'No account found for this email.'];
        }

        if (! in_array((int) $row->user_type, [1, 2, 3], true)) {
            return ['status' => 'error', 'error_msg' => 'This account type cannot sign in here.'];
        }

        if (md5($password) !== $row->password) {
            return ['status' => 'error', 'error_msg' => 'Incorrect email or password.'];
        }

        if ($row->status === 'Inactive') {
            return ['status' => 'error', 'error_msg' => 'Your account has been inactive.'];
        }
        if ($row->status === 'Delete') {
            return ['status' => 'error', 'error_msg' => 'Your account has been deleted! Contact Admin.'];
        }

        // Success — mirror CI3 side effects.
        unset($row->password);
        $loginTime = date('Y-m-d H:i:s');

        $db->table($this->userTable)->where('id', $row->id)->update([
            'last_login' => $loginTime,
        ]);

        if ($db->tableExists('aa_login_detail')) {
            $req = service('request');
            $db->table('aa_login_detail')->insert([
                'REQUEST_URI' => (string) $req->getUri()->getPath(),
                'HTTP_USER_AGENT' => (string) $req->getUserAgent(),
                'REMOTE_ADDR' => (string) $req->getIPAddress(),
                'time_zone' => $loginTime,
                'remark' => 'You are successfully Logged',
                'user_id' => (int) $row->id,
            ]);
        }

        return ['status' => 'success', 'result' => $row];
    }

    /**
     * Forgot-password lookup — CI3 admin/Auth_mod::forgot(). Returns the active
     * super-admin (user_type==1) row for this email, or null.
     */
    public function forgotLookup(string $email): ?object
    {
        $db  = Database::connect();
        $row = $db->table($this->userTable)
            ->where('email', $email)
            ->where('status', 'Active')
            ->get()->getRow();

        if (! $row || (int) $row->user_type !== 1) {
            return null;
        }
        return $row;
    }

    /** Issue a temporary password and force a change at next login (CI3 parity). */
    public function updatePasswordOnForgot(int $userId, string $password): bool
    {
        if ($userId <= 0 || $password === '') {
            return false;
        }
        return (bool) Database::connect()->table($this->userTable)
            ->where('id', $userId)
            ->update([
                'password' => md5($password),
                'token' => '',
                'token_valid' => '',
                'is_reuired_to_change_password' => 1,
                'remark' => $password,
            ]);
    }

    /** Roll back the temporary password if the SMS could not be sent. */
    public function restorePasswordOnForgot(int $userId, string $passwordHash): bool
    {
        if ($userId <= 0 || $passwordHash === '') {
            return false;
        }
        return (bool) Database::connect()->table($this->userTable)
            ->where('id', $userId)
            ->update([
                'password' => $passwordHash,
                'is_reuired_to_change_password' => 0,
                'remark' => '',
            ]);
    }
}
