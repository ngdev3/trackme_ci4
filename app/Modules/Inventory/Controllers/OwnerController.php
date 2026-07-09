<?php

namespace Modules\Inventory\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Exporter;
use App\Libraries\InventoryReport;

/**
 * Task 8 — Owner Dashboard & Reports. A dedicated owner view of the whole
 * operation (current inventory, value, today's flow, utilisation, old stock,
 * user activity) plus nine drill-down reports, each exportable to PDF / Excel /
 * CSV and printable.
 */
class OwnerController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company', 'format'];

    protected string $moduleCode = 'inventory';

    /** key => [label, icon, needsDateRange]. */
    private const REPORTS = [
        'product'   => ['Product-wise Inventory',   'bi-box-seam',        false],
        'warehouse' => ['Warehouse-wise Inventory', 'bi-buildings',       false],
        'party'     => ['Party-wise Inventory',     'bi-people',          false],
        'lot'       => ['Lot-wise Inventory',       'bi-stack',           false],
        'inward'    => ['Daily Inward Report',      'bi-box-arrow-in-down', true],
        'outward'   => ['Daily Outward Report',     'bi-box-arrow-up',    true],
        'difference'=> ['Stock Difference Report',  'bi-arrow-left-right', true],
        'pending'   => ['Pending Approval Report',  'bi-hourglass-split', false],
        'movement'  => ['Inventory Movement Report','bi-list-columns',    true],
    ];

    private function cid(): ?int
    {
        return company_id();
    }

    private function canApprove(): bool
    {
        return is_super_admin_account() || in_array(company_role(), ['owner', 'admin'], true);
    }

    // =================================================================
    // Dashboard
    // =================================================================
    public function dashboard()
    {
        $cid  = $this->cid();
        $data = (new InventoryReport())->dashboard($cid);

        return $this->render('owner_dashboard', [
            'title'      => 'Owner Dashboard',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Owner Dashboard']],
            'd'          => $data,
            'reports'    => self::REPORTS,
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    // =================================================================
    // Reports
    // =================================================================
    public function reports()
    {
        return $this->render('reports_index', [
            'title'      => 'Inventory Reports',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Reports']],
            'reports'    => self::REPORTS,
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    public function report(string $key = '')
    {
        if (! isset(self::REPORTS[$key])) {
            return redirect()->to(site_url('inventory/reports'))->with('error', 'Unknown report.');
        }
        [$range, $report] = $this->build($key);

        return $this->render('report', [
            'title'      => self::REPORTS[$key][0],
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Reports', 'url' => site_url('inventory/reports')], ['label' => self::REPORTS[$key][0]]],
            'key'        => $key,
            'label'      => self::REPORTS[$key][0],
            'needsRange' => self::REPORTS[$key][2],
            'range'      => $range,
            'report'     => $report,
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    public function export(string $key = '', string $format = 'csv')
    {
        if (! isset(self::REPORTS[$key])) {
            return redirect()->to(site_url('inventory/reports'))->with('error', 'Unknown report.');
        }
        [$range, $report] = $this->build($key);
        $label = self::REPORTS[$key][0];
        $name  = 'inventory_' . $key . '_' . date('Ymd_His');

        // Build the export matrix (rows + optional totals line).
        $matrix = $report['rows'];
        if (! empty($report['totals'])) {
            $matrix[] = $report['totals'];
        }

        return match ($format) {
            'xlsx' => Exporter::xlsx($name, $label, $report['columns'], $matrix),
            'pdf'  => Exporter::pdf($name, view('Modules\Inventory\Views\report_print', [
                'label' => $label, 'report' => $report, 'range' => $range, 'needsRange' => self::REPORTS[$key][2],
                'firm' => function_exists('current_company') ? current_company() : null, 'pdf' => true,
            ]), 'landscape'),
            default => Exporter::csv($name, $report['columns'], $matrix),
        };
    }

    public function printReport(string $key = '')
    {
        if (! isset(self::REPORTS[$key])) {
            return redirect()->to(site_url('inventory/reports'))->with('error', 'Unknown report.');
        }
        [$range, $report] = $this->build($key);

        return view('Modules\Inventory\Views\report_print', [
            'label'      => self::REPORTS[$key][0],
            'report'     => $report,
            'range'      => $range,
            'needsRange' => self::REPORTS[$key][2],
            'firm'       => function_exists('current_company') ? current_company() : null,
            'pdf'        => false,
        ]);
    }

    // =================================================================
    // Builder — resolve the dataset for a report key
    // =================================================================

    /** @return array{0: array{from:string,to:string}, 1: array} */
    private function build(string $key): array
    {
        $cid = $this->cid();
        $rep = new InventoryReport();

        $from = $this->cleanDate((string) $this->request->getGet('from')) ?: date('Y-m-01');
        $to   = $this->cleanDate((string) $this->request->getGet('to')) ?: date('Y-m-d');
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }
        $range = ['from' => $from, 'to' => $to];

        $report = match ($key) {
            'product'    => $rep->productWise($cid),
            'warehouse'  => $rep->warehouseWise($cid),
            'party'      => $rep->partyWise($cid),
            'lot'        => $rep->lotWise($cid),
            'inward'     => $rep->dailyFlow($cid, 'inward', $from, $to),
            'outward'    => $rep->dailyFlow($cid, 'outward', $from, $to),
            'difference' => $rep->stockDifference($cid, $from, $to),
            'pending'    => $rep->pendingApproval($cid),
            'movement'   => $rep->movement($cid, $from, $to),
            default      => ['columns' => [], 'rows' => []],
        };

        return [$range, $report];
    }

    private function cleanDate(string $d): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
    }
}
