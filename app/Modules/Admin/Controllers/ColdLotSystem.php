<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use Config\Database;

/** Cold Lot System — CI4 port, listing slice (cls_cold_lot). Gated rbac('cold_lot_system'). */
class ColdLotSystem extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\cold_lot_system\listing', ['title' => 'Cold Lot System · C R Industries ERP']);
    }

    public function viewAll()
    {
        $post  = $this->request->getPost();
        $start = (int) ($post['start'] ?? 0);
        $b = Database::connect()->table('cls_cold_lot cl')
            ->select('cl.*, k.kisan_name')
            ->join('cls_kisan k', 'k.id = cl.kisan_id', 'left')
            ->where('cl.template_id', fy()->template_id)->where('cl.FY', fy()->FY)
            ->where("COALESCE(cl.status,'') != 'Delete'", null, false)->orderBy('cl.id', 'desc');
        $total = (clone $b)->countAllResults(false);
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], $start);
        }
        $rows = $b->get()->getResult();
        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [$j, esc(($row->alias_id ?? '') . '/' . ($row->alias_year ?? '')), esc($row->kisan_name ?? ('Kisan #' . ($row->kisan_id ?? ''))), esc($row->total_packets ?? ''), esc($row->grand_total ?? ''), '<span class="label label-' . (strtolower((string) $row->status) === 'active' ? 'success' : 'default') . '">' . esc($row->status ?: 'Active') . '</span>'];
        }
        return $this->response->setJSON(['draw' => (int) ($post['draw'] ?? 0), 'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data]);
    }
}
