<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\TaxinvoiceModel;

/**
 * Tax Invoice — CI4 port, listing slice (primary flow). Gated rbac('taxinvoice').
 */
class Taxinvoice extends BaseController
{
    protected $helpers = ['url', 'app', 'cr_cache'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\taxinvoice\listing', [
            'title' => 'Tax Invoice · C R Industries ERP',
        ]);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new TaxinvoiceModel();
        $total = $model->countData();
        $rows  = $model->getData();

        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                '<a target="_blank" href="' . site_url('admin/taxinvoice/DownloadGeneratePdf/' . ID_encode($row->tax_invoice_id)) . '">' . esc($row->tax_invoice_fy_id) . '</a>',
                esc($row->account_name),
                esc($row->billing_date),
                esc($row->quantity),
                esc($row->total_invoice),
                ((int) ($row->is_einvoice ?? 0) === 1) ? '<span class="label label-info">E-Invoice</span>' : '<span class="label label-default">Tax Invoice</span>',
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
