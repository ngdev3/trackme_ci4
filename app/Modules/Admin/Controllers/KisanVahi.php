<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\KisanVahiModel;
use App\Modules\Admin\Models\ReportModel;

/** Kisan Vahi — CI4 port: listing + Parcha report. Gated rbac('kisan_vahi'). */
class KisanVahi extends BaseController
{
    protected $helpers = ['url', 'app'];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\kisan_vahi\listing', ['title' => 'Kisan Vahi · C R Industries ERP']);
    }

    /* ===================== Parcha report (read-only) ===================== */

    /** Persist the parcha filters into session, then reopen the report. */
    private function applyParchaFilters(): void
    {
        [$fyStart, $fyEnd] = fy_date_range();
        $s = session();

        if (strtoupper($this->request->getMethod()) === 'POST') {
            $from = fy_clamp_date(date('Y-m-d', strtotime((string) $this->request->getPost('setParchaFromDate'))));
            $to   = fy_clamp_date(date('Y-m-d', strtotime((string) $this->request->getPost('setParchaToDate'))));
            if ($to < $from) { $to = $from; }
            $s->set('setParchaFromDate', $from);
            $s->set('setParchaToDate', $to);
            $s->set('kvParchaSearch', trim((string) $this->request->getPost('kvSearch')));
            $s->set('kvParchaCenter', trim((string) $this->request->getPost('kvCenter')));
            $s->set('kvParchaPfms', trim((string) $this->request->getPost('kvPfms')));
            $s->set('kvParchaPaid', trim((string) $this->request->getPost('kvPaid')));
            return;
        }

        if ((string) $this->request->getGet('reset') === '1') {
            foreach (['setParchaFromDate', 'setParchaToDate', 'kvParchaSearch', 'kvParchaCenter', 'kvParchaPfms', 'kvParchaPaid'] as $k) {
                $s->remove($k);
            }
            return;
        }

        // Seed the window to the whole FY on the very first visit.
        if ($s->get('setParchaFromDate') === null) {
            $s->set('setParchaFromDate', $fyStart !== '' ? $fyStart : date('Y-m-d', strtotime('-2day')));
            $s->set('setParchaToDate', $fyEnd !== '' ? $fyEnd : date('Y-m-d', strtotime('-2day')));
            $s->set('kvParchaSearch', '');
            $s->set('kvParchaCenter', '');
            $s->set('kvParchaPfms', '');
            $s->set('kvParchaPaid', '');
        }
    }

    public function report()
    {
        // POST (filter apply) or GET ?reset=1 → save/clear then redirect (PRG).
        if (strtoupper($this->request->getMethod()) === 'POST' || (string) $this->request->getGet('reset') === '1') {
            $this->applyParchaFilters();
            return redirect()->to(base_url('admin/kisan_vahi/report'));
        }
        $this->applyParchaFilters();

        [$fyStart, $fyEnd] = fy_date_range();
        $m = new ReportModel();
        return _layout('\App\Modules\Admin\Views\kisan_vahi\parcha_report', [
            'title'         => 'Kisan Vahi Parcha Report',
            'kisanVahiData' => $m->kishanVahi_Data_details() ?: [],
            'center_list'   => $m->kv_center_list(),
            'pfms_options'  => $m->kv_pfms_options(),
            'fy_start'      => $fyStart,
            'fy_end'        => $fyEnd,
        ]);
    }

    public function report_csv()
    {
        $this->applyParchaFilters();
        $rows = (new ReportModel())->kishanVahi_Data_details() ?: [];
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Kisan Vahi Parcha Report', trim((string) (fy()->firm_name ?? '')), 'Printed ' . date('d-m-Y H:i')]);
        fputcsv($out, []);
        fputcsv($out, ['#', 'Purchase Date', 'Farmer ID', 'Farmer Name', 'Mobile', 'Center', 'Qty (Qtl)', 'Amount', 'Account', 'Bank', 'IFSC', 'Ack Status', 'PFMS', 'Payment Status', 'UTR No']);
        $i = 0;
        foreach ($rows as $r) {
            $i++;
            $fputrow = [
                $i, $r->Purchase_Date ?? '', $r->Farmer_ID ?? '', $r->Farmer_name ?? '', $r->mobile_no ?? '',
                $r->centern ?? ($r->name ?? ''), $r->Quantity ?? '', $r->Ammount ?? '', $r->name ?? '',
                $r->bank_name ?? '', $r->ifsc_code ?? '', $r->Ack_Status ?? '', $r->PFMS_Status ?? '',
                (isset($r->paid_status) && (int) $r->paid_status === 1) ? 'Paid' : 'Pending', $r->UTR_No ?? '',
            ];
            fputcsv($out, array_map(fn($c) => html_entity_decode(strip_tags((string) $c), ENT_QUOTES, 'UTF-8'), $fputrow));
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $this->response
            ->setContentType('text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="kisan_vahi_parcha_' . date('Ymd_His') . '.csv"')
            ->setBody($csv);
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
