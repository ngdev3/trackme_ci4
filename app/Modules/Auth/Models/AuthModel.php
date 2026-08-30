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
}
