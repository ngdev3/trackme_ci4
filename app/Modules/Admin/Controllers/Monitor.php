<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\MonitorModel;

/**
 * Monitor — Activity & Audit Monitor (admin/monitor). CI4 port of the overview
 * slice: KPIs, daily/hourly charts, online-now and a unified recent-activity
 * timeline over daily_traffic + aa_login_detail + aa_entry_trace. The tabbed nav
 * (_tabs) carries a date/user filter across the sibling tabs. rbac('monitor').
 */
class Monitor extends BaseController
{
    protected $helpers = ['url', 'app', 'permission'];

    private function baseData(string $active): array
    {
        $m = new MonitorModel();
        return [
            'active'   => $active,
            'is_super' => function_exists('erp_is_super_admin') ? erp_is_super_admin() : false,
            'filters'  => $m->filters(),
            'users'    => $m->users_list(),
        ];
    }

    public function index()
    {
        return redirect()->to(base_url('admin/monitor/overview'));
    }

    public function overview()
    {
        $m = new MonitorModel();
        $data = $this->baseData('overview');
        $f = $data['filters'];
        $data['kpis']   = $m->overview_kpis($f);
        $data['series'] = $m->activity_series($f);
        $data['hours']  = $m->hourly_series($f);
        $data['online'] = $m->online_now();
        $data['recent'] = $m->recent_activity($f, 18, $data['is_super']);
        $data['title']  = 'Track (The Rest Accounting Key) || Activity & Audit Monitor';
        return _layout('\App\Modules\Admin\Views\monitor\overview', $data);
    }
}
