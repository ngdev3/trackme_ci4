<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\InvoiceModel;

/**
 * Invoice (Bill of Supply) — CI4 port of admin/Invoice listing slice.
 * Renders the listing inside the Metronic shell and feeds a server-side
 * DataTable from viewAll() (CI3 view_all). Read-only for now; add/edit/delete/
 * PDF/ZIP port next. Gated by adminAuth + fyContext + rbac('invoice').
 */
class Invoice extends BaseController
{
    protected $helpers = ['url', 'app', 'cr_cache'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\invoice\listing', [
            'title'    => 'Bill of Supply · C R Industries ERP',
            'hsn_list' => get_hsn_code() ?: [],
        ]);
    }

    /** Server-side DataTables JSON feed (CI3 view_all). */
    public function viewAll()
    {
        $req   = $this->request;
        $draw  = (int) $req->getPost('draw');
        $start = (int) ($req->getPost('start') ?? 0);

        $model = new InvoiceModel();
        $total = $model->countBillingData();
        $rows  = $model->getBillingData();

        $data = [];
        $j    = $start;
        foreach ($rows as $row) {
            $j++;
            $data[] = [
                $j,
                '<a target="_blank" href="' . site_url('admin/invoice/GeneratePdf/' . ID_encode($row->bos_id)) . '">'
                    . esc($row->invoice_id) . ' || ' . esc($row->bos_id) . '</a>',
                esc($row->billing_date),
                esc($row->account_name) . '_' . esc($row->account_id),
                esc($row->hsn_code) . ' - ' . esc($row->product_name),
                esc($row->rate),
                esc($row->quantity),
                esc($row->total_invoice),
                $this->billStatusBadge($row->status),
                $this->actionMenu($row),
            ];
        }

        return $this->response->setJSON([
            'draw'            => $draw,
            'recordsTotal'    => $total,
            'recordsFiltered' => $total,
            'data'            => $data,
        ]);
    }

    /**
     * Live stock-availability check for the invoice form (CI3 stock_balance).
     * Returns the current available balance for the selected product — the
     * strict stock guard that prevents overselling. Read-only.
     */
    public function stock_balance()
    {
        $bal = (new \App\Modules\Admin\Models\StockModel())->currentBalance((int) $this->request->getPost('hsn_code_id'));
        return $this->response->setJSON([
            'status'  => 'success',
            'balance' => $bal['balance'],
            'product' => $bal['product'],
            'unit'    => $bal['unit'],
        ]);
    }

    /** Stream the themed Bill-of-Supply PDF (CI3 GeneratePdf). */
    public function GeneratePdf($event = null)
    {
        $bosId   = (int) ID_decode($event);
        $invoice = (new InvoiceModel())->getInvoiceDetails($bosId);
        if (! $invoice) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound('Invoice not found.');
        }

        $html = view('\App\Modules\Admin\Views\invoice\pdf_bos', [
            'invoice_data' => $invoice,
            'firm'         => getFirmDetails(),
            'pdf_doc'      => ['module' => 'invoice', 'title' => 'BILL OF SUPPLY'],
        ]);

        $dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => false, 'isHtml5ParserEnabled' => true]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $name = ($invoice['invoice_id'] ?? 'BOS') . '_' . ($invoice['contact_person_name'] ?? '') . '_' . ($invoice['billing_date'] ?? '') . '.pdf';
        $name = preg_replace('/[^0-9A-Za-z._-]+/', '_', $name);

        // Clear any buffered output so the binary PDF is not corrupted.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        $dompdf->stream($name, ['Attachment' => false]);
        exit;
    }

    private function billStatusBadge($status): string
    {
        $s = strtolower(trim((string) $status));
        $map = [
            'active'   => ['Active', 'success'],
            'inactive' => ['Cancelled', 'danger'],
            'draft'    => ['Draft', 'warning'],
        ];
        [$label, $cls] = $map[$s] ?? [ucfirst($s ?: 'Active'), 'default'];
        return '<span class="label label-' . $cls . '">' . esc($label) . '</span>';
    }

    private function actionMenu($row): string
    {
        $enc = ID_encode($row->bos_id);
        return '<div class="btn-group">'
            . '<button class="btn btn-xs btn-default dropdown-toggle" data-toggle="dropdown">Actions <span class="caret"></span></button>'
            . '<ul class="dropdown-menu dropdown-menu-right">'
            . '<li><a target="_blank" href="' . site_url('admin/invoice/GeneratePdf/' . $enc) . '"><i class="fa fa-file-pdf-o"></i> Download PDF</a></li>'
            . '<li><a href="' . site_url('admin/invoice/view/' . $enc) . '"><i class="fa fa-eye"></i> View</a></li>'
            . '</ul></div>';
    }
}
