<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\BankPasswordModel;

/** Password Manager (Bank Password vault) — CI4 port, listing slice (metadata only). */
class BankPassword extends BaseController
{
    protected $helpers = ['url', 'app', 'form'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\bank_password\listing', ['title' => 'Password Manager · C R Industries ERP']);
    }

    /**
     * Add Credential — CI3 add() 1:1.
     * GET renders the credential form; POST encrypts the secrets, inserts,
     * fires a notification + toast and redirects back to the manager.
     */
    public function add()
    {
        if (strtoupper($this->request->getMethod()) === 'POST') {
            $model = new BankPasswordModel();
            $model->add($this->postedData());

            $bn = (string) $this->request->getPost('bank_name');
            $pn = (string) $this->request->getPost('password_name');
            $detail = 'Bank credential for "' . $bn . '"' . ($pn !== '' ? ' (' . $pn . ')' : '') . ' was added.';
            notify(
                'New bank credential <b>' . esc($bn) . '</b> added' . ($pn !== '' ? ' &middot; ' . esc($pn) : ''),
                base_url('admin/bank_password/listing'),
                ['event' => 'added', 'remark' => $detail]
            );
            flash_toast($detail, 'success', 'Credential added');
            return redirect()->to(base_url('admin/bank_password'))->with('success', 'Credential added successfully');
        }

        return _layout('\App\Modules\Admin\Views\bank_password\add', [
            'title'  => 'Track (The Rest Accounting Key) || Add Credential',
            'result' => null,
        ]);
    }

    /** Password export/print/share history — CI3 history() 1:1. */
    public function history()
    {
        $model = new BankPasswordModel();
        return _layout('\App\Modules\Admin\Views\bank_password\history', [
            'title' => 'Track (The Rest Accounting Key) || Password Export History',
            'logs'  => $model->auditLogs(),
        ]);
    }

    /** Assemble the insert payload from POST, encrypting the two secret fields (CI3 posted_data() 1:1). */
    private function postedData(): array
    {
        $req       = $this->request;
        $isCommon  = $req->getPost('is_common') ? 1 : 0;

        $primary   = trim((string) $req->getPost('login_password'));
        $secondary = trim((string) $req->getPost('transaction_password'));
        $primaryExp   = $req->getPost('login_password_expiry_date');
        $secondaryExp = $req->getPost('transaction_password_expiry_date');

        return [
            'user_login_id'                    => $req->getPost('user_login_id'),
            'corp_id'                          => $req->getPost('corp_id'),
            'bank_name'                        => $req->getPost('bank_name'),
            'bank_url'                         => $req->getPost('bank_url'),
            'login_password'                   => $primary !== '' ? $this->encryptValue($primary) : null,
            'transaction_password'             => $secondary !== '' ? $this->encryptValue($secondary) : null,
            'login_password_expiry_date'       => ! empty($primaryExp) ? $primaryExp : null,
            'transaction_password_expiry_date' => ! empty($secondaryExp) ? $secondaryExp : null,
            'password_type'                    => $req->getPost('password_type'),
            'password_name'                    => $req->getPost('password_name'),
            'notes'                            => $req->getPost('notes'),
            'FY'                               => fy()->FY,
            'template_id'                      => $isCommon ? 0 : fy()->template_id,
            'is_common'                        => $isCommon,
            'added_by'                         => currentuserinfo()->id,
            'status'                           => $req->getPost('status'),
            'updated_date'                     => date('Y-m-d'),
        ];
    }

    /** Encrypt a secret with CI4's Encrypter (CI3 used $this->encryption->encrypt()). */
    private function encryptValue(string $value): string
    {
        return service('encrypter')->encrypt($value);
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
