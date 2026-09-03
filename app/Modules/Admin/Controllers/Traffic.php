<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\TrafficModel;

/**
 * Traffic — CI4 port of admin/Traffic (page-traffic viewer). In CI3 this was
 * merged into the Monitor module; until Monitor is ported this renders its own
 * standalone listing over daily_traffic.
 */
class Traffic extends BaseController
{
    protected $helpers = ['url', 'app', 'permission'];

    /**
     * Page-traffic logs expose every user's activity + IP, so — like the CI3
     * Monitor that absorbed this module — access is Super-Admin only. Enforced
     * here (not just via the RBAC filter) because 'traffic' is not a grantable
     * module key and the RBAC filter fails open for unknown keys. Returns a
     * denial Response when blocked, or null when the caller may proceed.
     */
    private function guard()
    {
        if (function_exists('erp_is_super_admin') && erp_is_super_admin()) {
            return null;
        }
        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'denied', 'message' => 'Super Admin only.']);
        }
        return redirect()->to(site_url('permission_denied'));
    }

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        if (($deny = $this->guard()) !== null) { return $deny; }
        return _layout('\App\Modules\Admin\Views\traffic\listing', [
            'title' => 'Page Traffic · C R Industries ERP',
        ]);
    }

    public function view_all()
    {
        if (($deny = $this->guard()) !== null) { return $deny; }
        $model   = new TrafficModel();
        $filters = $this->filters();
        $total   = $model->countVisits($filters, false);
        $filt    = $model->countVisits($filters, true);
        $rows    = $model->visits($filters);

        $data = [];
        $j = (int) ($this->request->getPost('start') ?? 0);
        foreach ($rows as $r) {
            $j++;
            $when = ! empty($r->trafiic_date) ? date('d M Y h:i A', strtotime($r->trafiic_date)) : '-';
            $data[] = [
                '<span>' . $j . '</span>',
                '<div><strong>' . esc($r->user_name ?: 'Guest / Unknown') . '</strong><br><small>' . esc($r->action_type ?: 'site/visit') . '</small></div>',
                '<div><i class="fa fa-link"></i> ' . esc($r->url ?: 'Unknown URL') . '</div>',
                '<span>' . $when . '</span>',
                ! empty($r->ip_address) ? '<span class="label label-default">' . esc($r->ip_address) . '</span>' : '<span class="text-muted">—</span>',
            ];
        }

        return $this->response->setJSON([
            'draw' => (int) $this->request->getPost('draw'),
            'recordsTotal' => $total, 'recordsFiltered' => $filt, 'data' => $data,
        ]);
    }

    private function filters(): array
    {
        $from = $this->request->getGet('from');
        $to   = $this->request->getGet('to');
        $valid = fn($d) => $d && (bool) strtotime($d);
        return [
            'from' => $valid($from) ? $from : date('Y-m-01'),
            'to'   => $valid($to) ? $to : date('Y-m-d'),
        ];
    }
}
