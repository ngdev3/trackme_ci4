<?php

namespace Modules\Dashboard\Controllers;

use App\Controllers\BaseController;
use App\Models\DashboardModel;
use App\Models\LoginLogModel;
use App\Models\ModuleModel;
use App\Models\RoleModel;
use App\Models\UserModel;

class DashboardController extends BaseController
{
    /**
     * Main dashboard page. (Kept backward-compatible: still supplies the
     * original variables plus the new KPI bundle for the redesigned view.)
     */
    public function index()
    {
        // Super Admin sees an all-company ERP dashboard. Firm/customer users
        // see the same ERP dashboard scoped to their active company.
        helper('company');
        if ((bool) session()->get('is_superadmin') || company_id()) {
            return $this->firmDashboard((bool) session()->get('is_superadmin') ? null : (int) company_id());
        }

        $users   = new UserModel();
        $roles   = new RoleModel();
        $modules = new ModuleModel();

        // 7-day login trend for the chart (original logic, unchanged).
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $day   = date('Y-m-d', strtotime("-{$i} days"));
            $count = (new LoginLogModel())
                ->where('status', 'success')
                ->where('DATE(created_at)', $day)
                ->countAllResults();
            $trend[] = ['label' => date('D', strtotime($day)), 'count' => $count];
        }

        // TrackmeNew-style widget data (server-rendered so tiles paint instantly).
        $dash        = new DashboardModel();
        $loginTrend  = $dash->loginTrend(7);
        $loginOk     = array_sum($loginTrend['success']);
        $loginFail   = array_sum($loginTrend['failed']);

        $data = [
            'title'        => 'Dashboard',
            'totalUsers'   => $users->countAllResults(),
            'activeUsers'  => (new UserModel())->countActive(),
            'totalRoles'   => $roles->countAllResults(),
            'totalModules' => $modules->countAllResults(),
            'recentLogins' => (new LoginLogModel())->recent(8),
            'trend'        => $trend,
            // New: full KPI set for the redesigned widgets (initial paint).
            'kpis'         => $dash->kpis(),
            // TrackmeNew-style gauges + strips.
            'loginOk'      => $loginOk,
            'loginFail'    => $loginFail,
            'topUsers'     => $dash->topActiveUsers(5, 30),
            'usersByRole'  => $dash->usersByRole(),
            // Slice layout extras.
            'breadcrumb'   => [['label' => 'Dashboard']],
            'css'          => [
                base_url('assets/css/trackme-dashboard.css'),
                base_url('assets/css/dashboard.css'),
            ],
            'js'           => [
                base_url('assets/vendor/chart/chart.umd.min.js'),
                base_url('assets/js/dashboard.js'),
            ],
            'inline_js'    => 'window.ERP_DASH={analyticsUrl:' . json_encode(site_url('dashboard/analytics'))
                . ',defaultCity:' . json_encode('New Delhi')
                . ',weatherUnits:' . json_encode('metric') . '};',
        ];

        return $this->render('index', $data);
    }

    /**
     * Firm (business) dashboard — cash flow, money in/out, notes & reminders,
     * all scoped to the active firm and rendered with charts.
     */
    private function firmDashboard(?int $companyId)
    {
        $m    = new \App\Models\FirmDashboardModel();
        $firm = $companyId ? current_company() : null;
        $filter = (string) ($this->request->getGet('period') ?: 'month');
        $customFrom = $this->request->getGet('from') ? (string) $this->request->getGet('from') : null;
        $customTo = $this->request->getGet('to') ? (string) $this->request->getGet('to') : null;
        [$from, $to, $periodLabel] = $m->dateRange($filter, $customFrom, $customTo, $firm);

        // Heavy aggregates (25+ queries) are cached per company + period so the
        // post-login load is fast and repeated loads / other users don't re-hit
        // the DB. dash_bust() (on any transaction write) invalidates instantly;
        // otherwise the data is at most 90s stale. `?fresh=1` forces a recompute.
        // Cache TTL: a firm's board stays fresh at 90s (and busts instantly on any
        // of its writes); the Super-Admin all-firms board aggregates 1M+ rows and
        // changes slowly, so cache it far longer to avoid the ~2.5s recompute on
        // every load (it isn't busted per-firm-write, only by TTL).
        $ttl = $companyId === null ? 600 : 90;
        $agg = dash_remember($companyId, 'page:' . $filter . ':' . md5($from . '|' . $to), $ttl, function () use ($m, $companyId, $from, $to, $firm) {
            $erp = $m->erpSummary($companyId, $from, $to);
            $charts = [
                'cashFlow'     => $companyId ? $m->cashFlow($companyId, 14) : ($m->erpCharts(null, $from, $to)['jamaNaam'] ?? []),
                'monthlyCash'  => $companyId ? $m->monthlyCash($companyId, 6) : ($m->erpCharts(null, $from, $to)['salesPurchase'] ?? []),
                'reminders'    => $m->remindersByStatus(),
                'notes'        => $m->notesBreakdown(),
                'moneyInOut'   => ['labels' => ['Jama', 'Naam'], 'data' => [$erp['jama'], $erp['naam']]],
                'erp'          => $m->erpCharts($companyId, $from, $to),
                'txnTrend'     => $m->txnDailyTrend($companyId, 14),
                'txnByMode'    => $m->txnByMode($companyId, $from, $to),
            ];
            return [
                'erp'    => $erp,
                'txn'    => $m->txnSummary($companyId, $from, $to),
                'fy'     => $m->financialYearSummary($companyId, $firm),
                'charts' => $charts,
                'kpis'   => $companyId ? $m->kpis($companyId) : [
                    'cash_balance' => $erp['cash_balance'],
                    'month_in' => $erp['jama'],
                    'month_out' => $erp['naam'],
                    'ledgers' => 0, 'vouchers' => $erp['vouchers'],
                    'reminders' => 0, 'notes' => 0, 'firm_users' => 0,
                ],
                'recentCash'         => $companyId ? $m->recentCash($companyId, 6) : [],
                'recentTransactions' => $m->recentTransactions($companyId, $from, $to, 10),
                'recentTxns'         => $m->recentTxns($companyId, 8),
                'topParties'         => $m->topParties($companyId, 5),
                'counts'             => $m->liveCounts($companyId),
                'upcoming'           => $m->upcomingReminders(5),
            ];
        });

        // User/session-specific bits stay fresh (never cached across users).
        return $this->render('firm', array_merge($agg, [
            'title'        => 'Dashboard',
            'breadcrumb'   => [['label' => 'Dashboard']],
            'firm'         => $firm,
            'isSuperDashboard' => (bool) session()->get('is_superadmin') && $companyId === null,
            'period'       => $filter,
            'periodLabel'  => $periodLabel,
            'dateFrom'     => $from,
            'dateTo'       => $to,
            'canRokad'     => (bool) session()->get('is_superadmin') || firm_can('rokad'),
            'me'           => current_user(),
            'liveUrl'      => site_url('dashboard/live'),
            'css'          => [base_url('assets/css/dashboard-live.css')],
            'js'           => [base_url('assets/vendor/chart/chart.umd.min.js'), base_url('assets/js/firm_dashboard.js')],
        ]));
    }

    /**
     * AJAX analytics feed (JSON) for the dashboard charts.
     * GET so it is exempt from CSRF; still guarded by permission:dashboard,view.
     *
     * Query param ?block= lets widgets load independently:
     *   logins | usersByType | usersByRole | activity | growth | topUsers | finance | all (default)
     */
    public function analytics()
    {
        if (! $this->request->isAJAX()) {
            // Allow direct access too, but hint intended usage.
            $this->response->setHeader('X-Info', 'Dashboard analytics feed');
        }

        $model = new DashboardModel();
        $blockParam = $this->request->getGet('block');
        $block = (string) ($blockParam !== null ? $blockParam : 'all');

        $payload = [];
        $wants = static function ($b) use ($block) {
            return $block === 'all' || $block === $b;
        };

        if ($wants('kpis'))        { $payload['kpis']        = $model->kpis(); }
        if ($wants('logins'))      { $payload['logins']      = $model->loginTrend(7); }
        if ($wants('usersByType')) { $payload['usersByType'] = $model->usersByType(); }
        if ($wants('usersByRole')) { $payload['usersByRole'] = $model->usersByRole(); }
        if ($wants('activity'))    { $payload['activity']    = $model->activityByAction(30); }
        if ($wants('growth'))      { $payload['growth']      = $model->userGrowth(6); }
        if ($wants('topUsers'))    { $payload['topUsers']    = $model->topActiveUsers(10, 30); }
        if ($wants('finance'))     {
            $payload['financeKpis']   = $model->financeKpis();
            $payload['financeSeries'] = $model->financeSeries();
        }

        return $this->response->setJSON([
            'status' => 'ok',
            'time'   => date('c'),
            'data'   => $payload,
        ]);
    }

    /**
     * Real-time feed for the firm dashboard (JSON). Polled every ~20s to refresh
     * KPIs, live counts and the recent-activity feed without a full reload —
     * scoped to the active company (null = Super Admin, all companies).
     * GET so it is CSRF-exempt; still guarded by permission:dashboard,view.
     */
    public function live()
    {
        helper('company');
        $cid       = company_id();
        $companyId = ((bool) session()->get('is_superadmin') || ! $cid) ? null : (int) $cid;

        $m = new \App\Models\FirmDashboardModel();
        $filter = (string) ($this->request->getGet('period') ?: 'month');
        $from   = $this->request->getGet('from') ? (string) $this->request->getGet('from') : null;
        $to     = $this->request->getGet('to') ? (string) $this->request->getGet('to') : null;
        [$dFrom, $dTo] = $m->dateRange($filter, $from, $to, $companyId ? current_company() : null);

        // The live feed is polled every ~20s; cache the query result for 12s so
        // concurrent viewers share one computation (busted instantly on a write).
        $payload = dash_remember($companyId, 'live:' . $filter . ':' . md5(($dFrom ?? '') . '|' . ($dTo ?? '')), 12, function () use ($m, $companyId, $dFrom, $dTo) {
            $txn = $m->txnSummary($companyId, $dFrom, $dTo);
            $recent = array_map(function ($r) {
                return [
                    'txn_no' => $r['txn_no'] ?? ('#' . $r['id']),
                    'name'   => $r['name'],
                    'type'   => $r['type'],
                    'amount' => (float) $r['amount'],
                    'mode'   => $r['payment_mode'] ?? 'cash',
                    'status' => $r['status'] ?? 'paid',
                    'date'   => $r['txn_date'],
                    'href'   => site_url('transactions/report') . '?period=day&date=' . $r['txn_date'],
                    'ago'    => $this->timeAgo($r['created_at'] ?? $r['txn_date']),
                ];
            }, $m->recentTxns($companyId, 8));

            return [
                'kpis'   => [
                    'cash_in_hand' => $txn['cash_in_hand'],
                    'jama'         => $txn['jama'],
                    'naam'         => $txn['naam'],
                    'net'          => $txn['net'],
                    'today_jama'   => $txn['today_jama'],
                    'today_naam'   => $txn['today_naam'],
                    'pending'      => $txn['pending'],
                    'count'        => $txn['count'],
                ],
                'counts' => $m->liveCounts($companyId),
                'recent' => $recent,
            ];
        });

        return $this->response
            ->setHeader('Cache-Control', 'no-store')
            ->setJSON(array_merge([
                'status' => 'ok',
                'time'   => date('c'),
                'clock'  => date('H:i:s'),
            ], $payload));
    }

    /** Compact "x mins ago" relative time for the activity feed. */
    private function timeAgo(?string $ts): string
    {
        if (! $ts) {
            return '';
        }
        $diff = time() - (int) strtotime($ts);
        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            return floor($diff / 60) . 'm ago';
        }
        if ($diff < 86400) {
            return floor($diff / 3600) . 'h ago';
        }
        return floor($diff / 86400) . 'd ago';
    }
}
