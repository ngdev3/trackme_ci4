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

    /** Hard-gate the sensitive tabs to Super Admin. */
    private function superOnly(bool $ajax = false)
    {
        if (function_exists('erp_is_super_admin') && erp_is_super_admin()) { return null; }
        return $ajax
            ? $this->response->setStatusCode(403)->setJSON(['status' => 'denied', 'message' => 'Super Admin only.'])
            : redirect()->to(site_url('permission_denied'));
    }

    private function moduleLabel(string $slug): string
    {
        $reg = function_exists('erp_module_registry') ? erp_module_registry() : [];
        if (isset($reg[$slug])) { return $reg[$slug]; }
        $lc = array_change_key_case($reg, CASE_LOWER);
        $k = strtolower($slug);
        return $lc[$k] ?? ucwords(str_replace('_', ' ', $slug));
    }

    private function esc($s): string { return esc((string) $s); }

    /* ===================== Activity Scores (super) ===================== */
    public function scores()
    {
        if (($d = $this->superOnly()) !== null) { return $d; }
        $data = $this->baseData('scores');
        $data['scores'] = (new MonitorModel())->user_scores($data['filters']);
        $data['title']  = 'Track (The Rest Accounting Key) || Monitor — Activity Scores';
        return _layout('\App\Modules\Admin\Views\monitor\scores', $data);
    }

    /* ===================== Traffic ===================== */
    public function traffic()
    {
        $m = new MonitorModel();
        $data = $this->baseData('traffic');
        $f = $data['filters'];
        $data['summary']       = $m->trafficSummary($f);
        $data['top_pages']     = $m->topPages($f);
        $data['user_activity'] = $m->userActivity($f);
        $data['title'] = 'Track (The Rest Accounting Key) || Monitor — Page Traffic';
        return _layout('\App\Modules\Admin\Views\monitor\traffic', $data);
    }

    public function traffic_data()
    {
        $m = new MonitorModel(); $f = $m->filters(); $p = $this->request->getPost();
        $search = trim((string) ($p['search']['value'] ?? ''));
        $start = (int) ($p['start'] ?? 0); $length = $p['length'] ?? 10;
        $total = $m->countVisits($f, false); $filt = $m->countVisits($f, true, $search);
        $rows = $m->visits($f, $search, $start, $length);
        $out = [];
        foreach ($rows as $i => $r) {
            $out[] = [
                '<span class="mon-serial">' . ($start + $i + 1) . '</span>',
                '<div class="mon-user"><b>' . $this->esc($r->user_name) . '</b><small>' . $this->esc($r->action_type ?: 'site/visit') . '</small></div>',
                '<div class="mon-url"><i class="ti-link"></i> ' . $this->esc($r->url ?: 'Unknown') . '</div>',
                '<span class="mon-when">' . (! empty($r->trafiic_date) ? date('d M Y h:i A', strtotime($r->trafiic_date)) : '-') . '</span>',
                ! empty($r->ip_address) ? '<span class="mon-ip">' . $this->esc($r->ip_address) . '</span>' : '<span class="text-muted">—</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) ($p['draw'] ?? 0), 'recordsTotal' => $total, 'recordsFiltered' => $filt, 'data' => $out]);
    }

    /* ===================== Logins ===================== */
    public function logins()
    {
        $data = $this->baseData('logins');
        $data['title'] = 'Track (The Rest Accounting Key) || Monitor — Logins';
        return _layout('\App\Modules\Admin\Views\monitor\logins', $data);
    }

    public function logins_data()
    {
        $m = new MonitorModel(); $f = $m->filters(); $p = $this->request->getPost();
        $r = $m->loginsData($f, trim((string) ($p['search']['value'] ?? '')), (int) ($p['start'] ?? 0), $p['length'] ?? 10);
        $out = [];
        foreach ($r['rows'] as $x) {
            $ua = (string) $x->HTTP_USER_AGENT;
            $out[] = [
                '<div class="mon-user"><b>' . $this->esc($x->user_name) . '</b><small>ID ' . (int) $x->user_id . '</small></div>',
                ! empty($x->REMOTE_ADDR) ? '<span class="mon-ip">' . $this->esc($x->REMOTE_ADDR) . '</span>' : '<span class="text-muted">—</span>',
                '<span class="mon-ua" title="' . $this->esc($ua) . '">' . $this->esc(mb_substr($ua, 0, 46)) . '</span>',
                '<span class="mon-when">' . (! empty($x->added_date) ? date('d M Y h:i A', strtotime($x->added_date)) : '-') . '</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) ($p['draw'] ?? 0), 'recordsTotal' => $r['total'], 'recordsFiltered' => $r['filtered'], 'data' => $out]);
    }

    /* ===================== Login Security ===================== */
    public function login_security()
    {
        $m = new MonitorModel();
        $data = $this->baseData('login_security');
        $data['stats']   = $m->loginAttemptsStats($data['filters']);
        $data['top_ips'] = $m->loginAttemptsTopIps($data['filters']);
        $data['title'] = 'Track (The Rest Accounting Key) || Monitor — Login Security';
        return _layout('\App\Modules\Admin\Views\monitor\login_security', $data);
    }

    public function login_security_data()
    {
        $m = new MonitorModel(); $f = $m->filters(); $p = $this->request->getPost();
        $r = $m->loginAttemptsData($f, trim((string) ($p['search']['value'] ?? '')), (int) ($p['start'] ?? 0), $p['length'] ?? 10);
        $out = [];
        foreach ($r['rows'] as $x) {
            $ua = (string) $x->user_agent;
            $out[] = [
                '<span class="mon-when">' . (! empty($x->attempted_at) ? date('d M Y h:i A', strtotime($x->attempted_at)) : '-') . '</span>',
                ! empty($x->email) ? '<b>' . $this->esc($x->email) . '</b>' : '<span class="text-muted">—</span>',
                ! empty($x->ip_address) ? '<span class="mon-ip">' . $this->esc($x->ip_address) . '</span>' : '<span class="text-muted">—</span>',
                '<span class="mon-kind k-entry_delete">' . $this->esc($x->reason ?: 'failed') . '</span>',
                '<span class="mon-ua" title="' . $this->esc($ua) . '">' . $this->esc(mb_substr($ua, 0, 46)) . '</span>',
            ];
        }
        return $this->response->setJSON(['draw' => (int) ($p['draw'] ?? 0), 'recordsTotal' => $r['total'], 'recordsFiltered' => $r['filtered'], 'data' => $out]);
    }

    /* ===================== Timeline ===================== */
    public function timeline()
    {
        $data = $this->baseData('timeline');
        $data['rows'] = (new MonitorModel())->timeline($data['filters'], 200, $data['is_super']);
        $data['title'] = 'Track (The Rest Accounting Key) || Monitor — Activity Timeline';
        return _layout('\App\Modules\Admin\Views\monitor\timeline', $data);
    }

    /* ===================== IP & Location ===================== */
    public function ip_intel()
    {
        $m = new MonitorModel();
        $data = $this->baseData('ip_intel');
        $f = $data['filters'];
        $data['ips'] = $m->ip_intel($f, $data['is_super']);

        // Geolocate every IP in the rollup (cached) and attach city/country/isp.
        $ipList = array_map(fn ($r) => $r->ip, $data['ips']);
        $geo = $m->geolocate_ips($ipList);
        $markers = [];
        foreach ($data['ips'] as $row) {
            $g = $geo[$row->ip] ?? null;
            $row->city       = $g->city ?? null;
            $row->country    = $g->country ?? null;
            $row->region     = $g->region ?? null;
            $row->isp        = $g->isp ?? null;
            $row->geo_status = $g->status ?? 'unknown';
            if ($g && ($g->status ?? '') === 'ok' && $g->lat !== null && $g->lng !== null) {
                $markers[] = [
                    'lat' => (float) $g->lat, 'lng' => (float) $g->lng, 'ip' => $row->ip, 'version' => (int) $row->version,
                    'city' => $g->city, 'region' => $g->region, 'country' => $g->country, 'isp' => $g->isp,
                    'hits' => (int) $row->hits, 'users' => (int) $row->user_count, 'modules' => '',
                    'first' => ! empty($row->first) ? date('d M Y h:i A', strtotime($row->first)) : '',
                    'last'  => ! empty($row->last) ? date('d M Y h:i A', strtotime($row->last)) : '',
                ];
            }
        }
        $data['ip_markers']    = $markers;
        $data['points']        = $m->geo_points($f);          // exact device GPS from entry audit
        $data['module_access'] = [];
        $data['module_labels'] = [];

        $v4 = 0; $v6 = 0;
        foreach ($data['ips'] as $row) { if ($row->version === 6) { $v6++; } elseif ($row->version === 4) { $v4++; } }
        $data['v4_count'] = $v4; $data['v6_count'] = $v6;
        $data['title'] = 'Track (The Rest Accounting Key) || Monitor — IP & Location';
        return _layout('\App\Modules\Admin\Views\monitor\ip_intel', $data);
    }

    /* ===================== Anomalies ===================== */
    public function anomalies()
    {
        $data = $this->baseData('anomalies');
        $data['flags'] = (new MonitorModel())->anomalies($data['filters'], $data['is_super']);
        $data['title'] = 'Track (The Rest Accounting Key) || Monitor — Anomalies';
        return _layout('\App\Modules\Admin\Views\monitor\anomalies', $data);
    }

    /* ===================== Entry Audit (super) ===================== */
    public function entries()
    {
        if (($d = $this->superOnly()) !== null) { return $d; }
        $m = new MonitorModel();
        $data = $this->baseData('entries');
        $data['modules'] = $m->entryModules();
        $labels = [];
        foreach ($data['modules'] as $mm) { $labels[$mm->module] = $this->moduleLabel($mm->module); }
        $data['module_labels'] = $labels;
        $data['retention_days'] = 0;
        $data['title'] = 'Track (The Rest Accounting Key) || Monitor — Entry Audit';
        return _layout('\App\Modules\Admin\Views\monitor\entries', $data);
    }

    public function entries_data()
    {
        if (($d = $this->superOnly(true)) !== null) { return $d; }
        $m = new MonitorModel();
        $r = $m->entryData($this->request->getPost() ?? []);
        $out = [];
        foreach ($r['rows'] as $row) {
            $mod = '<span class="et-mod">' . $this->esc($this->moduleLabel($row->module)) . '</span><span class="et-slug">' . $this->esc($row->module) . '</span>';
            $eid = (int) $row->entry_id;
            $act = '<span class="et-act et-a-' . strtolower((string) $row->action) . '">' . $this->esc(ucfirst($row->action)) . '</span>';
            $uname = trim((string) ($row->full_name ?? '')) !== '' ? $row->full_name : (trim((string) ($row->user_name ?? '')) !== '' ? $row->user_name : ($row->user_id ? '#' . $row->user_id : '—'));
            $user = '<div class="et-user">' . $this->esc($uname) . '</div>';
            $src = strtolower((string) $row->entry_source) === 'app'
                ? '<span class="et-src et-src-app"><i class="ti-mobile"></i> App</span>'
                : (strtolower((string) $row->entry_source) === 'system'
                    ? '<span class="et-src et-src-sys"><i class="ti-server"></i> System</span>'
                    : '<span class="et-src et-src-web"><i class="ti-desktop"></i> Web</span>');
            $ip = trim((string) $row->ip_address) !== '' ? '<span class="et-ip">' . $this->esc($row->ip_address) . '</span>' : '<span class="text-muted">—</span>';
            if ($row->latitude !== null && $row->longitude !== null && $row->latitude !== '' && $row->longitude !== '') {
                $q = rawurlencode($row->latitude . ',' . $row->longitude);
                $loc = '<a class="et-loc" target="_blank" rel="noopener" href="https://www.google.com/maps?q=' . $q . '"><i class="ti-location-pin"></i> ' . $this->esc($row->latitude) . ', ' . $this->esc($row->longitude) . '</a>';
            } else { $loc = '<span class="text-muted">Not captured</span>'; }
            $when = ! empty($row->created_at) ? date('d-m-Y h:i A', strtotime($row->created_at)) : '-';
            $out[] = [$mod, '#' . $eid, $act, $user, $src, $ip, $loc, '<span class="et-when">' . $this->esc($when) . '</span>'];
        }
        return $this->response->setJSON(['draw' => (int) ($this->request->getPost('draw') ?? 0), 'recordsTotal' => $r['total'], 'recordsFiltered' => $r['filtered'], 'data' => $out, 'stats' => $r['stats']]);
    }

    /* ===================== Retention (super) ===================== */
    public function retention()
    {
        if (($d = $this->superOnly()) !== null) { return $d; }
        if (strtoupper($this->request->getMethod()) === 'POST') {
            $db = \Config\Database::connect();
            $tdays = (int) $this->request->getPost('traffic_days');
            if ($tdays > 0 && $db->tableExists('aa_traffic_settings')) {
                $row = $db->table('aa_traffic_settings')->limit(1)->get()->getRow();
                $d2 = ['retention_days' => $tdays, 'updated_date' => date('Y-m-d H:i:s')];
                if ($row) { $db->table('aa_traffic_settings')->where('id', (int) $row->id)->update($d2); }
                else { $db->table('aa_traffic_settings')->insert($d2); }
            }
            if (function_exists('flash_toast')) { flash_toast('Retention settings updated.', 'success', 'Saved'); }
            return redirect()->to(base_url('admin/monitor/retention'))->with('success', 'Retention settings updated.');
        }
        $db = \Config\Database::connect();
        $data = $this->baseData('retention');
        $data['traffic_row'] = $db->tableExists('aa_traffic_settings') ? $db->table('aa_traffic_settings')->limit(1)->get()->getRow() : null;
        $data['entry_days']  = 0;
        $data['entry_prune'] = 0;
        $data['traffic_max'] = 3650;
        $data['title'] = 'Track (The Rest Accounting Key) || Monitor — Retention';
        return _layout('\App\Modules\Admin\Views\monitor\retention', $data);
    }
}
