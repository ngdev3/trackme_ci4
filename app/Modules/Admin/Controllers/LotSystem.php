<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\LotSystemModel;

/** Lot System — CI4 port, listing slice. Gated rbac('lot_system'). */
class LotSystem extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\lot_system\listing', ['title' => 'Lot System · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new LotSystemModel();
        $total = $model->countData();
        $rows  = $model->getData();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                esc($row->lot_number ?? ''),
                esc($row->dispatch_date ?? ''),
                esc($row->center_id ?? ''),
                esc($row->challan_bags_one ?? ''),
                esc($row->challan_weight_one ?? ''),
                '<span class="label label-' . (strtolower((string) ($row->status ?? '')) === 'accept' ? 'success' : 'default') . '">' . esc($row->status ?? '') . '</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }
}
