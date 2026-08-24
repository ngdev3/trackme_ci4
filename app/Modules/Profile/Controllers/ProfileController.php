<?php

namespace Modules\Profile\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class ProfileController extends BaseController
{
    protected string $vns = 'Modules\Profile\Views\\';

    public function index()
    {
        $oauth = config('OAuth');
        $user  = current_user();

        return $this->render('index', [
            'title'         => 'My Profile',
            'breadcrumb'    => [['label' => 'My Profile']],
            'row'           => $user,
            'score'         => profile_score($user),
            'errors'        => session()->getFlashdata('errors') ?? [],
            'oauthProviders'=> $oauth->providers,
            'oauthEnabled'  => array_filter(
                array_fill_keys(array_keys($oauth->providers), true),
                static fn ($_, $key) => $oauth->enabled($key),
                ARRAY_FILTER_USE_BOTH
            ),
        ]);
    }

    /**
     * File a self-service request to permanently delete this account. Nothing is
     * deleted here — a super admin reviews and approves it (which triggers the
     * full purge via AccountPurgeService). One open request at a time.
     */
    public function requestDeletion()
    {
        $user = current_user();
        if (! $user) {
            return redirect()->to(site_url('login'));
        }
        $requests = new \App\Models\AccountDeletionRequestModel();
        if ($requests->hasPending((int) $user['id'])) {
            return redirect()->to(site_url('profile'))->with('info', 'Your account deletion request is already pending review.');
        }
        $reason = trim((string) $this->request->getPost('reason'));
        $requests->insert([
            'user_id'    => (int) $user['id'],
            'name'       => mb_substr((string) ($user['name'] ?? ''), 0, 150),
            'email'      => mb_substr((string) ($user['email'] ?? ''), 0, 190),
            'mobile'     => isset($user['mobile']) ? mb_substr((string) $user['mobile'], 0, 20) : null,
            'reason'     => $reason !== '' ? mb_substr($reason, 0, 1000) : null,
            'source'     => 'web',
            'status'     => 'pending',
            'ip_address' => $this->request->getIPAddress(),
            'user_agent' => mb_substr((string) $this->request->getUserAgent()->getAgentString(), 0, 255),
        ]);
        activity_log('Profile', 'Add', 'Account deletion requested by user #' . $user['id']);

        return redirect()->to(site_url('profile'))
            ->with('success', 'Your account deletion request has been submitted. Our team will review it and get back to you.');
    }

    public function update()
    {
        $id    = user_id();
        $rules = [
            'name'   => 'required|min_length[2]|max_length[150]',
            'email'  => "required|valid_email|is_unique[users.email,id,{$id}]",
            'mobile' => 'permit_empty|max_length[20]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new UserModel())->skipValidation(true)->update($id, [
            'name'   => $this->request->getPost('name'),
            'email'  => $this->request->getPost('email'),
            'mobile' => $this->request->getPost('mobile'),
        ]);

        activity_log('Profile', 'Edit', 'Updated own profile');
        return redirect()->to(site_url('profile'))->with('success', 'Profile updated.');
    }

    /** Directory (under public/) where user avatars are stored. */
    private const AVATAR_DIR = FCPATH . 'uploads/users/';

    public function uploadAvatar()
    {
        if (! $this->validate([
            'avatar' => [
                'label' => 'Photo',
                'rules' => 'uploaded[avatar]|is_image[avatar]|max_size[avatar,4096]'
                    . '|mime_in[avatar,image/png,image/jpeg,image/jpg,image/webp,image/gif]',
            ],
        ])) {
            return redirect()->to(site_url('profile'))->with('error', implode(' ', $this->validator->getErrors()));
        }

        $file = $this->request->getFile('avatar');
        if (! $file || ! $file->isValid()) {
            return redirect()->to(site_url('profile'))->with('error', 'Upload failed. Please try again.');
        }

        if (! is_dir(self::AVATAR_DIR)) {
            @mkdir(self::AVATAR_DIR, 0755, true);
        }

        $newName = 'u' . user_id() . '_' . bin2hex(random_bytes(6)) . '.' . $file->getExtension();
        $file->move(self::AVATAR_DIR, $newName);

        // Remove the previous uploaded file (if any) so old avatars don't pile up.
        $old = current_user()['profile_image'] ?? '';
        if ($old && is_file(self::AVATAR_DIR . $old)) {
            @unlink(self::AVATAR_DIR . $old);
        }

        (new UserModel())->skipValidation(true)->update(user_id(), ['profile_image' => $newName]);
        activity_log('Profile', 'Edit', 'Updated profile photo');

        return redirect()->to(site_url('profile'))->with('success', 'Profile photo updated.');
    }

    public function removeAvatar()
    {
        $old = current_user()['profile_image'] ?? '';
        if ($old && is_file(self::AVATAR_DIR . $old)) {
            @unlink(self::AVATAR_DIR . $old);
        }
        (new UserModel())->skipValidation(true)->update(user_id(), ['profile_image' => null]);
        activity_log('Profile', 'Edit', 'Removed profile photo');

        return redirect()->to(site_url('profile'))->with('success', 'Profile photo removed.');
    }

    public function changePassword()
    {
        $rules = [
            'current_password'  => 'required',
            'new_password'      => 'required|min_length[8]',
            'confirm_password'  => 'required|matches[new_password]',
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->with('errors', $this->validator->getErrors());
        }

        $user = current_user();
        if (! password_verify((string) $this->request->getPost('current_password'), $user['password'])) {
            return redirect()->back()->with('error', 'Current password is incorrect.');
        }

        (new UserModel())->skipValidation(true)->update(user_id(), [
            'password'             => password_hash((string) $this->request->getPost('new_password'), PASSWORD_DEFAULT),
            'must_change_password' => 0,
        ]);

        activity_log('Profile', 'Edit', 'Changed own password');
        return redirect()->to(site_url('profile'))->with('success', 'Password changed.');
    }
}
