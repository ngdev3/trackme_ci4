<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\KisanVahiModel;

/** Kisan Vahi — CI4 port, listing slice. Gated rbac('kisan_vahi'). */
class KisanVahi extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\kisan_vahi\listing', ['title' => 'Kisan Vahi · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new KisanVahiModel();
        $total = $model->countData();
        $rows  = $model->getData();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                esc($row->Farmer_name ?? ''),
                esc($row->Farmer_ID ?? ''),
                esc($row->mobile_no ?? ''),
                esc($row->Purchase_Date ?? ''),
                esc($row->Quantity ?? ''),
                esc($row->Ammount ?? ''),
                esc($row->status_rec ?? ''),
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }
}
