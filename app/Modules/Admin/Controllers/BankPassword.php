<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\BankPasswordModel;

/** Password Manager (Bank Password vault) — CI4 port, listing slice (metadata only). */
class BankPassword extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\bank_password\listing', ['title' => 'Password Manager · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new BankPasswordModel();
        $total = $model->countData();
        $rows  = $model->getData();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $exp = ! empty($row->login_password_expiry_date) && $row->login_password_expiry_date !== '0000-00-00'
                ? esc(date('d M Y', strtotime($row->login_password_expiry_date))) : '—';
            $data[] = [
                $j,
                esc($row->password_name),
                esc($row->bank_name ?: '—'),
                esc($row->user_login_id ?: '—'),
                esc($row->corp_id ?: '—'),
                $exp,
                (int) ($row->is_common ?? 0) === 1 ? '<span class="label label-info">All firms</span>' : '<span class="label label-default">Current firm</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }
}
