<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\BillingRegisterModel;

/**
 * Billing_register — CI4 port of admin/Billing_register (listing slice). The
 * aa_billing register (firm+FY scoped, soft-delete) with server-side DataTable,
 * total-amount tile, account filter, and soft delete. Add/statement deferred.
 */
class Billing_register extends BaseController
{
    protected $helpers = ['url', 'form', 'app'];

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        $model = new BillingRegisterModel();
        return _layout('\App\Modules\Admin\Views\billing_register\listing', [
            'title'        => 'Billing Register · C R Industries ERP',
            'total_amount' => $model->totalAmount(),
            'accounts'     => $model->usedAccounts(),
        ]);
    }

    public function listing_data()
    {
        $model = new BillingRegisterModel();
        $rows  = $model->getData();

        $data = [];
        $i = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $row) {
            $r = (array) $row;
            $i++;
            $data[] = [
                $i,
                esc($r['billing_date'] ?? ''),
                ucfirst(esc($r['billing_type'] ?? '')),
                ucfirst(esc($r['type_of_account'] ?? '')),
                esc($r['purchaser_account_name'] ?? ''),
                esc($r['khata_entry_no'] ?? ''),
                esc($r['challan_no'] ?? ''),
                '₹ ' . number_format((float) ($r['final_amount'] ?? 0), 2),
                $this->statusBadge((string) ($r['status'] ?? '')),
                '<button class="btn btn-xs btn-danger br-del" data-id="' . (int) ($r['billing_id'] ?? 0) . '" title="Delete"><i class="fa fa-trash"></i></button>',
            ];
        }

        return $this->response->setJSON([
            'draw' => (int) $this->request->getPost('draw'),
            'recordsTotal' => $model->countTotal(),
            'recordsFiltered' => $model->countAll(),
            'data' => $data,
        ]);
    }

    public function add()
    {
        $model = new BillingRegisterModel();

        if ($this->request->is('post')) {
            $rules = [
                'billing_date'      => 'trim|required',
                'billing_type'      => 'trim|required',
                'type_of_account'   => 'trim|required',
                'purchaser_account' => 'trim|required',
                'final_amount'      => 'trim|required|numeric',
            ];
            if ($this->validate($rules)) {
                $new_id = $model->addEntry($this->request->getPost());
                if ($new_id) {
                    notify('New <b>Billing</b> entry added', base_url('admin/billing_register/listing'), ['event' => 'added']);
                    session()->setFlashdata('success', 'Billing entry added successfully');
                    flash_toast('Billing entry #' . $new_id . ' added.', 'success', 'Billing Register');
                    return redirect()->to(base_url('admin/billing_register/listing'));
                }
                session()->setFlashdata('error', 'Could not add the billing entry. Please try again.');
            }
        }

        return _layout('\App\Modules\Admin\Views\billing_register\add', [
            'title'      => 'Track (The Rest Accounting Key) || Add Billing',
            'accounts'   => $model->accounts(),
            'validation' => $this->validator,
        ]);
    }

    /**
     * Account-wise statement: pick an account (+ optional date range) and see
     * that party's billing entries with a running balance and totals.
     */
    public function statement()
    {
        $model = new BillingRegisterModel();

        $account_id = (int) $this->request->getGet('account_id');
        $from = $this->request->getGet('from_date');
        $to   = $this->request->getGet('to_date');

        $rows         = [];
        $account_name = '';
        if ($account_id > 0) {
            $rows         = $model->statementRows($account_id, $from, $to);
            $account_name = $model->accountLabel($account_id);
        }

        return _layout('\App\Modules\Admin\Views\billing_register\statement', [
            'title'        => 'Track (The Rest Accounting Key) || Account Statement',
            'accounts'     => $model->usedAccounts(),
            'account_id'   => $account_id,
            'from_date'    => $from,
            'to_date'      => $to,
            'rows'         => $rows,
            'account_name' => $account_name,
        ]);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        if ($id) { (new BillingRegisterModel())->softDelete($id); }
        return $this->response->setJSON(['status' => 'success']);
    }

    private function statusBadge(string $status): string
    {
        $cls = $status === 'Active' ? 'success' : ($status === 'Delete' ? 'danger' : 'default');
        return '<span class="label label-' . $cls . '">' . esc($status ?: 'Active') . '</span>';
    }
}
