<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\UninvoiceModel;

/**
 * Unregistered Bill of Supply (UBOS) — CI4 port, listing slice.
 * Mirrors the Invoice listing recipe. Gated rbac('uninvoice').
 */
class Uninvoice extends BaseController
{
    protected $helpers = ['url', 'app', 'cr_cache'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\uninvoice\listing', [
            'title'    => 'Unregistered BOS · C R Industries ERP',
            'hsn_list' => get_hsn_code() ?: [],
        ]);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new UninvoiceModel();
        $total = $model->countData();
        $rows  = $model->getData();

        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                '<a target="_blank" href="' . site_url('admin/uninvoice/GeneratePdf/' . ID_encode($row->bos_id)) . '">U' . esc($row->invoice_id) . '</a>',
                esc($row->FY),
                esc($row->billing_date),
                esc($row->account_name) . '_' . esc($row->account_id),
                esc($row->product_name) . ' (' . esc($row->hsn_code) . ')',
                esc($row->quantity),
                esc($row->total_invoice),
                $this->statusBadge($row->status),
            ];
        }
        return $this->response->setJSON([
            'draw' => (int) $this->request->getPost('draw'),
            'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data,
        ]);
    }

    private function statusBadge($status): string
    {
        $s = strtolower(trim((string) $status));
        $map = ['active' => ['Active', 'success'], 'inactive' => ['Cancelled', 'danger'], 'draft' => ['Draft', 'warning']];
        [$label, $cls] = $map[$s] ?? [ucfirst($s ?: 'Active'), 'default'];
        return '<span class="label label-' . $cls . '">' . esc($label) . '</span>';
    }
}
