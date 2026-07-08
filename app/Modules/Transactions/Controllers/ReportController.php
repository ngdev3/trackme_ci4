<?php

namespace Modules\Transactions\Controllers;

use App\Controllers\BaseController;
use App\Libraries\OpeningBalance;
use App\Libraries\ReportPeriod;
use App\Models\CompanySettingModel;
use App\Models\TransactionModel;
use Config\Services;

/**
 * Rokad Parcha (cash book) reports over a chosen period — daily, monthly,
 * quarterly, financial year or a custom range. Opening / running / closing and
 * carry-forward balances are all derived from the transactions, so they are
 * always consistent with the ledger.
 */
class ReportController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected string $moduleCode = 'transactions';
    protected TransactionModel $txns;
    protected CompanySettingModel $settings;

    public function __construct()
    {
        $this->txns     = new TransactionModel();
        $this->settings = new CompanySettingModel();
    }

    private function scope(): ?int
    {
        return Services::acl()->isSuperAdmin() ? null : (int) company_id();
    }

    private ?OpeningBalance $ob = null;

    /**
     * Shared opening-cash calculator (Shri Rokad Nagad). Transaction rows are
     * scoped to the active company; the opening-cash setting owner stays the
     * signed-in user (settings are keyed per user).
     */
    private function ob(): OpeningBalance
    {
        return $this->ob ??= new OpeningBalance($this->scope(), (int) company_id(), $this->txns, $this->settings);
    }

    /** Financial-year start year (Indian FY, 1 Apr) for a date. */
    private function fyStartFor(string $date): int
    {
        return $this->ob()->fyStartFor($date);
    }

    /** Whether the user has explicitly set a Shri Rokad Nagad for this FY. */
    public function shriIsExplicit(int $fyStart): bool
    {
        return $this->ob()->isExplicit($fyStart);
    }

    /** "Shri Rokad Nagad" — opening cash-in-hand for a financial year. */
    public function shriNagad(int $fyStart, int $depth = 0): float
    {
        return $this->ob()->shriNagad($fyStart, $depth);
    }

    /** Custom, user-editable display label for the opening cash. */
    public function shriLabel(): string
    {
        return $this->ob()->label();
    }

    /** FY label like "2026-27" from a start year. */
    public static function fyLabel(int $fyStart): string
    {
        return $fyStart . '-' . substr((string) ($fyStart + 1), -2);
    }

    /**
     * Build the full report dataset for a resolved period. Shared by the
     * on-screen report, the print view and the exporters.
     *
     * @return array<string,mixed>
     */
    public function build(array $in): array
    {
        $scope = $this->scope();
        $p     = ReportPeriod::resolve($in, date('Y-m-d'));

        // Opening carried into the period = the FY's Shri Rokad Nagad plus the
        // net of every entry from 1 April up to (but excluding) the period start.
        $fyStart = $this->fyStartFor($p->from);
        $shri    = $this->shriNagad($fyStart);
        $opening = $this->ob()->carryInto($p->from);

        $rows    = $this->txns->rangeRows($scope, $p->from, $p->to);
        $running = $opening;
        foreach ($rows as &$r) {
            $running += ($r['type'] === 'jama' ? (float) $r['amount'] : -(float) $r['amount']);
            $r['balance'] = round($running, 2);
        }
        unset($r);

        [$jama, $naam] = $this->txns->rangeTotals($scope, $p->from, $p->to);
        $closing = round($opening + $jama - $naam, 2);

        return [
            'period'    => $p,
            'rows'      => $rows,
            'opening'   => $opening,
            'totalJama' => $jama,
            'totalNaam' => $naam,
            'closing'   => $closing,
            'carry'     => $closing, // carried forward to the next period
            'buckets'   => $this->txns->dailyBuckets($scope, $p->from, $p->to),
            'shri'      => $shri,      // FY opening cash (Shri Rokad Nagad)
            'fyStart'   => $fyStart,
            'fyLabel'   => self::fyLabel($fyStart),
            'shriLabel' => $this->shriLabel(),
            'shriAuto'  => ! $this->shriIsExplicit($fyStart), // auto-carried vs manually set
        ];
    }

    private function inputs(): array
    {
        return [
            // Default landing is the daily Rokad Parcha (TrackMe-style).
            'period'  => (string) ($this->request->getGet('period') ?: 'day'),
            'date'    => (string) $this->request->getGet('date'),
            'month'   => (string) $this->request->getGet('month'),
            'year'    => (string) $this->request->getGet('year'),
            'quarter' => (string) $this->request->getGet('quarter'),
            'fy'      => (string) $this->request->getGet('fy'),
            'from'    => (string) $this->request->getGet('from'),
            'to'      => (string) $this->request->getGet('to'),
        ];
    }

    public function index()
    {
        // Surface any reminders (incl. those set on entries) that are now due.
        (new \App\Libraries\ReminderService())->fireDueForCompany(company_id());

        $in   = $this->inputs();
        $data = $this->build($in);

        $common = [
            'title'      => 'Rokadh Parcha',
            'breadcrumb' => [['label' => 'Transactions', 'url' => site_url('transactions')], ['label' => 'Rokadh Parcha']],
            'in'         => $in,
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/tm-table.css'), base_url('assets/css/transactions.css')],
        ];

        // Daily view = the two-column Jama/Naam register; other periods = tabular.
        if ($data['period']->period === 'day') {
            $date = $data['period']->from;
            return $this->render('report_day', $data + $common + [
                'prevDate' => date('Y-m-d', strtotime($date . ' -1 day')),
                'nextDate' => date('Y-m-d', strtotime($date . ' +1 day')),
            ]);
        }

        return $this->render('report', $data + $common);
    }

    /** Soft-deleted entries for a date, with the option to restore them. */
    public function deleted()
    {
        $date = (string) ($this->request->getGet('date') ?: date('Y-m-d'));
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        return $this->render('report_deleted', [
            'title'      => 'Deleted Entries',
            'breadcrumb' => [['label' => 'Transactions', 'url' => site_url('transactions')], ['label' => 'Rokadh Parcha', 'url' => site_url('transactions/report') . '?period=day&date=' . $date], ['label' => 'Deleted']],
            'date'       => $date,
            'rows'       => $this->txns->deletedOnDate($this->scope(), $date),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/tm-table.css'), base_url('assets/css/transactions.css')],
        ]);
    }

    public function restore($id = null)
    {
        $id = unhid($id);
        // Confirm the (soft-deleted) row is in scope before restoring.
        $row = $this->txns->withDeleted()->find($id);
        $scope = $this->scope();
        if (! $row || ($scope !== null && (int) $row['company_id'] !== $scope)) {
            return redirect()->to(site_url('transactions/report'))->with('error', 'Entry not found.');
        }
        // deleted_at isn't an allowed field, so clear it via the builder directly.
        $this->txns->builder()->where('id', $id)->update(['deleted_at' => null]);
        activity_log('Transactions', 'Edit', "Transaction {$row['txn_no']} restored");
        return redirect()->to(site_url('transactions/report/deleted') . '?date=' . $row['txn_date'])->with('success', 'Entry restored.');
    }

    public function printReport()
    {
        $data = $this->build($this->inputs());
        return view('Modules\Transactions\Views\report_print', $data + [
            'firm' => function_exists('current_company') ? current_company() : null,
        ]);
    }

    // ===============================================================
    // Shri Rokad Nagad — opening cash settings (per financial year)
    // ===============================================================

    /** Settings page: set the opening cash-in-hand for each financial year. */
    public function opening()
    {
        $thisFy = $this->fyStartFor(date('Y-m-d'));

        // Show a window of financial years around the current one. Each carries
        // its value (explicit or auto-rolled from the prior year's closing).
        $years = [];
        for ($y = $thisFy + 1; $y >= $thisFy - 6; $y--) {
            $years[$y] = ['value' => $this->shriNagad($y), 'auto' => ! $this->shriIsExplicit($y)];
        }
        krsort($years);

        $label = $this->shriLabel();
        return $this->render('report_opening', [
            'title'      => $label . ' — Opening Cash',
            'breadcrumb' => [['label' => 'Transactions', 'url' => site_url('transactions')], ['label' => $label]],
            'thisFy'     => $thisFy,
            'years'      => $years,
            'shriLabel'  => $label,
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/tm-table.css'), base_url('assets/css/transactions.css')],
        ]);
    }

    /** Save the Shri Rokad Nagad amount (per FY) or its display label. */
    public function saveOpening()
    {
        // ---- Rename the label ----
        if ($this->request->getPost('label') !== null) {
            if (! $this->validate(['label' => 'required|max_length[60]'])) {
                return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
            }
            $clean = trim((string) $this->request->getPost('label'));
            $this->settings->put((int) company_id(), 'transactions', 'shri_rokad_label', $clean);
            activity_log('Transactions', 'Edit', 'Opening-cash label renamed to "' . $clean . '"');
            return $this->redirectBack('Name updated.');
        }

        // ---- Set / update the amount for a chosen FY ----
        if (! $this->validate([
            'fy'     => 'required|is_natural|greater_than_equal_to[2000]|less_than_equal_to[2100]',
            'amount' => 'required|decimal|greater_than_equal_to[-9999999999.99]|less_than_equal_to[9999999999.99]',
        ], [
            'fy'     => ['greater_than_equal_to' => 'Please choose a valid financial year.', 'less_than_equal_to' => 'Please choose a valid financial year.'],
            'amount' => [
                'required' => 'Please enter the opening cash amount.',
                'decimal'  => 'The amount must be a number.',
                'greater_than_equal_to' => 'The amount is too small.',
                'less_than_equal_to'    => 'The amount is too large (max 999,99,99,999.99).',
            ],
        ])) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $fy     = (int) $this->request->getPost('fy');
        $amount = round((float) $this->request->getPost('amount'), 2);

        $this->settings->put((int) company_id(), 'transactions', 'shri_rokad_nagad_' . $fy, $amount);
        activity_log('Transactions', 'Edit', $this->shriLabel() . ' set for FY ' . self::fyLabel($fy) . ' = ' . number_format($amount, 2));

        return $this->redirectBack('Opening cash saved for FY ' . self::fyLabel($fy) . '.');
    }

    /** Redirect to the posted return URL (if same-origin) or the settings page. */
    private function redirectBack(string $msg)
    {
        $back = (string) $this->request->getPost('return');
        if ($back !== '' && str_starts_with($back, base_url())) {
            return redirect()->to($back)->with('success', $msg);
        }
        return redirect()->to(site_url('transactions/opening'))->with('success', $msg);
    }
}
