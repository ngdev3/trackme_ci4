<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\UsersModel;

/**
 * Users — CI4 port of admin/Users (the listing slice).
 * Server-side DataTable of staff accounts with rich grouped cells (avatar/name/
 * email, contact/PAN, role+firm, status pill, last login) + per-row actions
 * (View, Edit, Permissions, Activate/Deactivate, Delete/Restore). `users` is a
 * global table; status ∈ Active/Inactive/Delete. Gated rbac('users').
 *
 * Ported: listing, view_all (DataTables JSON), updateUserStatus, delete.
 * Deferred: add/edit form + view profile (large forms — next increment);
 * "Login as" impersonation (login_as controller not yet ported).
 */
class Users extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\users\listing', [
            'title'      => 'Users · C R Industries ERP',
            'user_stats' => (new UsersModel())->userStats(),
        ]);
    }

    /** DataTables JSON feed — rich grouped cells, CI3-faithful. */
    public function viewAll()
    {
        $model = new UsersModel();
        $total = $model->countBillingData();
        $rows  = $model->getBillingData();

        $palette = ['#2563eb', '#7c3aed', '#0ea5e9', '#059669', '#d97706', '#db2777', '#0891b2', '#4f46e5'];
        $data    = [];
        $j       = (int) ($this->request->getPost('start') ?? 0);

        foreach ($rows as $r) {
            $row = (array) $r;
            $j++;

            $id       = (int) $row['id'];
            $name     = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
            if ($name === '') { $name = '(no name)'; }
            $initials = strtoupper(substr((string) ($row['first_name'] ?? ''), 0, 1) . substr((string) ($row['last_name'] ?? ''), 0, 1));
            if (trim($initials) === '') { $initials = 'U'; }
            $avatarBg = $palette[$id % count($palette)];

            $email  = trim((string) ($row['email'] ?? ''));
            $mobile = trim((string) ($row['mobile'] ?? ''));
            $pan    = trim((string) ($row['pan_number'] ?? ''));
            $firm   = trim((string) ($row['firm_name'] ?? ''));
            $roleTxt = ! empty($row['role_name'])
                ? $row['role_name'] . (! empty($row['job_title']) ? ' · ' . $row['job_title'] : '')
                : 'Type ' . ($row['user_type'] ?? '');

            $userCell = '<div class="u-id">'
                . '<span class="u-avatar" style="background:' . $avatarBg . '">' . esc($initials) . '</span>'
                . '<span class="u-id-text">'
                    . '<span class="u-name">' . esc($name) . '</span>'
                    . ($email !== '' ? '<span class="u-mail"><i class="fa fa-envelope-o"></i> ' . esc($email) . '</span>' : '')
                    . '<span class="u-acc">ID #' . $id . '</span>'
                . '</span></div>';

            $contactCell = '<div class="u-stack">'
                . '<span class="u-line"><i class="fa fa-phone"></i> ' . ($mobile !== '' ? esc($mobile) : '<span class="u-muted">—</span>') . '</span>'
                . '<span class="u-sub">PAN: ' . ($pan !== '' ? esc(strtoupper($pan)) : '—') . '</span></div>';

            $roleCell = '<div class="u-stack">'
                . '<span class="u-badge">' . esc($roleTxt) . '</span>'
                . '<span class="u-sub"><i class="fa fa-building-o"></i> ' . ($firm !== '' ? esc($firm) : '<span class="u-muted">No firm</span>') . '</span></div>';

            $status = (string) ($row['status'] ?? '');
            if ($status === 'Active') {
                $statusCell = '<span class="u-pill on"><i class="fa fa-check-circle"></i> Active</span>';
            } elseif ($status === 'Inactive') {
                $statusCell = '<span class="u-pill off"><i class="fa fa-pause-circle"></i> Inactive</span>';
            } elseif ($status === 'Delete' || $status === 'Deleted') {
                $statusCell = '<span class="u-pill del"><i class="fa fa-trash"></i> Deleted</span>';
            } else {
                $statusCell = '<span class="u-pill">' . esc($status) . '</span>';
            }

            if (! empty($row['last_login'])) {
                $ts       = strtotime($row['last_login']);
                $lastCell = '<div class="u-stack"><span class="u-line">' . date('d M Y', $ts) . '</span><span class="u-sub">' . date('h:i A', $ts) . '</span></div>';
            } else {
                $lastCell = '<span class="u-muted">Never</span>';
            }

            $data[] = [$j, $userCell, $contactCell, $roleCell, $statusCell, $lastCell, $this->rowActions($id, $status)];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    /** Active/Inactive toggle (AJAX). */
    public function updateUserStatus()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error']);
        }
        $id   = (int) $this->request->getPost('id');
        $flag = (string) $this->request->getPost('status');
        (new UsersModel())->updateStatus($id, $flag);
        return $this->response->setJSON(['status' => 'success']);
    }

    /** Delete / restore (soft — sets the status passed by the row action). */
    public function delete()
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return $this->response->setStatusCode(405)->setJSON(['status' => 'error']);
        }
        $id     = (int) $this->request->getPost('id');
        $status = (string) $this->request->getPost('status');
        $status = in_array($status, ['Delete', 'Inactive', 'Active'], true) ? $status : 'Delete';
        (new UsersModel())->setStatus($id, $status);
        return $this->response->setJSON(['status' => 'success']);
    }

    /** Add user — GET renders the form, POST validates + inserts. */
    public function add()
    {
        $model  = new UsersModel();
        $errors = [];

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $errors = $this->validateUser($model, 0);
            if (empty($errors)) {
                $id = $model->create($this->collectUser(true));
                session()->setFlashdata('success', 'User added successfully');
                $this->notifyChange('added', $this->request->getPost('first_name'), $this->request->getPost('email'));
                return redirect()->to(base_url('admin/users/listing'));
            }
        }

        return _layout('\App\Modules\Admin\Views\users\add', [
            'title'      => 'Add User · C R Industries ERP',
            'result'     => null,
            'errors'     => $errors,
            'fy'         => $model->financialYears(),
            'role_types' => $model->roles(),
        ]);
    }

    /** Edit user — GET loads + renders, POST validates + updates. */
    public function edit($enc = null)
    {
        $model  = new UsersModel();
        $id     = (int) ID_decode($enc);
        $user   = $id ? $model->find($id) : null;
        if (! $user) {
            session()->setFlashdata('error', 'User not found');
            return redirect()->to(base_url('admin/users/listing'));
        }
        $errors = [];

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $errors = $this->validateUser($model, $id);
            if (empty($errors)) {
                $model->updateUser($id, $this->collectUser(false));
                session()->setFlashdata('success', 'User updated successfully');
                $this->notifyChange('updated', $this->request->getPost('first_name'), $this->request->getPost('email'));
                return redirect()->to(base_url('admin/users/listing'));
            }
        }

        return _layout('\App\Modules\Admin\Views\users\add', [
            'title'      => 'Edit User · C R Industries ERP',
            'result'     => $user,
            'errors'     => $errors,
            'fy'         => $model->financialYears(),
            'role_types' => $model->roles(),
        ]);
    }

    /** View a user's profile. */
    public function view($enc = null)
    {
        $id   = (int) ID_decode($enc);
        $user = $id ? (new UsersModel())->findWithFirm($id) : null;
        if (! $user) {
            session()->setFlashdata('error', 'User not found');
            return redirect()->to(base_url('admin/users/listing'));
        }
        return _layout('\App\Modules\Admin\Views\users\view', [
            'title' => 'User Profile · C R Industries ERP',
            'users' => $user,
        ]);
    }

    /** Server-side validation mirroring the CI3 form rules. Returns field→msg. */
    private function validateUser(UsersModel $model, int $exceptId): array
    {
        $p = fn($k) => trim((string) $this->request->getPost($k));
        $e = [];
        if ($p('first_name') === '') { $e['first_name'] = 'First name is required.'; }
        if ($p('last_name') === '')  { $e['last_name']  = 'Last name is required.'; }
        if ($p('email') === '') {
            $e['email'] = 'Email is required.';
        } elseif (! filter_var($p('email'), FILTER_VALIDATE_EMAIL)) {
            $e['email'] = 'Enter a valid email.';
        } elseif ($model->emailExists($p('email'), $exceptId)) {
            $e['email'] = 'This email is already in use.';
        }
        if ($exceptId === 0 && $p('password') === '') { $e['password'] = 'Password is required.'; }
        if ($p('mobile') === '')     { $e['mobile']     = 'Mobile is required.'; }
        if ($p('pan_number') === '') { $e['pan_number'] = 'PAN number is required.'; }
        if (strlen($p('address')) < 4) { $e['address'] = 'Address is required (min 4 chars).'; }
        if ($p('user_type') === '')  { $e['user_type']  = 'Select a user type.'; }
        if ($p('status') === '')     { $e['status']     = 'Select a status.'; }
        return $e;
    }

    /** Build the users row from POST. On add, hashes the password (md5, DB-shared parity). */
    private function collectUser(bool $isAdd): array
    {
        $data = [
            'first_name'   => $this->request->getPost('first_name'),
            'last_name'    => $this->request->getPost('last_name'),
            'email'        => $this->request->getPost('email'),
            'mobile'       => $this->request->getPost('mobile'),
            'pan_number'   => $this->request->getPost('pan_number'),
            'address'      => $this->request->getPost('address'),
            'status'       => $this->request->getPost('status'),
            'user_type'    => $this->request->getPost('user_type'),
            'default_firm' => $this->request->getPost('financial_year'),
            'updated_date' => date('Y-m-d'),
        ];
        if ($isAdd) {
            // md5 kept — the CI4 app shares the live DB with CI3 (see tracker R-7).
            $data['password'] = md5((string) $this->request->getPost('password'));
        }
        return $data;
    }

    /** Fire an in-app notification if the helper is available (deferred in CI4). */
    private function notifyChange(string $event, $name, $email): void
    {
        if (function_exists('notify')) {
            $uname = trim((string) $name);
            notify('User <b>' . esc($uname) . '</b> ' . $event . ' · ' . esc((string) $email),
                base_url('admin/users/listing'), ['event' => $event]);
        }
    }

    /** Per-row action buttons (kebab-independent — self-contained). */
    private function rowActions(int $id, string $status): string
    {
        $enc  = ID_encode($id);
        $view = '<a class="btn btn-xs btn-default" href="' . base_url('admin/users/view/' . $enc) . '" title="View"><i class="fa fa-eye"></i></a> ';
        $edit = '<a class="btn btn-xs btn-primary" href="' . base_url('admin/users/edit/' . $enc) . '" title="Edit"><i class="fa fa-pencil"></i></a> ';
        $perm = '<a class="btn btn-xs btn-info" href="' . base_url('admin/user_permissions?user_id=' . $id) . '" title="Permissions"><i class="fa fa-shield"></i></a> ';

        if ($status === 'Active') {
            $toggle = '<button class="btn btn-xs btn-warning usr-toggle" data-id="' . $id . '" data-flag="checked" title="Set inactive"><i class="fa fa-pause"></i></button> ';
        } else {
            $toggle = '<button class="btn btn-xs btn-success usr-toggle" data-id="' . $id . '" data-flag="NotChecked" title="Set active"><i class="fa fa-check"></i></button> ';
        }

        if ($status !== 'Delete' && $status !== 'Deleted') {
            $del = '<button class="btn btn-xs btn-danger usr-del" data-id="' . $id . '" data-status="Delete" title="Delete"><i class="fa fa-trash"></i></button>';
        } else {
            $del = '<button class="btn btn-xs btn-default usr-del" data-id="' . $id . '" data-status="Inactive" title="Restore"><i class="fa fa-undo"></i></button>';
        }

        return '<div class="text-nowrap users-actions">' . $view . $edit . $perm . $toggle . $del . '</div>';
    }
}
