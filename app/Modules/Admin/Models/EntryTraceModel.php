<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * EntryTraceModel — CI4 port of admin/models/Entry_trace_mod. Read-only viewer
 * over the aa_entry_trace audit log (who/what/where per business entry), firm-
 * scoped, with module/source/action/user/IP/date/geo filters + DataTables paging.
 */
class EntryTraceModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function templateId(): int
    {
        $fy = function_exists('fy') ? fy() : null;
        if (is_object($fy) && isset($fy->template_id) && $fy->template_id !== '') {
            return (int) $fy->template_id;
        }
        $ui = function_exists('currentuserinfo') ? currentuserinfo() : null;
        if (is_object($ui) && isset($ui->default_firm)) {
            return (int) $ui->default_firm;
        }
        return 0;
    }

    /** Shared WHERE: firm scope + all active POST filters. */
    private function applyFilters($b): void
    {
        $req = service('request');
        $b->where('t.template_id', $this->templateId());

        foreach (['f_module' => 't.module', 'f_source' => 't.entry_source', 'f_action' => 't.action'] as $post => $col) {
            $v = $req->getPost($post);
            if ($v !== null && $v !== '' && $v !== 'all') { $b->where($col, $v); }
        }
        $user = $req->getPost('f_user');
        if ($user !== null && $user !== '' && $user !== 'all') { $b->where('t.user_id', (int) $user); }

        $ip = trim((string) $req->getPost('f_ip'));
        if ($ip !== '') { $b->like('t.ip_address', $ip); }

        $from = trim((string) $req->getPost('f_from'));
        if ($from !== '') { $b->where('DATE(t.created_at) >=', $from); }
        $to = trim((string) $req->getPost('f_to'));
        if ($to !== '') { $b->where('DATE(t.created_at) <=', $to); }

        if ($req->getPost('f_geo') === '1') { $b->where('t.latitude IS NOT NULL', null, false); }

        $search = $req->getPost('search');
        $sv = is_array($search) && isset($search['value']) ? trim((string) $search['value']) : '';
        if ($sv !== '') {
            $b->groupStart()->like('t.ip_address', $sv)->orLike('t.module', $sv)->orLike('t.user_name', $sv)->orLike('t.entry_id', $sv)->groupEnd();
        }
    }

    public function total(): int
    {
        return (int) $this->db()->table('aa_entry_trace')->where('template_id', $this->templateId())->countAllResults();
    }

    public function countFiltered(): int
    {
        $b = $this->db()->table('aa_entry_trace t');
        $this->applyFilters($b);
        return (int) $b->countAllResults();
    }

    public function fetch(): array
    {
        $req = service('request');
        $b = $this->db()->table('aa_entry_trace t')
            ->select('t.*, TRIM(CONCAT(COALESCE(u.first_name,""),\' \',COALESCE(u.last_name,""))) AS full_name', false)
            ->join('users u', 'u.id = t.user_id', 'left');
        $this->applyFilters($b);

        $length = (int) $req->getPost('length');
        if ($length <= 0) { $length = 25; }
        return $b->orderBy('t.id', 'DESC')->limit($length, (int) $req->getPost('start'))->get()->getResult();
    }

    public function filterUsers(): array
    {
        return $this->db()->table('aa_entry_trace t')
            ->distinct()
            ->select('t.user_id, TRIM(CONCAT(COALESCE(u.first_name,""),\' \',COALESCE(u.last_name,""))) AS full_name, t.user_name', false)
            ->join('users u', 'u.id = t.user_id', 'left')
            ->where('t.template_id', $this->templateId())
            ->where('t.user_id IS NOT NULL', null, false)
            ->orderBy('full_name', 'ASC')
            ->get()->getResult();
    }

    public function filterModules(): array
    {
        return $this->db()->table('aa_entry_trace')
            ->distinct()->select('module')
            ->where('template_id', $this->templateId())
            ->orderBy('module', 'ASC')
            ->get()->getResult();
    }

    public function stats(): array
    {
        $b = $this->db()->table('aa_entry_trace t')
            ->select("COUNT(*) AS total,
                SUM(CASE WHEN t.entry_source = 'App' THEN 1 ELSE 0 END) AS app_cnt,
                SUM(CASE WHEN t.latitude IS NOT NULL THEN 1 ELSE 0 END) AS geo_cnt,
                COUNT(DISTINCT t.ip_address) AS ip_cnt,
                COUNT(DISTINCT t.user_id) AS user_cnt", false);
        $this->applyFilters($b);
        $row = $b->get()->getRow();
        return [
            'total'    => $row ? (int) $row->total : 0,
            'app_cnt'  => $row ? (int) $row->app_cnt : 0,
            'geo_cnt'  => $row ? (int) $row->geo_cnt : 0,
            'ip_cnt'   => $row ? (int) $row->ip_cnt : 0,
            'user_cnt' => $row ? (int) $row->user_cnt : 0,
        ];
    }
}
