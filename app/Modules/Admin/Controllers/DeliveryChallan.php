<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\DeliveryChallanModel;

/** Delivery Challan — CI4 port, listing slice. Gated rbac('delivery_challan'). */
class DeliveryChallan extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\delivery_challan\listing', ['title' => 'Delivery Challan · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new DeliveryChallanModel();
        $total = $model->countData();
        $rows  = $model->getData();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                '<a target="_blank" href="' . site_url('admin/delivery_challan/GeneratePdf/' . ID_encode($row->invoice_id)) . '">' . esc($row->invoice_id) . '</a>',
                esc($row->FY),
                esc($row->billing_date),
                esc($row->account_name) . '_' . esc($row->account_id),
                esc($row->quantity),
                esc($row->total_invoice),
                (int) $row->type_of_invoice === 2 ? 'Delivery Challan' : 'Tax Invoice',
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }
}
