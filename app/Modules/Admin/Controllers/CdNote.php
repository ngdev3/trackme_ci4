<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\CdNoteModel;

/**
 * Credit / Debit Note — CI4 port, listing slice. Gated rbac('cd_note').
 */
class CdNote extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\cdnote\listing', [
            'title' => 'Credit / Debit Note · C R Industries ERP',
        ]);
    }

    public function viewAll()
    {
        $start = (int) ($this->request->getPost('start') ?? 0);
        $model = new CdNoteModel();
        $total = $model->countData();
        $rows  = $model->getData();

        $data = [];
        $j = $start;
        foreach ($rows as $row) {
            $j++;
            $type = (int) ($row->cd_note_type ?? 0) === 1 ? 'Credit Note' : 'Debit Note';
            $data[] = [
                $j,
                esc($row->credit_debit_id ?? $row->tax_invoice_fy_id ?? ''),
                ! empty($row->cd_date) ? esc(date('d/m/Y', strtotime($row->cd_date))) : '-',
                '<span class="label label-' . ((int) ($row->cd_note_type ?? 0) === 1 ? 'success' : 'warning') . '">' . $type . '</span>',
                esc($row->invoice_number ?? ''),
                number_format((float) ($row->cd_amount ?? 0), 2),
                number_format((float) ($row->total_tax_amount ?? 0), 2),
                number_format((float) ($row->cd_cgst ?? 0), 2),
                number_format((float) ($row->cd_sgst ?? 0), 2),
                number_format((float) ($row->cd_igst ?? 0), 2),
                number_format((float) ($row->cd_cess ?? 0), 2),
            ];
        }
        return $this->response->setJSON([
            'draw' => (int) $this->request->getPost('draw'),
            'recordsTotal' => $total, 'recordsFiltered' => $total, 'data' => $data,
        ]);
    }
}
