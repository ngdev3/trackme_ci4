<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\PaymentReceiptModel;

/**
 * Payment Receipt (Purchase from Farmer) — CI4 port, listing slice.
 * Mirrors the Invoice listing recipe. Gated rbac('payment_receipt').
 */
class PaymentReceipt extends BaseController
{
    protected $helpers = ['url', 'app', 'cr_cache'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\paymentreceipt\listing', [
            'title'    => 'Payment Receipt · C R Industries ERP',
            'hsn_list' => get_hsn_code() ?: [],
        ]);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new PaymentReceiptModel();
        $total = $model->countData();
        $rows  = $model->getData();

        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                '<a target="_blank" href="' . site_url('admin/payment_receipt/GeneratePdf/' . ID_encode($row->bos_id)) . '">'
                    . esc($row->invoice_id) . ' || ' . esc($row->bos_id) . '</a>',
                esc($row->billing_date),
                esc($row->account_name) . '_' . esc($row->account_id),
                esc($row->hsn_code) . ' - ' . esc($row->product_name),
                esc($row->rate),
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
