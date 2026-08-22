<?php

namespace App\Models;

use Config\Database;

/**
 * Read-only analytics for the firm (business) dashboard. Every query is scoped
 * to a company_id so one firm's figures never mix with another's. Sources are
 * the firm modules already built: rokad_entries, vouchers, notes, reminders,
 * company_users.
 */
class FirmDashboardModel
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    public function dateRange(string $filter = 'month', ?string $from = null, ?string $to = null, ?array $company = null): array
    {
        $today = date('Y-m-d');
        switch ($filter) {
            case 'today':
                return [$today, $today, 'Today'];
            case 'week':
                return [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week')), 'This Week'];
            case 'quarter':
                $month = (int) date('n');
                $quarterStart = ((int) floor(($month - 1) / 3) * 3) + 1;
                $start = date('Y') . '-' . str_pad((string) $quarterStart, 2, '0', STR_PAD_LEFT) . '-01';
                return [$start, date('Y-m-t', strtotime($start . ' +2 months')), 'Quarter'];
            case 'financial_year':
                $fyStart = $company['financial_year_from'] ?? null;
                if ($fyStart) {
                    $startMonth = date('m-d', strtotime($fyStart));
                    $thisYearStart = date('Y') . '-' . $startMonth;
                    $year = $thisYearStart > $today ? (int) date('Y') - 1 : (int) date('Y');
                    $start = $year . '-' . $startMonth;
                } else {
                    $year = date('m-d') < '04-01' ? (int) date('Y') - 1 : (int) date('Y');
                    $start = $year . '-04-01';
                }
                return [$start, date('Y-m-d', strtotime($start . ' +1 year -1 day')), 'Financial Year'];
            case 'custom':
                $start = $from ?: $today;
                $end = $to ?: $start;
                if ($start > $end) {
                    [$start, $end] = [$end, $start];
                }
                return [$start, $end, 'Custom Range'];
            case 'month':
            default:
                return [date('Y-m-01'), date('Y-m-t'), 'This Month'];
        }
    }

    public function erpSummary(?int $companyId, string $from, string $to): array
    {
        $companyIds = $companyId ? [$companyId] : $this->allCompanyIds();
        $voucher = fn (?string $type = null) => $this->voucherSum($companyIds, $from, $to, $type);
        $jamaNaam = $this->jamaNaam($companyIds, $from, $to);
        $balances = $this->ledgerBalances($companyIds);
        $inventory = $this->inventorySnapshot($companyIds);

        $sales = $voucher('sales');
        $purchase = $voucher('purchase');
        $receipts = $voucher('receipt');
        $payments = $voucher('payment');

        return [
            'sales' => $sales,
            'purchases' => $purchase,
            'inventory_items' => $inventory['items'],
            'inventory_value' => $inventory['value'],
            'low_stock' => $inventory['low_stock'],
            'jama' => $jamaNaam['jama'],
            'naam' => $jamaNaam['naam'],
            'cash_balance' => $this->cashBalance($companyIds),
            'bank_balance' => $this->bankBalance($companyIds),
            'outstanding_receivable' => $balances['receivable'],
            'outstanding_payable' => $balances['payable'],
            'gross_profit' => $sales - $purchase,
            'net_flow' => ($receipts + $jamaNaam['jama']) - ($payments + $jamaNaam['naam']),
            'vouchers' => $this->voucherCount($companyIds, $from, $to),
            'companies' => count($companyIds),
            'inventory_configured' => $inventory['configured'],
        ];
    }

    public function erpCharts(?int $companyId, string $from, string $to): array
    {
        $companyIds = $companyId ? [$companyId] : $this->allCompanyIds();

        return [
            'salesPurchase' => $this->monthlyVoucherSeries($companyIds, 6, ['sales', 'purchase']),
            'jamaNaam' => $this->dailyJamaNaamSeries($companyIds, $from, $to),
            'voucherTypes' => $this->voucherTypeBreakdown($companyIds, $from, $to),
            'cashBank' => [
                'labels' => ['Cash', 'Bank'],
                'data' => [round($this->cashBalance($companyIds), 2), round($this->bankBalance($companyIds), 2)],
            ],
        ];
    }

    public function recentTransactions(?int $companyId, string $from, string $to, int $limit = 8): array
    {
        $companyIds = $companyId ? [$companyId] : $this->allCompanyIds();
        if ($companyIds === []) {
            return [];
        }

        return $this->db->table('vouchers v')
            ->select('v.id, v.company_id, c.name AS company_name, v.voucher_type, v.voucher_no, v.date, v.narration, v.amount')
            ->join('companies c', 'c.id = v.company_id', 'left')
            ->whereIn('v.company_id', $companyIds)
            ->where('v.deleted_at', null)
            ->where('v.date >=', $from)
            ->where('v.date <=', $to)
            ->orderBy('v.date', 'DESC')
            ->orderBy('v.id', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();
    }

    public function financialYearSummary(?int $companyId, ?array $company = null): array
    {
        [$from, $to] = $this->dateRange('financial_year', null, null, $company);
        return array_merge(['from' => $from, 'to' => $to], $this->erpSummary($companyId, $from, $to));
    }

    private function allCompanyIds(): array
    {
        if (! $this->db->tableExists('companies')) {
            return [];
        }
        $rows = $this->db->table('companies')->select('id')->where('deleted_at', null)->get()->getResultArray();
        return array_map(static fn ($row) => (int) $row['id'], $rows);
    }

    private function scopedTable(string $table, string $alias, array $companyIds)
    {
        $builder = $this->db->table($table . ' ' . $alias);
        if ($companyIds === []) {
            $builder->where($alias . '.company_id', -1);
        } else {
            $builder->whereIn($alias . '.company_id', $companyIds);
        }
        return $builder;
    }

    private function voucherSum(array $companyIds, string $from, string $to, ?string $type = null): float
    {
        $builder = $this->scopedTable('vouchers', 'v', $companyIds)
            ->select('COALESCE(SUM(v.amount),0) AS total')
            ->where('v.deleted_at', null)
            ->where('v.date >=', $from)
            ->where('v.date <=', $to);
        if ($type !== null) {
            $builder->where('v.voucher_type', $type);
        }
        return round((float) ($builder->get()->getRowArray()['total'] ?? 0), 2);
    }

    private function voucherCount(array $companyIds, string $from, string $to): int
    {
        return $this->scopedTable('vouchers', 'v', $companyIds)
            ->where('v.deleted_at', null)
            ->where('v.date >=', $from)
            ->where('v.date <=', $to)
            ->countAllResults();
    }

    private function jamaNaam(array $companyIds, string $from, string $to): array
    {
        if (! $this->db->tableExists('rokad_entries')) {
            return ['jama' => 0.0, 'naam' => 0.0];
        }
        $row = $this->scopedTable('rokad_entries', 'r', $companyIds)
            ->select('COALESCE(SUM(r.jama),0) AS jama, COALESCE(SUM(r.naam),0) AS naam')
            ->where('r.deleted_at', null)
            ->where('r.entry_date >=', $from)
            ->where('r.entry_date <=', $to)
            ->get()->getRowArray();

        return [
            'jama' => round((float) ($row['jama'] ?? 0), 2),
            'naam' => round((float) ($row['naam'] ?? 0), 2),
        ];
    }

    private function cashBalance(array $companyIds): float
    {
        if (! $this->db->tableExists('rokad_entries')) {
            return 0.0;
        }
        $row = $this->scopedTable('rokad_entries', 'r', $companyIds)
            ->select('COALESCE(SUM(r.jama - r.naam),0) AS balance')
            ->where('r.deleted_at', null)
            ->get()->getRowArray();

        return round((float) ($row['balance'] ?? 0), 2);
    }

    private function bankBalance(array $companyIds): float
    {
        if (! $this->db->tableExists('voucher_entries')) {
            return 0.0;
        }
        $builder = $this->scopedTable('voucher_entries', 've', $companyIds)
            ->select('COALESCE(SUM(ve.dr_amount - ve.cr_amount),0) AS balance')
            ->join('ledgers l', 'l.id = ve.ledger_id', 'left')
            ->join('accounting_groups ag', 'ag.id = l.group_id', 'left')
            ->groupStart()
                ->like('l.name', 'bank')
                ->orLike('ag.name', 'bank')
            ->groupEnd();
        return round((float) ($builder->get()->getRowArray()['balance'] ?? 0), 2);
    }

    private function ledgerBalances(array $companyIds): array
    {
        if (! $this->db->tableExists('ledgers')) {
            return ['receivable' => 0.0, 'payable' => 0.0];
        }
        $rows = $this->scopedTable('ledgers', 'l', $companyIds)
            ->select('l.id, l.opening_balance, l.opening_type, COALESCE(SUM(ve.dr_amount - ve.cr_amount),0) AS movement')
            ->join('voucher_entries ve', 've.ledger_id = l.id', 'left')
            ->where('l.deleted_at', null)
            ->groupBy('l.id')
            ->get()->getResultArray();

        $receivable = 0.0;
        $payable = 0.0;
        foreach ($rows as $row) {
            $opening = (float) $row['opening_balance'] * (($row['opening_type'] ?? 'Dr') === 'Cr' ? -1 : 1);
            $balance = $opening + (float) $row['movement'];
            if ($balance > 0) {
                $receivable += $balance;
            } elseif ($balance < 0) {
                $payable += abs($balance);
            }
        }

        return ['receivable' => round($receivable, 2), 'payable' => round($payable, 2)];
    }

    private function inventorySnapshot(array $companyIds): array
    {
        $configured = $this->db->tableExists('items') || $this->db->tableExists('stock');
        return ['configured' => $configured, 'items' => 0, 'value' => 0.0, 'low_stock' => 0];
    }

    private function monthlyVoucherSeries(array $companyIds, int $months, array $types): array
    {
        $labels = [];
        $series = array_fill_keys($types, []);
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("-{$i} months"));
            $end = date('Y-m-t', strtotime($start));
            $labels[] = date('M Y', strtotime($start));
            foreach ($types as $type) {
                $series[$type][] = $this->voucherSum($companyIds, $start, $end, $type);
            }
        }
        return ['labels' => $labels] + $series;
    }

    private function dailyJamaNaamSeries(array $companyIds, string $from, string $to): array
    {
        $startTs = strtotime($from);
        $endTs = strtotime($to);
        $days = max(1, min(31, (int) floor(($endTs - $startTs) / 86400) + 1));
        $labels = $jama = $naam = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days", $endTs));
            $labels[] = date('d M', strtotime($day));
            $row = $this->jamaNaam($companyIds, $day, $day);
            $jama[] = $row['jama'];
            $naam[] = $row['naam'];
        }
        return ['labels' => $labels, 'jama' => $jama, 'naam' => $naam];
    }

    private function voucherTypeBreakdown(array $companyIds, string $from, string $to): array
    {
        $types = ['sales', 'purchase', 'receipt', 'payment', 'contra', 'journal'];
        return [
            'labels' => array_map(static fn ($type) => ucfirst($type), $types),
            'data' => array_map(fn ($type) => $this->voucherSum($companyIds, $from, $to, $type), $types),
        ];
    }

    /** Opening balance the cash book started with. */
    private function rokadOpening(int $companyId): float
    {
        $row = $this->db->table('company_settings')
            ->where('company_id', $companyId)->where('scope', 'rokad')->where('key', 'opening_balance')
            ->get()->getRowArray();
        return (float) ($row['value'] ?? 0);
    }

    /** Net cash movement (jama − naam) over all entries, optional date bounds. */
    private function rokadNet(int $companyId, ?string $from = null, ?string $to = null): float
    {
        $b = $this->db->table('rokad_entries')
            ->select('COALESCE(SUM(jama - naam),0) AS net')
            ->where('company_id', $companyId)->where('deleted_at', null);
        if ($from) {
            $b->where('entry_date >=', $from);
        }
        if ($to) {
            $b->where('entry_date <=', $to);
        }
        return (float) ($b->get()->getRowArray()['net'] ?? 0);
    }

    // ------------------------------------------------------------------
    public function kpis(int $companyId): array
    {
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $jamaMonth = (float) ($this->db->table('rokad_entries')->select('COALESCE(SUM(jama),0) AS s')
            ->where('company_id', $companyId)->where('deleted_at', null)
            ->where('entry_date >=', $monthStart)->where('entry_date <=', $monthEnd)->get()->getRowArray()['s'] ?? 0);
        $naamMonth = (float) ($this->db->table('rokad_entries')->select('COALESCE(SUM(naam),0) AS s')
            ->where('company_id', $companyId)->where('deleted_at', null)
            ->where('entry_date >=', $monthStart)->where('entry_date <=', $monthEnd)->get()->getRowArray()['s'] ?? 0);

        return [
            'cash_balance'   => round($this->rokadOpening($companyId) + $this->rokadNet($companyId), 2),
            'month_in'       => round($jamaMonth, 2),
            'month_out'      => round($naamMonth, 2),
            'notes'          => $this->db->table('notes')->where('user_id', (int) session()->get('user_id'))->where('deleted_at', null)->countAllResults(),
            'reminders'      => $this->db->table('reminders')->where('user_id', (int) session()->get('user_id'))->where('status', 'pending')->where('deleted_at', null)->countAllResults(),
            'ledgers'        => $this->db->table('ledgers')->where('company_id', $companyId)->where('deleted_at', null)->countAllResults(),
            'vouchers'       => $this->db->table('vouchers')->where('company_id', $companyId)->where('deleted_at', null)->countAllResults(),
            'firm_users'     => $this->db->table('company_users')->where('company_id', $companyId)->countAllResults(),
        ];
    }

    /** Daily Jama vs Naam for the last N days (line/bar chart). */
    public function cashFlow(int $companyId, int $days = 14): array
    {
        $labels = $jama = $naam = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('d M', strtotime($day));
            $row = $this->db->table('rokad_entries')->select('COALESCE(SUM(jama),0) AS j, COALESCE(SUM(naam),0) AS n')
                ->where('company_id', $companyId)->where('deleted_at', null)->where('entry_date', $day)->get()->getRowArray();
            $jama[] = round((float) ($row['j'] ?? 0), 2);
            $naam[] = round((float) ($row['n'] ?? 0), 2);
        }
        return ['labels' => $labels, 'jama' => $jama, 'naam' => $naam];
    }

    /** Monthly Jama vs Naam for the last N months (grouped bar chart). */
    public function monthlyCash(int $companyId, int $months = 6): array
    {
        $labels = $jama = $naam = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("-{$i} months"));
            $end   = date('Y-m-t', strtotime("-{$i} months"));
            $labels[] = date('M Y', strtotime($start));
            $row = $this->db->table('rokad_entries')->select('COALESCE(SUM(jama),0) AS j, COALESCE(SUM(naam),0) AS n')
                ->where('company_id', $companyId)->where('deleted_at', null)
                ->where('entry_date >=', $start)->where('entry_date <=', $end)->get()->getRowArray();
            $jama[] = round((float) ($row['j'] ?? 0), 2);
            $naam[] = round((float) ($row['n'] ?? 0), 2);
        }
        return ['labels' => $labels, 'jama' => $jama, 'naam' => $naam];
    }

    /** Reminders of the current user by status (pie): pending / overdue / completed. */
    public function remindersByStatus(): array
    {
        $uid   = (int) session()->get('user_id');
        $now   = date('Y-m-d H:i:s');
        $base  = fn () => $this->db->table('reminders')->where('user_id', $uid)->where('deleted_at', null);

        $completed = $base()->where('status', 'completed')->countAllResults();
        $overdue   = $base()->where('status', 'pending')
            ->where('COALESCE(snoozed_until, remind_at) < ' . $this->db->escape($now), null, false)->countAllResults();
        $pending   = $base()->where('status', 'pending')
            ->where('COALESCE(snoozed_until, remind_at) >= ' . $this->db->escape($now), null, false)->countAllResults();

        return ['labels' => ['Pending', 'Overdue', 'Completed'], 'data' => [$pending, $overdue, $completed]];
    }

    /** Notes of the current user: important vs normal (doughnut). */
    public function notesBreakdown(): array
    {
        $uid  = (int) session()->get('user_id');
        $base = fn () => $this->db->table('notes')->where('user_id', $uid)->where('deleted_at', null);
        $important = $base()->where('is_important', 1)->countAllResults();
        $normal    = $base()->where('is_important', 0)->countAllResults();
        return ['labels' => ['Important', 'Normal'], 'data' => [$important, $normal]];
    }

    /** Recent cash-book entries for the firm. */
    public function recentCash(int $companyId, int $limit = 6): array
    {
        return $this->db->table('rokad_entries')
            ->where('company_id', $companyId)->where('deleted_at', null)
            ->orderBy('entry_date', 'DESC')->orderBy('id', 'DESC')
            ->limit($limit)->get()->getResultArray();
    }

    /** Upcoming reminders for the current user. */
    public function upcomingReminders(int $limit = 5): array
    {
        $uid = (int) session()->get('user_id');
        return $this->db->table('reminders')
            ->where('user_id', $uid)->where('status', 'pending')->where('deleted_at', null)
            ->orderBy('COALESCE(snoozed_until, remind_at)', 'ASC', false)
            ->limit($limit)->get()->getResultArray();
    }

    // ==================================================================
    // Real Jama/Naam ledger (the `transactions` table) — the data users
    // actually populate. Powers the redesigned, real-time dashboard.
    // A null $companyId = the Super Admin's all-company view.
    // ==================================================================

    /** Scoped builder on the transactions table (excludes soft-deleted rows). */
    private function txnBuilder(?int $companyId)
    {
        if (! $this->db->tableExists('transactions')) {
            return null;
        }
        $b = $this->db->table('transactions')->where('deleted_at', null);
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return $b;
    }

    /**
     * Money-in (Jama) / money-out (Naam) headline figures from the real ledger:
     * period totals, today's movement, pending count and the live cash-in-hand.
     */
    public function txnSummary(?int $companyId, string $from, string $to): array
    {
        $empty = ['jama' => 0.0, 'naam' => 0.0, 'net' => 0.0, 'count' => 0, 'today_jama' => 0.0, 'today_naam' => 0.0, 'pending' => 0, 'cash_in_hand' => 0.0];
        if (! ($b = $this->txnBuilder($companyId))) {
            return $empty;
        }

        $row = (clone $b)->select(
            "COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS jama,"
            . "COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS naam,"
            . 'COUNT(*) AS cnt'
        )->where('txn_date >=', $from)->where('txn_date <=', $to)->get()->getRowArray();

        $today = date('Y-m-d');
        $todayRow = ($this->txnBuilder($companyId))->select(
            "COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS j,"
            . "COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS n"
        )->where('txn_date', $today)->get()->getRowArray();

        // Pending/overdue count. For the all-firms (super-admin) view force the
        // status index — otherwise MySQL picks a deleted_at-leading index and
        // scans ~1M rows (20s+). Company-scoped queries stay fast on the company
        // index, so they keep the normal builder.
        if ($companyId === null) {
            $pending = (int) (($this->db->query(
                "SELECT COUNT(*) AS c FROM transactions FORCE INDEX (idx_dash_status) "
                . "WHERE deleted_at IS NULL AND status IN ('pending','overdue')"
            )->getRowArray()['c']) ?? 0);
        } else {
            $pending = ($this->txnBuilder($companyId))->whereIn('status', ['pending', 'overdue'])->countAllResults();
        }

        $jama = round((float) ($row['jama'] ?? 0), 2);
        $naam = round((float) ($row['naam'] ?? 0), 2);

        return [
            'jama'         => $jama,
            'naam'         => $naam,
            'net'          => round($jama - $naam, 2),
            'count'        => (int) ($row['cnt'] ?? 0),
            'today_jama'   => round((float) ($todayRow['j'] ?? 0), 2),
            'today_naam'   => round((float) ($todayRow['n'] ?? 0), 2),
            'pending'      => $pending,
            'cash_in_hand' => $this->cashInHand($companyId),
        ];
    }

    /**
     * Live cash-in-hand from the Jama/Naam ledger: opening (Shri Rokad Nagad) +
     * net of every entry to date. Uses the shared OpeningBalance so it agrees
     * with the ledger and Rokad Parcha reports. Super-admin (null) = net only.
     */
    public function cashInHand(?int $companyId): float
    {
        if (! $this->db->tableExists('transactions')) {
            return 0.0;
        }
        if ($companyId === null) {
            // Whole-ledger net across every firm — force the covering date index so
            // MySQL does an index-only scan instead of mis-choosing a non-covering
            // index and doing ~1M random row lookups (20s → ~0.4s).
            $row = $this->db->query(
                "SELECT COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE -amount END),0) AS net "
                . "FROM transactions FORCE INDEX (idx_dash_date) WHERE deleted_at IS NULL"
            )->getRowArray();
            return round((float) ($row['net'] ?? 0), 2);
        }
        $ob = new \App\Libraries\OpeningBalance($companyId, $companyId);
        return round($ob->carryInto(date('Y-m-d', strtotime('+1 day'))), 2);
    }

    /** Daily Jama vs Naam from the real ledger for the last N days. */
    public function txnDailyTrend(?int $companyId, int $days = 14): array
    {
        $labels = $jama = $naam = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day      = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('d M', strtotime($day));
            $b = $this->txnBuilder($companyId);
            $row = $b ? $b->select("COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS j, COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS n")
                ->where('txn_date', $day)->get()->getRowArray() : [];
            $jama[] = round((float) ($row['j'] ?? 0), 2);
            $naam[] = round((float) ($row['n'] ?? 0), 2);
        }
        return ['labels' => $labels, 'jama' => $jama, 'naam' => $naam];
    }

    /** Movement volume grouped by payment mode (doughnut). */
    public function txnByMode(?int $companyId, string $from, string $to): array
    {
        $b = $this->txnBuilder($companyId);
        if (! $b) {
            return ['labels' => [], 'data' => []];
        }
        $rows = $b->select('COALESCE(payment_mode, "other") AS mode, COALESCE(SUM(amount),0) AS total')
            ->where('txn_date >=', $from)->where('txn_date <=', $to)
            ->groupBy('payment_mode')->orderBy('total', 'DESC')->get()->getResultArray();
        $labels = $data = [];
        foreach ($rows as $r) {
            if ((float) $r['total'] <= 0) {
                continue;
            }
            $labels[] = ucfirst((string) $r['mode']);
            $data[]   = round((float) $r['total'], 2);
        }
        return ['labels' => $labels, 'data' => $data];
    }

    /** Most recent ledger entries — the live activity feed. */
    public function recentTxns(?int $companyId, int $limit = 8): array
    {
        $b = $this->txnBuilder($companyId);
        if (! $b) {
            return [];
        }
        return $b->select('id, txn_no, txn_date, name, type, amount, payment_mode, status, created_at')
            ->orderBy('txn_date', 'DESC')->orderBy('id', 'DESC')
            ->limit($limit)->get()->getResultArray();
    }

    /** Top parties (accounts) by net balance in the period. */
    public function topParties(?int $companyId, int $limit = 5): array
    {
        $b = $this->txnBuilder($companyId);
        if (! $b) {
            return [];
        }
        $rows = $b->select("name,"
                . "COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS jama,"
                . "COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS naam,"
                . 'COUNT(*) AS cnt')
            ->where('name IS NOT NULL')->where('name !=', '')
            ->groupBy('name')->orderBy('cnt', 'DESC')
            ->limit($limit)->get()->getResultArray();
        return array_map(static fn ($r) => [
            'name' => (string) $r['name'],
            'jama' => round((float) $r['jama'], 2),
            'naam' => round((float) $r['naam'], 2),
            'net'  => round((float) $r['jama'] - (float) $r['naam'], 2),
            'cnt'  => (int) $r['cnt'],
        ], $rows);
    }

    /**
     * Small, relevant counters for the active company: transactions, ledgers,
     * vouchers, company-shared notes/reminders and stored passwords. Used by
     * both the initial paint and the real-time refresh.
     */
    public function liveCounts(?int $companyId): array
    {
        $scoped = function (string $table) use ($companyId) {
            if (! $this->db->tableExists($table)) {
                return null;
            }
            $b = $this->db->table($table)->where('deleted_at', null);
            if ($companyId !== null) {
                $b->where('company_id', $companyId);
            }
            return $b;
        };
        $count = static fn ($b) => $b ? $b->countAllResults() : 0;

        $remB = $scoped('reminders');
        if ($remB) {
            $remB->where('status', 'pending');
        }

        return [
            'transactions' => $count($this->txnBuilder($companyId)),
            'ledgers'      => $count($scoped('ledgers')),
            'vouchers'     => $count($scoped('vouchers')),
            'notes'        => $count($scoped('notes')),
            'reminders'    => $count($remB),
            'passwords'    => $count($scoped('passwords')),
        ];
    }
}
