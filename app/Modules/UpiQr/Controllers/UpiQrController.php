<?php

namespace Modules\UpiQr\Controllers;

use App\Controllers\BaseController;
use App\Models\UpiQrPayeeModel;

/**
 * UPI QR Codes (firm portal) — a per-company directory of saved payees (a UPI
 * ID, or a bank Account + IFSC) rendered into scannable UPI QR codes. Shares the
 * `upi_qr_payees` table + model with the mobile app, so both stay in sync.
 */
class UpiQrController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'company'];

    private function model(): UpiQrPayeeModel
    {
        return new UpiQrPayeeModel();
    }

    public function index()
    {
        $cid  = (int) company_id();
        $rows = $cid > 0
            ? $this->model()->where('company_id', $cid)->orderBy('updated_at', 'DESC')->findAll()
            : [];

        return $this->render('index', [
            'title'      => 'Receive Payment',
            'breadcrumb' => [['label' => 'Home', 'url' => site_url('dashboard')], ['label' => 'Receive Payment']],
            'rows'       => $rows,
        ]);
    }

    /** Create or update a payee (form POST → redirect back with a flash). */
    public function save()
    {
        $cid = (int) company_id();
        if ($cid <= 0) {
            return redirect()->to(site_url('upi-qr'))->with('error', 'Select a company first.');
        }
        $req    = $this->request;
        $id     = (int) $req->getPost('id');
        $method = $req->getPost('method') === 'bank' ? 'bank' : 'upi';
        $payee  = trim((string) $req->getPost('payee_name'));
        $label  = trim((string) $req->getPost('label')) ?: $payee;
        $upiId  = trim((string) $req->getPost('upi_id'));
        $acc    = trim((string) $req->getPost('account_number'));
        $ifsc   = strtoupper(trim((string) $req->getPost('ifsc')));

        if ($payee === '') {
            return redirect()->back()->withInput()->with('error', 'Payee name is required.');
        }
        if ($method === 'upi' && ! preg_match('/^[\w.\-]{2,256}@[\w.\-]{2,64}$/', $upiId)) {
            return redirect()->back()->withInput()->with('error', 'A valid UPI ID is required.');
        }
        if ($method === 'bank' && ($acc === '' || $ifsc === '')) {
            return redirect()->back()->withInput()->with('error', 'Account number and IFSC are required.');
        }

        $amount = $req->getPost('amount');
        $amount = ($amount === null || $amount === '' || (float) $amount <= 0) ? null : round((float) $amount, 2);

        $data = [
            'company_id'     => $cid,
            'user_id'        => (int) user_id(),
            'label'          => mb_substr($label, 0, 80),
            'method'         => $method,
            'payee_name'     => mb_substr($payee, 0, 80),
            'upi_id'         => $method === 'upi' ? mb_substr($upiId, 0, 120) : null,
            'bank_name'      => $method === 'bank' ? (mb_substr(trim((string) $req->getPost('bank_name')), 0, 80) ?: null) : null,
            'branch'         => $method === 'bank' ? (mb_substr(trim((string) $req->getPost('branch')), 0, 120) ?: null) : null,
            'city'           => $method === 'bank' ? (mb_substr(trim((string) $req->getPost('city')), 0, 80) ?: null) : null,
            'account_number' => $method === 'bank' ? mb_substr($acc, 0, 30) : null,
            'ifsc'           => $method === 'bank' ? mb_substr($ifsc, 0, 15) : null,
            'amount'         => $amount,
            'note'           => mb_substr(trim((string) $req->getPost('note')), 0, 120) ?: null,
        ];

        $model = $this->model();
        if ($id > 0 && $model->where('company_id', $cid)->find($id)) {
            $model->update($id, $data);
            $msg = 'Payee updated.';
        } else {
            $model->insert($data);
            $msg = 'Payee saved.';
        }

        return redirect()->to(site_url('upi-qr'))->with('success', $msg);
    }

    /** Delete a payee (owned by the active company). */
    public function delete($id = null)
    {
        $cid = (int) company_id();
        $model = $this->model();
        $row = $model->where('company_id', $cid)->find((int) $id);
        if ($row) {
            $model->delete((int) $id);
            return redirect()->to(site_url('upi-qr'))->with('success', 'Payee removed.');
        }
        return redirect()->to(site_url('upi-qr'))->with('error', 'Payee not found.');
    }
}
