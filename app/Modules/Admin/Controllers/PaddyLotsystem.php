<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\PaddyLotsystemModel;

/** Paddy Center Challan (Paddy Lot System) — CI4 port, listing slice. Gated rbac('PaddyLotsystem'). */
class PaddyLotsystem extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\paddy_lot_system\listing', ['title' => 'Paddy Lot System · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new PaddyLotsystemModel();
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
                esc($row->type_of_bags ?? ''),
                esc($row->total_bags ?? ''),
                esc($row->quantity ?? ''),
                esc($row->mill_name ?? ''),
                '<span class="label label-' . (strtolower((string) ($row->status ?? '')) === 'accept' ? 'success' : 'default') . '">' . esc($row->status ?? '') . '</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }

    /**
     * Add Paddy Lot — GET renders the form with its dropdowns.
     * Faithful port of CI3 PaddyLotsystem::add() render path. (Submit not ported.)
     */
    public function add()
    {
        $model = new PaddyLotsystemModel();
        $data = [
            'center_list'    => $model->center_list(),
            'get_truck_list' => $model->get_truck_list(),
            'get_driver_list' => $model->get_driver_list(),
            'title'          => 'Track (The Rest Accounting Key) || Add',
        ];
        return _layout('\App\Modules\Admin\Views\paddy_lot_system\add', $data);
    }
}
