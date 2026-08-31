<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\StockModel;

/** Stock — CI4 port, listing slice (stock master + live balance). Gated rbac('stock'). */
class Stock extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\stock\listing', ['title' => 'Stock · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new StockModel();
        $total = $model->countList();
        $rows  = $model->getList();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $bal = $model->currentBalance((int) $row->hsn_code_id);
            $data[] = [
                $j,
                esc($row->product_name),
                esc($row->hsn_code),
                esc($row->opening_stock),
                esc($row->purchase_stock),
                esc($row->production_stock),
                esc($row->sales_stock),
                '<b>' . esc($bal['balance']) . '</b> ' . esc($row->stock_unit),
                '<span class="label label-' . (strtolower((string) $row->status) === 'active' ? 'success' : 'default') . '">' . esc($row->status ?: 'Active') . '</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }
}
