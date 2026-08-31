<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\KisanregModel;

/** KV Registration — CI4 port: listing + status/center report. Gated rbac('Kisanreg'). */
class Kisanreg extends BaseController
{
    protected $helpers = ['url', 'app'];

    private array $report_views = [
        'status' => 'Status-wise Report',
        'center' => 'Center-wise Report',
    ];

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\kisanreg\listing', ['title' => 'Kisan Registration · C R Industries ERP']);
    }

    /* ===================== Registration report ===================== */

    public function report($view = 'status')
    {
        if (! isset($this->report_views[$view])) { $view = 'status'; }
        $m = new KisanregModel();
        return _layout('\App\Modules\Admin\Views\kisanreg\report', [
            'title'        => 'Kisan Registration Report',
            'report_views' => $this->report_views,
            'active'       => $view,
            'stats'        => $m->summary_stats(),
            'report'       => $this->build_report($view),
            'centers'      => $m->report_centers(),
            'filters'      => ['status' => $this->request->getGet('status'), 'center' => $this->request->getGet('center')],
        ]);
    }

    private function build_report($view): array
    {
        $m = new KisanregModel();
        $i = fn($v) => number_format((int) $v);
        $q = fn($v) => number_format((float) $v, 2);

        if ($view === 'center') {
            $rows = []; $tc = 0; $tq = 0; $tl = 0;
            foreach ($m->report_by_center() as $r) {
                $rows[] = [$r->center, $i($r->cnt), $q($r->qty), $q($r->lqty)];
                $tc += $r->cnt; $tq += $r->qty; $tl += $r->lqty;
            }
            return ['title' => 'Kisan Registrations — Center wise',
                'columns' => ['Center', 'Registrations', 'Total Quantity', 'Left Quantity'],
                'rows' => $rows, 'numeric' => [1, 2, 3],
                'totals' => ['TOTAL', $i($tc), $q($tq), $q($tl)]];
        }

        $rows = []; $tc = 0; $tq = 0; $tl = 0;
        foreach ($m->report_by_status() as $r) {
            $rows[] = [$r->status, $i($r->cnt), $q($r->qty), $q($r->lqty)];
            $tc += $r->cnt; $tq += $r->qty; $tl += $r->lqty;
        }
        return ['title' => 'Kisan Registrations — Status wise',
            'columns' => ['Status', 'Registrations', 'Total Quantity', 'Left Quantity'],
            'rows' => $rows, 'numeric' => [1, 2, 3],
            'totals' => ['TOTAL', $i($tc), $q($tq), $q($tl)]];
    }

    private function report_table_html(array $report): string
    {
        $numeric = $report['numeric'] ?? [];
        $html = '<style>body{font-family:Arial,sans-serif;color:#000;font-size:11px;margin:0;padding:10px;}h3{font-size:14px;margin:0 0 2px;}.sub{font-size:10px;margin:0 0 8px;color:#333;}table{width:100%;border-collapse:collapse;}th,td{border:1px solid #000;padding:4px 6px;font-size:10.5px;}th{background:#eee;font-weight:bold;text-align:center;}td.num{text-align:right;}tfoot td{font-weight:bold;background:#f2f2f2;}</style>';
        $html .= '<h3>' . esc($report['title']) . '</h3>';
        $html .= '<p class="sub">' . esc(fy()->firm_name ?? '') . ' | FY ' . esc(fy()->FY ?? '') . ' | Printed ' . date('d-m-Y H:i') . '</p>';
        $html .= '<table><thead><tr>';
        foreach ($report['columns'] as $c) { $html .= '<th>' . esc($c) . '</th>'; }
        $html .= '</tr></thead><tbody>';
        if (empty($report['rows'])) { $html .= '<tr><td colspan="' . count($report['columns']) . '" style="text-align:center">No records found</td></tr>'; }
        foreach ($report['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $idx => $cell) { $html .= '<td' . (in_array($idx, $numeric, true) ? ' class="num"' : '') . '>' . esc((string) $cell) . '</td>'; }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        if (! empty($report['totals'])) {
            $html .= '<tfoot><tr>';
            foreach ($report['totals'] as $idx => $cell) { $html .= '<td' . (in_array($idx, $numeric, true) ? ' class="num"' : '') . '>' . esc((string) $cell) . '</td>'; }
            $html .= '</tr></tfoot>';
        }
        return $html . '</table>';
    }

    public function report_pdf($view = 'status')
    {
        if (! isset($this->report_views[$view])) { $view = 'status'; }
        $report = $this->build_report($view);
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml('<html><head><meta charset="utf-8"></head><body>' . $this->report_table_html($report) . '</body></html>');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="kisan_reg_' . $view . '_' . date('Ymd_His') . '.pdf"')
            ->setBody($dompdf->output());
    }

    public function report_csv($view = 'status')
    {
        if (! isset($this->report_views[$view])) { $view = 'status'; }
        $report = $this->build_report($view);
        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [$report['title']]);
        fputcsv($out, [trim((string) (fy()->firm_name ?? '')), 'FY ' . (fy()->FY ?? ''), 'Printed ' . date('d-m-Y H:i')]);
        fputcsv($out, []);
        fputcsv($out, $report['columns']);
        $clean = fn($c) => html_entity_decode(strip_tags((string) $c), ENT_QUOTES, 'UTF-8');
        foreach ($report['rows'] as $row) { fputcsv($out, array_map($clean, (array) $row)); }
        if (! empty($report['totals'])) { fputcsv($out, array_map($clean, (array) $report['totals'])); }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);
        return $this->response
            ->setContentType('text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="kisan_reg_' . $view . '_' . date('Ymd_His') . '.csv"')
            ->setBody($csv);
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
