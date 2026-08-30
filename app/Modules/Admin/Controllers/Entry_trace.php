<?php

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Modules\Admin\Models\EntryTraceModel;

/**
 * Entry_trace — CI4 port of admin/Entry_trace. Read-only audit viewer over
 * aa_entry_trace (who/what/where per entry: user, source, IP, GPS). Super-admin
 * only (exposes staff IP + location). In CI3 index/listing were merged into the
 * Monitor module; until Monitor is ported, this renders its own standalone
 * listing. Retention controls degrade gracefully if the trace helpers aren't ported.
 */
class Entry_trace extends BaseController
{
    protected $helpers = ['url', 'app'];

    private function guard()
    {
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            return redirect()->to(base_url('permission_denied'));
        }
        return null;
    }

    private function moduleLabel($slug): string
    {
        $reg = function_exists('erp_module_registry') ? erp_module_registry() : [];
        if (isset($reg[$slug])) { return $reg[$slug]; }
        $lc = array_change_key_case($reg, CASE_LOWER);
        $k  = strtolower((string) $slug);
        if (isset($lc[$k])) { return $lc[$k]; }
        return ucwords(str_replace('_', ' ', (string) $slug));
    }

    public function index()
    {
        return $this->listing();
    }

    public function listing()
    {
        if ($r = $this->guard()) { return $r; }

        $model   = new EntryTraceModel();
        $modules = $model->filterModules();
        $labels  = [];
        foreach ($modules as $m) { $labels[$m->module] = $this->moduleLabel($m->module); }

        return _layout('\App\Modules\Admin\Views\entry_trace\listing', [
            'title'          => 'Entry Trace / Audit · C R Industries ERP',
            'modules'        => $modules,
            'users'          => $model->filterUsers(),
            'module_labels'  => $labels,
            'retention_days' => function_exists('entry_trace_retention_days') ? entry_trace_retention_days() : 0,
            'prunable_count' => function_exists('entry_trace_prunable_count') ? entry_trace_prunable_count() : 0,
        ]);
    }

    /** Server-side DataTables feed. */
    public function listing_data()
    {
        if ($r = $this->guard()) { return $r; }

        $model = new EntryTraceModel();
        $rows  = $model->fetch();
        $out   = [];

        foreach ($rows as $r) {
            $mod = '<span class="et-mod">' . esc($this->moduleLabel($r->module)) . '</span><span class="et-slug">' . esc($r->module) . '</span>';

            $eid = (int) $r->entry_id;
            if (strtolower((string) $r->action) === 'delete') {
                $viewUrl = base_url('admin/report/deleted_entries'); $vtitle = 'Open the Deleted Rokad Entries log';
            } else {
                $viewUrl = base_url('admin/account/edit/' . ID_encode($eid)); $vtitle = 'Open this entry';
            }
            $entry = '<a class="et-entry-link" href="' . esc($viewUrl, 'attr') . '" target="_blank" rel="noopener" title="' . esc($vtitle, 'attr') . '">#' . $eid . ' <i class="ti-new-window"></i></a>';

            $action = '<span class="et-act et-a-' . strtolower((string) $r->action) . '">' . esc(ucfirst($r->action)) . '</span>';

            $uname = trim((string) $r->full_name) !== '' ? esc($r->full_name)
                   : (trim((string) $r->user_name) !== '' ? esc($r->user_name)
                   : ($r->user_id ? '#' . (int) $r->user_id : '<span class="text-muted">—</span>'));
            $user = '<div class="et-user">' . $uname . '</div>';

            $srcLc = strtolower((string) $r->entry_source);
            $src = $srcLc === 'app' ? '<span class="et-src et-src-app"><i class="ti-mobile"></i> App</span>'
                 : ($srcLc === 'system' ? '<span class="et-src et-src-sys"><i class="ti-server"></i> System</span>'
                 : '<span class="et-src et-src-web"><i class="ti-desktop"></i> Web</span>');

            $ip = trim((string) $r->ip_address) !== '' ? '<span class="et-ip">' . esc($r->ip_address) . '</span>' : '<span class="text-muted">—</span>';

            if ($r->latitude !== null && $r->longitude !== null && $r->latitude !== '' && $r->longitude !== '') {
                $q = rawurlencode($r->latitude . ',' . $r->longitude);
                $loc = '<a class="et-loc" target="_blank" rel="noopener" href="https://www.google.com/maps?q=' . $q . '"><i class="ti-location-pin"></i> ' . esc($r->latitude) . ', ' . esc($r->longitude) . '</a>'
                     . ($r->accuracy ? '<span class="et-acc">±' . esc($r->accuracy) . 'm</span>' : '');
            } else {
                $loc = '<span class="text-muted">Not captured</span>';
            }

            $when = ! empty($r->created_at) ? date('d-m-Y h:i A', strtotime($r->created_at)) : '-';
            $ua = trim((string) $r->user_agent) !== ''
                ? '<span class="et-when">' . esc($when) . '</span><span class="et-ua" title="' . esc($r->user_agent, 'attr') . '">' . esc(mb_substr($r->user_agent, 0, 42)) . '</span>'
                : '<span class="et-when">' . esc($when) . '</span>';

            $out[] = [$mod, $entry, $action, $user, $src, $ip, $loc, $ua];
        }

        return $this->response->setJSON([
            'draw'            => (int) $this->request->getPost('draw'),
            'recordsTotal'    => $model->total(),
            'recordsFiltered' => $model->countFiltered(),
            'data'            => $out,
            'stats'           => $model->stats(),
        ]);
    }

    /** Save the retention window (guarded — degrades if the trace helper isn't ported). */
    public function save_retention()
    {
        if ($r = $this->guard()) { return $r; }
        if (! function_exists('entry_trace_save_retention')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Retention helper unavailable in CI4 yet.']);
        }
        $days = max(0, (int) $this->request->getPost('retention_days'));
        $saved = entry_trace_save_retention($days, (int) (currentuserinfo()->id ?? 0));
        return $this->response->setJSON([
            'status'         => 'success',
            'retention_days' => (int) $saved,
            'prunable_count' => function_exists('entry_trace_prunable_count') ? entry_trace_prunable_count((int) $saved) : 0,
            'message'        => $saved > 0 ? ('Retention set to ' . $saved . ' days.') : 'Retention set to keep all history.',
        ]);
    }
}
