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
    protected $helpers = ['url', 'app'];

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
