<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\ColdInventoryModel;

/**
 * Cold_inventory — CI4 port of admin/Cold_inventory. Read-only Cold Storage
 * Inventory Control derived from cls_* (see ColdInventoryModel). Overview KPIs +
 * variety/kisan/lot/movement reports with CSV/PDF/print export.
 */
class Cold_inventory extends BaseController
{
    protected $helpers = ['url', 'app'];

    private array $views = [
        'variety'  => 'Stock Position — Variety wise',
        'kisan'    => 'Stock Position — Kisan wise',
        'lot'      => 'Stock Position — Lot wise (detailed)',
        'movement' => 'Stock Movement Register',
    ];

    private function model(): ColdInventoryModel
    {
        return new ColdInventoryModel();
    }

    public function index()
    {
        return $this->overview();
    }

    public function overview()
    {
        $m = $this->model();
        return _layout('\App\Modules\Admin\Views\cold_inventory\overview', [
            'title'      => 'Track || Cold Storage Inventory',
            'kpi'        => $m->overview_kpis(),
            'by_variety' => $m->position_grouped('variety'),
            'filters'    => $m->current_filters(),
        ]);
    }

    public function report($view = 'variety')
    {
        if (! isset($this->views[$view])) { $view = 'variety'; }
        $m = $this->model();
        return _layout('\App\Modules\Admin\Views\cold_inventory\report', [
            'title'     => 'Track || Cold Inventory — ' . $this->views[$view],
            'views'     => $this->views,
            'active'    => $view,
            'report'    => $this->build_report($view),
            'filters'   => $m->current_filters(),
            'varieties' => $m->varieties(),
            'kisans'    => $m->kisan_dropdown(),
        ]);
    }

    /** Build {title, columns, rows, totals, numeric} for a view. */
    private function build_report($view): array
    {
        $m = $this->model();
        $i = fn($v) => number_format((int) $v);
        $d = fn($v) => ! empty($v) ? date('d-m-Y', strtotime($v)) : '-';

        switch ($view) {
            case 'kisan':
                $rows = []; $tin = 0; $tdel = 0; $tbal = 0;
                foreach ($m->position_grouped('kisan') as $g) {
                    $rows[] = [$g->label, $i($g->lots), $i($g->in), $i($g->delivered), $i($g->balance)];
                    $tin += $g->in; $tdel += $g->delivered; $tbal += $g->balance;
                }
                return ['title' => 'Stock Position — Kisan wise',
                    'columns' => ['Kisan', 'Lots in Store', 'Packets In', 'Delivered', 'Balance in Store'],
                    'rows' => $rows, 'numeric' => [1, 2, 3, 4],
                    'totals' => ['TOTAL', $i(count($rows)), $i($tin), $i($tdel), $i($tbal)]];

            case 'lot':
                $rows = [];
                foreach ($m->position_rows(true) as $r) {
                    $rows[] = [$r->variety_name, $r->cls_alias, $r->lot_alias, $r->lot_number,
                        trim($r->kisan_alias . ' — ' . $r->kisan_name), $d($r->inward_supply_date),
                        $i($r->packets_in), $i($r->delivered), $i($r->balance), ($r->employee_name ?: '-')];
                }
                $kpi = $m->overview_kpis();
                return ['title' => 'Stock Position — Lot wise (in store)',
                    'columns' => ['Variety', 'Cold Lot', 'Lot', 'Lot No.', 'Kisan', 'Inward Date', 'Packets In', 'Delivered', 'Balance', 'Received By'],
                    'rows' => $rows, 'numeric' => [6, 7, 8],
                    'totals' => ['TOTAL', '', '', '', '', '', $i($kpi->in), $i($kpi->delivered), $i($kpi->balance), '']];

            case 'movement':
                $rows = []; $run = 0; $tin = 0; $tout = 0;
                foreach ($m->movements() as $e) {
                    $run += (int) $e->in - (int) $e->out;
                    $tin += (int) $e->in; $tout += (int) $e->out;
                    $badge = $e->type === 'IN' ? 'IN (received)' : 'OUT (delivered)';
                    $rows[] = [$d($e->dt), $badge, $e->ref, $e->kisan, $e->variety,
                        $e->in ? $i($e->in) : '', $e->out ? $i($e->out) : '', $i($run)];
                }
                return ['title' => 'Stock Movement Register',
                    'columns' => ['Date', 'Type', 'Reference', 'Kisan', 'Variety', 'In (+)', 'Out (-)', 'Balance'],
                    'rows' => $rows, 'numeric' => [5, 6, 7],
                    'totals' => ['TOTAL', '', '', '', '', $i($tin), $i($tout), $i($tin - $tout)]];

            case 'variety':
            default:
                $rows = []; $tin = 0; $tdel = 0; $tbal = 0;
                foreach ($m->position_grouped('variety') as $g) {
                    $rows[] = [$g->label, $i($g->lots), $i($g->in), $i($g->delivered), $i($g->balance)];
                    $tin += $g->in; $tdel += $g->delivered; $tbal += $g->balance;
                }
                return ['title' => 'Stock Position — Variety wise',
                    'columns' => ['Variety', 'Lots in Store', 'Packets In', 'Delivered', 'Balance in Store'],
                    'rows' => $rows, 'numeric' => [1, 2, 3, 4],
                    'totals' => ['TOTAL', $i(count($rows)), $i($tin), $i($tdel), $i($tbal)]];
        }
    }

    private function report_table_html(array $report): string
    {
        $css = '<style>
            body{font-family:Arial,sans-serif;color:#000;font-size:11px;margin:0;padding:10px;}
            h3{font-size:14px;margin:0 0 2px;} .sub{font-size:10px;margin:0 0 8px;color:#333;}
            table{width:100%;border-collapse:collapse;} th,td{border:1px solid #000;padding:3px 5px;font-size:10.5px;}
            th{background:#eee;font-weight:bold;text-align:center;} td.num{text-align:right;white-space:nowrap;}
            tfoot td{font-weight:bold;background:#f2f2f2;}
        </style>';
        $f = $this->model()->current_filters();
        $meta = ' | As on ' . date('d-m-Y', strtotime($f['as_on']));
        if (! empty($f['from']) && ! empty($f['to'])) { $meta = ' | ' . $f['from'] . ' to ' . $f['to']; }
        $numeric = $report['numeric'] ?? [];

        $html  = $css . '<h3>' . esc($report['title']) . '</h3>';
        $html .= '<p class="sub">' . esc(fy()->firm_name ?? '') . ' | Printed ' . date('d-m-Y H:i') . esc($meta) . '</p>';
        $html .= '<table><thead><tr>';
        foreach ($report['columns'] as $c) { $html .= '<th>' . esc($c) . '</th>'; }
        $html .= '</tr></thead><tbody>';
        if (empty($report['rows'])) {
            $html .= '<tr><td colspan="' . count($report['columns']) . '" style="text-align:center;">No stock records found</td></tr>';
        }
        foreach ($report['rows'] as $row) {
            $html .= '<tr>';
            foreach ($row as $idx => $cell) {
                $cls = in_array($idx, $numeric, true) ? ' class="num"' : '';
                $html .= '<td' . $cls . '>' . esc((string) $cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        if (! empty($report['totals'])) {
            $html .= '<tfoot><tr>';
            foreach ($report['totals'] as $idx => $cell) {
                $cls = in_array($idx, $numeric, true) ? ' class="num"' : '';
                $html .= '<td' . $cls . '>' . esc((string) $cell) . '</td>';
            }
            $html .= '</tr></tfoot>';
        }
        return $html . '</table>';
    }

    public function report_pdf($view = 'variety')
    {
        if (! isset($this->views[$view])) { $view = 'variety'; }
        $report = $this->build_report($view);
        $html = '<html><head><meta charset="utf-8"></head><body>' . $this->report_table_html($report) . '</body></html>';

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', count($report['columns']) > 7 ? 'landscape' : 'portrait');
        $dompdf->render();

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'attachment; filename="cold_inventory_' . $view . '_' . date('Ymd_His') . '.pdf"')
            ->setBody($dompdf->output());
    }

    public function report_csv($view = 'variety')
    {
        if (! isset($this->views[$view])) { $view = 'variety'; }
        $report = $this->build_report($view);
        $f = $this->model()->current_filters();
        $meta = 'As on ' . date('d-m-Y', strtotime($f['as_on']));
        if (! empty($f['from']) && ! empty($f['to'])) { $meta = $f['from'] . ' to ' . $f['to']; }

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, [$report['title']]);
        fputcsv($out, [trim((string) (fy()->firm_name ?? '')), 'Printed ' . date('d-m-Y H:i'), $meta]);
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
            ->setHeader('Content-Disposition', 'attachment; filename="cold_inventory_' . $view . '_' . date('Ymd_His') . '.csv"')
            ->setBody($csv);
    }
}
