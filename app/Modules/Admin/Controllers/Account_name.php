<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\AccountNameModel;

/**
 * Account_name — CI4 port of admin/Account_name (account master, LISTING slice).
 * aa_account_name = global trade parties + farmers. Ships the core read path
 * (server-side DataTable) + status toggle + soft delete/restore + inline
 * quick-update. Accounting-group/ledger/GST-verify enrichment is a follow-up
 * (needs the accounting + gstin subsystems). rbac('account_name').
 */
class Account_name extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\accountname\listing', [
            'title'   => 'Account Master · C R Industries ERP',
            'summary' => (new AccountNameModel())->listingSummary(),
        ]);
    }

    public function view_all()
    {
        $model = new AccountNameModel();
        $total = $model->countBillingData();
        $rows  = $model->getBillingData();

        $data = [];
        $j    = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $r) {
            $row = (array) $r;
            $j++;
            $aid = (int) $row['account_id'];

            $gst = trim((string) ($row['purchaser_gst_no'] ?? ''));
            $gstCell = $gst !== ''
                ? esc($gst) . (! empty($row['gst_verified']) ? ' <span class="label label-success">Verified</span>' : '')
                : '<span class="text-muted">No GST</span>';

            $status = (string) ($row['status'] ?? 'Active');
            $statusCell = $status === 'Active' ? '<span class="label label-success">Active</span>'
                : ($status === 'Inactive' ? '<span class="label label-warning">Inactive</span>'
                : '<span class="label label-danger">' . esc($status) . '</span>');

            $src = (isset($row['entry_source']) && strtolower($row['entry_source']) === 'app') ? 'App' : 'Web';
            $farmer = ! empty($row['is_farmer']) ? ' <span class="label label-info">Kisan</span>' : '';

            $data[] = [
                $j,
                '<a href="' . base_url('admin/account_name/view/' . ID_encode($aid)) . '">' . $aid . '</a>',
                esc($row['name'] ?? '') . $farmer,
                esc($row['contact_person_name'] ?? ''),
                $gstCell,
                esc($src),
                $statusCell,
                $this->rowActions($aid, $status),
            ];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    public function updateStatus()
    {
        $id = (int) $this->request->getPost('id');
        if (! $id) { return $this->response->setJSON(['status' => 'error']); }
        (new AccountNameModel())->setStatus($id, (string) $this->request->getPost('status'));
        return $this->response->setJSON(['status' => 'success']);
    }

    public function soft_delete()
    {
        $id = (int) $this->request->getPost('id');
        if (! $id) { return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid account']); }
        $model = new AccountNameModel();
        if ($model->hasRokad($id)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'This account has cash-book entries and cannot be deleted.']);
        }
        $model->softDelete($id);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function restore()
    {
        $id = (int) $this->request->getPost('id');
        if ($id) { (new AccountNameModel())->restore($id); }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function quick_update()
    {
        $id = (int) $this->request->getPost('id');
        if (! $id) { return $this->response->setJSON(['status' => 'error']); }
        (new AccountNameModel())->quickUpdate($id, [
            'name'                  => trim((string) $this->request->getPost('name')),
            'contact_person_name'   => trim((string) $this->request->getPost('contact_person_name')),
            'contact_person_number' => trim((string) $this->request->getPost('contact_person_number')),
            'purchaser_gst_no'      => trim((string) $this->request->getPost('purchaser_gst_no')),
            'status'                => (string) $this->request->getPost('status'),
        ]);
        return $this->response->setJSON(['status' => 'success']);
    }

    private function rowActions(int $id, string $status): string
    {
        $enc = ID_encode($id);
        $a = '<a class="btn btn-xs btn-default" href="' . base_url('admin/account_name/view/' . $enc) . '" title="View"><i class="fa fa-eye"></i></a> ';
        if ($status === 'Active') {
            $a .= '<button class="btn btn-xs btn-warning acc-toggle" data-id="' . $id . '" data-status="Inactive" title="Deactivate"><i class="fa fa-pause"></i></button> ';
        } else {
            $a .= '<button class="btn btn-xs btn-success acc-toggle" data-id="' . $id . '" data-status="Active" title="Activate"><i class="fa fa-check"></i></button> ';
        }
        $a .= '<button class="btn btn-xs btn-danger acc-del" data-id="' . $id . '" title="Delete"><i class="fa fa-trash"></i></button>';
        return '<div class="text-nowrap">' . $a . '</div>';
    }
}
