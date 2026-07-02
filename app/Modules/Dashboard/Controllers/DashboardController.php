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
        $loginTrend  = $dash->loginTrend(14);
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
            'inline_js'    => 'window.ERP_DASH={analyticsUrl:' . json_encode(site_url('dashboard/analytics')) . '};',
        ];

        return $this->render('index', $data);
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
        $block = (string) ($this->request->getGet('block') ?? 'all');

        $payload = [];
        $wants   = static fn (string $b) => $block === 'all' || $block === $b;

        if ($wants('kpis'))        { $payload['kpis']        = $model->kpis(); }
        if ($wants('logins'))      { $payload['logins']      = $model->loginTrend(14); }
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
}
