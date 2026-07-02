<?php

namespace Modules\Profile\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class ProfileController extends BaseController
{
    protected string $vns = 'Modules\Profile\Views\\';

    public function index()
    {
        return $this->render('index', [
            'title'      => 'My Profile',
            'breadcrumb' => [['label' => 'My Profile']],
            'row'        => current_user(),
            'errors'     => session()->getFlashdata('errors') ?? [],
        ]);
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

        (new UserModel())->allowValidation(false)->update($id, [
            'name'   => $this->request->getPost('name'),
            'email'  => $this->request->getPost('email'),
            'mobile' => $this->request->getPost('mobile'),
        ]);

        activity_log('Profile', 'Edit', 'Updated own profile');
        return redirect()->to(site_url('profile'))->with('success', 'Profile updated.');
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

        (new UserModel())->allowValidation(false)->update(user_id(), [
            'password' => password_hash((string) $this->request->getPost('new_password'), PASSWORD_DEFAULT),
        ]);

        activity_log('Profile', 'Edit', 'Changed own password');
        return redirect()->to(site_url('profile'))->with('success', 'Password changed.');
    }
}
