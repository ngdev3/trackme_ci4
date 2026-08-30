<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * ProfileModel — CI4 port of admin/models/Profile_mod (self-profile slice).
 * Operates on the current user's own `users` row: view, update basic fields,
 * set the profile image, and change password (md5-verified against the DB —
 * kept for parity with the CI3 app sharing the live DB; remark stores the
 * plaintext new password, matching CI3 behaviour).
 */
class ProfileModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function details(int $id)
    {
        return $this->db()->table('users')->where('id', $id)->get()->getRow();
    }

    public function update(int $id, array $data): bool
    {
        $this->db()->table('users')->where('id', $id)->update($data);
        return true;
    }

    public function setImage(int $id, string $thumb): array
    {
        if ($id <= 0 || $thumb === '') {
            return ['status' => 'error', 'error_msg' => 'Invalid Request'];
        }
        $this->db()->table('users')->where('id', $id)->update(['profile_image' => $thumb]);
        return ['status' => 'success', 'file_name' => $thumb];
    }

    /**
     * Change password. Verifies the old password (md5) against the DB row, then
     * checks new === confirm. Returns ['status'=>'success'] or ['status'=>'error','error_msg'=>...].
     */
    public function resetPassword(int $id, string $old, string $new, string $confirm): array
    {
        if ($old === '' || $new === '' || $confirm === '') {
            return ['status' => 'error', 'error_msg' => 'All password fields are required.'];
        }
        if ($new !== $confirm) {
            return ['status' => 'error', 'error_msg' => 'New Password and Confirm Password should be same'];
        }
        $row = $this->db()->table('users')->where('id', $id)->get()->getRow();
        if (! $row) {
            return ['status' => 'error', 'error_msg' => 'User not found.'];
        }
        if (md5($old) !== $row->password) {
            return ['status' => 'error', 'error_msg' => 'Please enter correct password'];
        }
        $this->db()->table('users')->where('id', $id)->update([
            'password' => md5($new),
            'remark'   => $new,
        ]);
        return ['status' => 'success'];
    }
}
