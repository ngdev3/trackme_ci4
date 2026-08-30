<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\ProfileModel;

/**
 * Profile — CI4 port of admin/Profile (self-profile slice).
 * The logged-in user manages their OWN account: view + update basic details,
 * change the profile photo (AJAX upload → 200×200 thumbnail), and reset their
 * password (md5-verified, then forced re-login). Gated rbac('profile').
 *
 * Ported: index (view/update), changeImage, reset_password.
 * Skipped: save_ajax + update_profile_data — dead advertiser-flow legacy
 * (organisation/state/city fields for an ad-network profile this ERP never uses).
 */
class Profile extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];

    /** View + update the current user's profile. */
    public function index()
    {
        $model = new ProfileModel();
        $uid   = (int) (currentuserinfo()->id ?? 0);

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $errors = $this->validateBasic();
            if (empty($errors)) {
                $model->update($uid, [
                    'first_name' => $this->request->getPost('first_name'),
                    'last_name'  => $this->request->getPost('last_name'),
                    'mobile'     => $this->request->getPost('mobile'),
                    'pan_number' => $this->request->getPost('pan_number'),
                    'address'    => $this->request->getPost('address'),
                ]);
                session()->setFlashdata('success', 'Profile Updated successfully. Updated data will reflect after logout and login again.');
                return redirect()->to(base_url('admin/profile'));
            }
            session()->setFlashdata('error', implode(' ', $errors));
        }

        $user = $model->details($uid);
        return _layout('\App\Modules\Admin\Views\profile\profile', [
            'title'     => 'Profile · C R Industries ERP',
            'user_data' => $user,
            'user'      => $user,
        ]);
    }

    /** AJAX profile-photo upload → 200×200 thumbnail in public/uploads/profile_image/. */
    public function changeImage()
    {
        $uid  = (int) (currentuserinfo()->id ?? 0);
        $file = $this->request->getFile('userfile');

        if (! $file || ! $file->isValid() || strpos((string) $file->getMimeType(), 'image/') !== 0) {
            return $this->response->setJSON(['status' => 'error', 'error_msg' => 'Please choose a valid image (PNG/JPG/GIF).']);
        }

        $ext   = strtolower($file->getClientExtension() ?: $file->getExtension() ?: 'png');
        $thumb = md5(time() . '_' . $uid) . '_thumb.' . $ext;
        $dir   = FCPATH . 'uploads/profile_image/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        try {
            $file->move($dir, $thumb, true);
            \Config\Services::image('gd')->withFile($dir . $thumb)->fit(200, 200, 'center')->save($dir . $thumb);
        } catch (\Throwable $e) {
            return $this->response->setJSON(['status' => 'error', 'error_msg' => 'Upload failed. Try again.']);
        }

        $res = (new ProfileModel())->setImage($uid, $thumb);
        if ($res['status'] === 'success') {
            $info = session()->get('userinfo');
            if (is_object($info)) {
                $info->profile_image = $thumb;
                session()->set('userinfo', $info);
            }
        }
        return $this->response->setJSON($res);
    }

    /** GET: render reset-password form. POST: change password → force re-login. */
    public function reset_password()
    {
        $uid = (int) (currentuserinfo()->id ?? 0);

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $res = (new ProfileModel())->resetPassword(
                $uid,
                (string) $this->request->getPost('password'),
                (string) $this->request->getPost('new_password'),
                (string) $this->request->getPost('confirm_password')
            );
            if ($res['status'] === 'success') {
                session()->destroy();
                return redirect()->to(base_url('admin/auth'))->with('success', 'Password changed successfully. Please log in again.');
            }
            session()->setFlashdata('error', $res['error_msg'] ?? 'Password change failed.');
            return redirect()->to(base_url('admin/profile/reset_password'));
        }

        return _layout('\App\Modules\Admin\Views\profile\reset_password', [
            'title' => 'Reset Password · C R Industries ERP',
        ]);
    }

    /** Basic-details validation (CI3 index() rules). Returns field→msg list. */
    private function validateBasic(): array
    {
        $p = fn($k) => trim((string) $this->request->getPost($k));
        $e = [];
        if ($p('first_name') === '') { $e['first_name'] = 'First name is required.'; }
        if ($p('last_name') === '')  { $e['last_name']  = 'Last name is required.'; }
        $m = $p('mobile');
        if ($m === '' || strlen($m) < 7 || strlen($m) > 15) { $e['mobile'] = 'Enter a valid mobile (7–15 digits).'; }
        if ($p('pan_number') === '') { $e['pan_number'] = 'PAN number is required.'; }
        if ($p('address') === '')    { $e['address']    = 'Address is required.'; }
        return $e;
    }
}
