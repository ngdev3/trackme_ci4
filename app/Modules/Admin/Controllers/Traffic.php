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
    protected $helpers = ['url', 'app'];

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        return _layout('\App\Modules\Admin\Views\traffic\listing', [
            'title' => 'Page Traffic · C R Industries ERP',
        ]);
    }

    public function view_all()
    {
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
