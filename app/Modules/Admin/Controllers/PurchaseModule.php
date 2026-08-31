<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\PurchaseModuleModel;

/** Purchase Module — CI4 port, listing slice. Gated rbac('purchase_module'). */
class PurchaseModule extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\purchase\listing', ['title' => 'Purchase · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new PurchaseModuleModel();
        $total = $model->countData();
        $rows  = $model->getData();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                esc($row->account_name),
                esc($row->invoice_no) . ' || ' . (int) $row->id,
                esc($row->invoice_date),
                esc($row->product_name ?? ''),
                esc($row->hsn_code ?? ''),
                esc($row->weight),
                esc($row->rate),
                esc($row->amount),
                esc($row->vehicle_no),
                $this->badge($row->status),
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }

    private function badge($s): string
    {
        $s = trim((string) $s);
        $cls = strtolower($s) === 'active' || $s === '' ? 'success' : 'default';
        return '<span class="label label-' . $cls . '">' . esc($s ?: 'Active') . '</span>';
    }
}
