<?php

namespace Modules\Inventory\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Exporter;
use App\Libraries\InventoryReport;
use App\Models\InvDailyClosingModel;
use App\Models\InvMovementModel;

/**
 * Task 7 — Daily Closing. A worker-simple end-of-day screen that shows what came
 * in, what went out, the closing stock and any pending corrections, and lets the
 * day be closed with one tap. Closing locks the day: workers can no longer add
 * or edit entries for it — only an owner/admin may reopen. Reports export to
 * PDF / Excel / CSV and print.
 */
class ClosingController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected string $moduleCode = 'inventory';

    private function cid(): ?int
    {
        return company_id();
    }

    private function uid(): int
    {
        return (int) user_id();
    }

    /** Owner/admin (or super admin) may reopen days and approve corrections. */
    private function canApprove(): bool
    {
        return is_super_admin_account() || in_array(company_role(), ['owner', 'admin'], true);
    }

    // =================================================================
    // Screen
    // =================================================================
    public function index()
    {
        $cid   = $this->cid();
        $date  = $this->cleanDate((string) $this->request->getGet('date')) ?: date('Y-m-d');
        $model = new InvDailyClosingModel();

        // If already closed, show the snapshot as-of closing; else compute live.
        $existing = $model->forDate($cid, $date);
        $summary  = (new InventoryReport())->dailySummary($cid, $date);

        return $this->render('closing', [
            'title'      => 'Daily Closing',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Daily Closing']],
            'date'       => $date,
            'isToday'    => $date === date('Y-m-d'),
            'summary'    => $summary,
            'existing'   => $existing,
            'history'    => $model->recent($cid, 30),
            'canApprove' => $this->canApprove(),
            'canClose'   => can('inventory', 'add'),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    // =================================================================
    // Close the day
    // =================================================================
    public function close()
    {
        $cid   = (int) $this->cid();
        $date  = $this->cleanDate((string) $this->request->getPost('date')) ?: date('Y-m-d');
        $model = new InvDailyClosingModel();

        // Can't close a future day; can't re-close an already-locked day.
        if ($date > date('Y-m-d')) {
            return redirect()->to(site_url('inventory/closing'))->with('error', 'You cannot close a future date.');
        }
        $existing = $model->forDate($cid, $date);
        if ($existing && $existing['status'] === 'closed') {
            return redirect()->to(site_url('inventory/closing?date=' . $date))->with('error', 'This day is already closed.');
        }

        $s   = (new InventoryReport())->dailySummary($cid, $date);
        $now = date('Y-m-d H:i:s');
        $data = [
            'company_id'          => $cid,
            'closing_date'        => $date,
            'opening_bags'        => $s['opening_bags'],
            'received_bags'       => $s['received_bags'],
            'dispatched_bags'     => $s['dispatched_bags'],
            'adjustment_bags'     => $s['adjustment_bags'],
            'closing_bags'        => $s['closing_bags'],
            'received_weight'     => $s['received_weight'],
            'dispatched_weight'   => $s['dispatched_weight'],
            'difference_bags'     => $s['difference_bags'],
            'pending_corrections' => $s['pending_corrections'],
            'entry_count'         => $s['entry_count'],
            'status'              => 'closed',
            'notes'               => trim((string) $this->request->getPost('notes')) ?: null,
            'closed_by'           => $this->uid(),
            'closed_at'           => $now,
            'reopened_by'         => null,
            'reopened_at'         => null,
        ];

        if ($existing) {
            $model->update((int) $existing['id'], $data);
        } else {
            $model->insert($data);
        }

        // Lock the day's ledger rows so workers can no longer edit them.
        $mv = new InvMovementModel();
        $b  = $mv->builder()->where('DATE(created_at)', $date);
        if ($cid) {
            $b->where('company_id', $cid);
        }
        $b->update(['day_closed' => 1]);

        activity_log('Inventory', 'Add', "Day closed for {$date} — closing {$s['closing_bags']} bags");

        return redirect()->to(site_url('inventory/closing?date=' . $date))
            ->with('success', "Inventory closed for {$date}. Entries are now locked.");
    }

    // =================================================================
    // Reopen a closed day (owner/admin only)
    // =================================================================
    public function reopen()
    {
        if (! $this->canApprove()) {
            return redirect()->to(site_url('inventory/closing'))->with('error', 'Only the owner or an admin can reopen a closed day.');
        }
        $cid   = (int) $this->cid();
        $date  = $this->cleanDate((string) $this->request->getPost('date')) ?: date('Y-m-d');
        $model = new InvDailyClosingModel();
        $row   = $model->forDate($cid, $date);
        if (! $row || $row['status'] !== 'closed') {
            return redirect()->to(site_url('inventory/closing?date=' . $date))->with('error', 'That day is not closed.');
        }

        $model->update((int) $row['id'], [
            'status'      => 'reopened',
            'reopened_by' => $this->uid(),
            'reopened_at' => date('Y-m-d H:i:s'),
        ]);

        $mv = new InvMovementModel();
        $b  = $mv->builder()->where('DATE(created_at)', $date);
        if ($cid) {
            $b->where('company_id', $cid);
        }
        $b->update(['day_closed' => 0]);

        activity_log('Inventory', 'Edit', "Day reopened for {$date}");

        return redirect()->to(site_url('inventory/closing?date=' . $date))
            ->with('success', "Inventory reopened for {$date}. Entries can be edited again.");
    }

    // =================================================================
    // Exports + print
    // =================================================================
    public function report(string $format = 'csv')
    {
        $cid  = $this->cid();
        $date = $this->cleanDate((string) $this->request->getGet('date')) ?: date('Y-m-d');
        $data = $this->reportData($cid, $date);

        $headers = ['Metric', 'Bags'];
        $matrix  = [
            ['Opening Stock', number_format($data['summary']['opening_bags'], 2, '.', '')],
            ['Received Today', number_format($data['summary']['received_bags'], 2, '.', '')],
            ['Dispatched Today', number_format($data['summary']['dispatched_bags'], 2, '.', '')],
            ['Adjustments Today', number_format($data['summary']['adjustment_bags'], 2, '.', '')],
            ['Closing Stock', number_format($data['summary']['closing_bags'], 2, '.', '')],
            ['Stock Difference', number_format($data['summary']['difference_bags'], 2, '.', '')],
            ['Pending Corrections', (string) $data['summary']['pending_corrections']],
            ['Entries', (string) $data['summary']['entry_count']],
        ];

        $name = 'daily_closing_' . $date;

        return match ($format) {
            'xlsx' => Exporter::xlsx($name, 'Daily Closing', $headers, $matrix),
            'pdf'  => Exporter::pdf($name, view('Modules\Inventory\Views\closing_print', $data + ['pdf' => true])),
            default => Exporter::csv($name, $headers, $matrix),
        };
    }

    public function printReport()
    {
        $cid  = $this->cid();
        $date = $this->cleanDate((string) $this->request->getGet('date')) ?: date('Y-m-d');

        return view('Modules\Inventory\Views\closing_print', $this->reportData($cid, $date) + ['pdf' => false]);
    }

    /** Assemble the data a closing report/print needs. */
    private function reportData(?int $cid, string $date): array
    {
        $model    = new InvDailyClosingModel();
        $existing = $model->forDate($cid, $date);
        $summary  = $existing
            ? [
                'opening_bags'        => (float) $existing['opening_bags'],
                'received_bags'       => (float) $existing['received_bags'],
                'dispatched_bags'     => (float) $existing['dispatched_bags'],
                'adjustment_bags'     => (float) $existing['adjustment_bags'],
                'closing_bags'        => (float) $existing['closing_bags'],
                'received_weight'     => (float) $existing['received_weight'],
                'dispatched_weight'   => (float) $existing['dispatched_weight'],
                'difference_bags'     => (float) $existing['difference_bags'],
                'pending_corrections' => (int) $existing['pending_corrections'],
                'entry_count'         => (int) $existing['entry_count'],
            ]
            : (new InventoryReport())->dailySummary($cid, $date);

        // The day's entries for the detail table.
        $entries = (new InvMovementModel())->scopedList($cid)
            ->where('DATE(inv_movements.created_at)', $date)
            ->orderBy('inv_movements.id', 'ASC')->get()->getResultArray();

        return [
            'date'     => $date,
            'summary'  => $summary,
            'existing' => $existing,
            'entries'  => $entries,
            'firm'     => function_exists('current_company') ? current_company() : null,
        ];
    }

    /** Accept only a Y-m-d date; reject anything else. */
    private function cleanDate(string $d): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
    }
}
