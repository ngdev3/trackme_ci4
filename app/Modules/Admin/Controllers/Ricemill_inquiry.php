<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\RicemillModel;

/**
 * Ricemill_inquiry — CI4 port of admin/Ricemill_inquiry. Manages public
 * rice-mill website inquiries (aa_ricemill_inquiry): status workflow, follow-up
 * remarks, soft delete. rbac via the report/website area.
 */
class Ricemill_inquiry extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\ricemill\listing', [
            'title'  => 'Rice Mill Website Inquiries · C R Industries ERP',
            'counts' => (new RicemillModel())->statusCounts(),
        ]);
    }

    public function view_all()
    {
        $model  = new RicemillModel();
        $status = (string) $this->request->getGet('status');
        $rows   = $model->getInquiry($status);
        $total  = $model->countInquiry();

        $badge = ['New' => '#1769c2', 'Contacted' => '#e0a92e', 'Converted' => '#1f9d70', 'Rejected' => '#e5484d'];
        $data  = [];
        $j     = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $r) {
            $j++;
            $who     = '<strong>' . esc($r->name) . '</strong><br><small><a href="tel:' . esc($r->mobile_no, 'attr') . '">' . esc($r->mobile_no) . '</a></small>';
            $product = esc($r->product) . (trim((string) $r->quantity) !== '' ? '<br><small>Qty: ' . esc($r->quantity) . '</small>' : '');
            $color   = $badge[$r->status] ?? '#777';
            $sBadge  = '<span class="badge" style="background:' . $color . ';color:#fff;">' . esc($r->status) . '</span>';
            $when    = ! empty($r->created_at) ? date('d-M-Y H:i', strtotime($r->created_at)) : '-';
            $data[]  = [$j, $who, esc($r->address) ?: '-', $product, $sBadge, $when, $this->rowActions((int) $r->id, (string) $r->status)];
        }

        return $this->response->setJSON([
            'draw' => (int) $this->request->getPost('draw'),
            'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data,
        ]);
    }

    public function update_status()
    {
        $id = (int) $this->request->getPost('id');
        (new RicemillModel())->updateStatus($id, (string) $this->request->getPost('status'), (int) (currentuserinfo()->id ?? 0) ?: null);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function add_remark()
    {
        $id = (int) $this->request->getPost('id');
        $name = trim((string) (currentuserinfo()->first_name ?? '') . ' ' . (currentuserinfo()->last_name ?? ''));
        (new RicemillModel())->addRemark($id, (string) $this->request->getPost('remark'), $name, (int) (currentuserinfo()->id ?? 0) ?: null);
        return $this->response->setJSON(['status' => 'success']);
    }

    public function delete()
    {
        $id = (int) $this->request->getPost('id');
        (new RicemillModel())->softDelete($id, (int) (currentuserinfo()->id ?? 0) ?: null);
        return $this->response->setJSON(['status' => 'success']);
    }

    private function rowActions(int $id, string $status): string
    {
        $sel = '<select class="ri-status" data-id="' . $id . '" style="font-size:11px;">';
        foreach (RicemillModel::$STATUSES as $s) {
            $sel .= '<option value="' . $s . '"' . ($s === $status ? ' selected' : '') . '>' . esc($s) . '</option>';
        }
        $sel .= '</select> ';
        $sel .= '<button class="btn btn-xs btn-danger ri-del" data-id="' . $id . '" title="Delete"><i class="fa fa-trash"></i></button>';
        return '<div class="text-nowrap">' . $sel . '</div>';
    }
}
