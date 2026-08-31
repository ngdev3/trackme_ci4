<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\KisanregModel;

/** KV Registration — CI4 port, listing slice. Gated rbac('Kisanreg'). */
class Kisanreg extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\kisanreg\listing', ['title' => 'Kisan Registration · C R Industries ERP']);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new KisanregModel();
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
                esc($row->reg_date ?? ''),
                esc($row->Quantity ?? ''),
                esc($row->left_quantity ?? ''),
                '<span class="label label-' . (strtolower((string) ($row->status ?? '')) === 'active' ? 'success' : 'default') . '">' . esc($row->status ?? '') . '</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) $this->request->getPost('draw'), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }
}
