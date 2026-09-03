<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * MonitorModel — CI4 port of the Activity & Audit Monitor overview slice
 * (CI3 admin/models/Monitor_mod). Reads daily_traffic + aa_login_detail +
 * aa_entry_trace (all guarded) to drive the overview KPIs, daily/hourly charts,
 * online-now list and the recent-activity timeline. The heavier tabs (IP intel,
 * entry audit, anomalies, retention) port separately.
 */
class MonitorModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function hasEntryGeo(): bool
    {
        return $this->db()->tableExists('aa_entry_trace');
    }

    /**
     * Current firm id for scoping the entry-audit log. aa_entry_trace carries
     * template_id, so the Monitor's entry data reflects the firm selected in the
     * top-nav switcher. (daily_traffic / aa_login_detail have no firm column and
     * stay global — page views & logins are not per-firm.) Returns 0 when the
     * firm context isn't resolvable, which disables the scope (shows all firms).
     */
    private function entryFirmId(): int
    {
        if (! function_exists('fy')) { return 0; }
        $f = fy();
        return (int) ($f->template_id ?? 0);
    }

    /** Sanitised date/user filter from GET/POST (defaults: month-to-date). */
    public function filters(): array
    {
        $req = service('request');
        $pg  = function ($k) use ($req) {
            $v = $req->getPost($k);
            if ($v === null || $v === '') { $v = $req->getGet($k); }
            return (string) ($v ?? '');
        };
        $ok = function ($d) {
            if ($d === '') { return false; }
            $p = \DateTime::createFromFormat('Y-m-d', $d);
            return $p && $p->format('Y-m-d') === $d;
        };
        $from = $pg('from');
        $to   = $pg('to');
        $user = $pg('user');
        return [
            'from' => $ok($from) ? $from : date('Y-m-01'),
            'to'   => $ok($to) ? $to : date('Y-m-d'),
            'user' => ($user !== '' && ctype_digit($user)) ? (int) $user : 0,
        ];
    }

    /** Users for the filter dropdown. */
    public function users_list(): array
    {
        return $this->db()->table('users')
            ->select("id, COALESCE(NULLIF(TRIM(CONCAT(first_name,' ',last_name)),''), CONCAT('User #',id)) nm", false)
            ->orderBy('nm', 'asc')
            ->get()->getResult();
    }

    public function online_now(int $minutes = 15): array
    {
        $cutoff = date('Y-m-d H:i:s', time() - $minutes * 60);
        return $this->db()->table('daily_traffic dt')
            ->select("dt.user_id, MAX(dt.trafiic_date) last_seen, MAX(dt.ip_address) ip, MAX(dt.action_type) last_action,
                COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''),'Guest / Unknown') user_name", false)
            ->join('users u', 'u.id = dt.user_id', 'left')
            ->where('dt.trafiic_date >=', $cutoff)
            ->where('dt.user_id IS NOT NULL', null, false)
            ->groupBy('dt.user_id')->orderBy('last_seen', 'desc')->limit(30)
            ->get()->getResult();
    }

    public function online_count(int $minutes = 15): int
    {
        $cutoff = date('Y-m-d H:i:s', time() - $minutes * 60);
        $r = $this->db()->table('daily_traffic')
            ->select('COUNT(DISTINCT user_id) c', false)
            ->where('trafiic_date >=', $cutoff)
            ->where('user_id IS NOT NULL', null, false)
            ->get()->getRow();
        return $r ? (int) $r->c : 0;
    }

    public function overview_kpis(array $f): array
    {
        $db = $this->db();
        $from = $f['from']; $to = $f['to'];

        $b = $db->table('daily_traffic')->where('DATE(trafiic_date) >=', $from)->where('DATE(trafiic_date) <=', $to);
        if (! empty($f['user'])) { $b->where('user_id', $f['user']); }
        $visits = (int) $b->countAllResults();

        $logins = 0;
        if ($db->tableExists('aa_login_detail')) {
            $b = $db->table('aa_login_detail')->where('DATE(added_date) >=', $from)->where('DATE(added_date) <=', $to);
            if (! empty($f['user'])) { $b->where('user_id', $f['user']); }
            $logins = (int) $b->countAllResults();
        }

        $tid = $this->entryFirmId();
        $ent = ['create' => 0, 'update' => 0, 'delete' => 0, 'geo' => 0, 'total' => 0];
        if ($this->hasEntryGeo()) {
            $b = $db->table('aa_entry_trace')
                ->select("COUNT(*) total, SUM(action='create') c_create, SUM(action='update') c_update, SUM(action='delete') c_delete, SUM(latitude IS NOT NULL) c_geo", false)
                ->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to);
            if ($tid) { $b->where('template_id', $tid); }
            if (! empty($f['user'])) { $b->where('user_id', $f['user']); }
            $r = $b->get()->getRow();
            if ($r) {
                $ent['total'] = (int) $r->total; $ent['create'] = (int) $r->c_create;
                $ent['update'] = (int) $r->c_update; $ent['delete'] = (int) $r->c_delete; $ent['geo'] = (int) $r->c_geo;
            }
        }

        $users = [];
        $ips   = [];
        foreach ($db->table('daily_traffic')->select('DISTINCT user_id', false)
            ->where('DATE(trafiic_date) >=', $from)->where('DATE(trafiic_date) <=', $to)
            ->where('user_id IS NOT NULL', null, false)->get()->getResult() as $x) { $users[(int) $x->user_id] = 1; }
        foreach ($db->table('daily_traffic')->select('DISTINCT ip_address', false)
            ->where('DATE(trafiic_date) >=', $from)->where('DATE(trafiic_date) <=', $to)
            ->where('ip_address IS NOT NULL', null, false)->get()->getResult() as $x) { if ($x->ip_address !== '') { $ips[$x->ip_address] = 1; } }
        if ($this->hasEntryGeo()) {
            $bi = $db->table('aa_entry_trace')->select('DISTINCT ip_address', false)
                ->where('DATE(created_at) >=', $from)->where('DATE(created_at) <=', $to)
                ->where('ip_address IS NOT NULL', null, false);
            if ($tid) { $bi->where('template_id', $tid); }
            foreach ($bi->get()->getResult() as $x) { if ($x->ip_address !== '') { $ips[$x->ip_address] = 1; } }
        }

        return [
            'visits' => $visits, 'logins' => $logins, 'entries' => $ent['total'],
            'entries_create' => $ent['create'], 'entries_update' => $ent['update'], 'entries_delete' => $ent['delete'],
            'geo' => $ent['geo'], 'users' => count($users), 'ips' => count($ips), 'online' => $this->online_count(),
        ];
    }

    public function activity_series(array $f): array
    {
        $db = $this->db();
        $to = $f['to'];
        $cur = strtotime($f['from']); $end = strtotime($to);
        if (($end - $cur) / 86400 > 92) { $cur = $end - 92 * 86400; }
        $days = [];
        for ($t = $cur; $t <= $end; $t += 86400) { $days[date('Y-m-d', $t)] = ['visits' => 0, 'entries' => 0]; }

        $b = $db->table('daily_traffic')->select('DATE(trafiic_date) d, COUNT(*) c', false)
            ->where('DATE(trafiic_date) >=', date('Y-m-d', $cur))->where('DATE(trafiic_date) <=', $to);
        if (! empty($f['user'])) { $b->where('user_id', $f['user']); }
        foreach ($b->groupBy('d')->get()->getResult() as $r) { if (isset($days[$r->d])) { $days[$r->d]['visits'] = (int) $r->c; } }

        if ($this->hasEntryGeo()) {
            $b = $db->table('aa_entry_trace')->select('DATE(created_at) d, COUNT(*) c', false)
                ->where('DATE(created_at) >=', date('Y-m-d', $cur))->where('DATE(created_at) <=', $to);
            if (($tid = $this->entryFirmId())) { $b->where('template_id', $tid); }
            if (! empty($f['user'])) { $b->where('user_id', $f['user']); }
            foreach ($b->groupBy('d')->get()->getResult() as $r) { if (isset($days[$r->d])) { $days[$r->d]['entries'] = (int) $r->c; } }
        }

        $out = ['labels' => [], 'visits' => [], 'entries' => []];
        foreach ($days as $d => $v) {
            $out['labels'][] = date('d M', strtotime($d));
            $out['visits'][] = $v['visits'];
            $out['entries'][] = $v['entries'];
        }
        return $out;
    }

    public function hourly_series(array $f): array
    {
        $db = $this->db();
        $buckets = array_fill(0, 24, 0);
        $b = $db->table('daily_traffic')->select('HOUR(trafiic_date) h, COUNT(*) c', false)
            ->where('DATE(trafiic_date) >=', $f['from'])->where('DATE(trafiic_date) <=', $f['to']);
        if (! empty($f['user'])) { $b->where('user_id', $f['user']); }
        foreach ($b->groupBy('h')->get()->getResult() as $r) { $buckets[(int) $r->h] += (int) $r->c; }
        if ($this->hasEntryGeo()) {
            $b = $db->table('aa_entry_trace')->select('HOUR(created_at) h, COUNT(*) c', false)
                ->where('DATE(created_at) >=', $f['from'])->where('DATE(created_at) <=', $f['to']);
            if (($tid = $this->entryFirmId())) { $b->where('template_id', $tid); }
            if (! empty($f['user'])) { $b->where('user_id', $f['user']); }
            foreach ($b->groupBy('h')->get()->getResult() as $r) { $buckets[(int) $r->h] += (int) $r->c; }
        }
        return $buckets;
    }

    /** Unified recent-activity stream (visits + entries + logins), newest first. */
    public function recent_activity(array $f, int $limit = 18, bool $include_entries = true): array
    {
        $db = $this->db();
        $from = $f['from']; $to = $f['to']; $uid = (int) $f['user'];
        $rows = [];

        $b = $db->table('daily_traffic dt')
            ->select("dt.trafiic_date ts, dt.user_id, dt.action_type, dt.url, dt.ip_address,
                COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''),'Guest / Unknown') user_name", false)
            ->join('users u', 'u.id = dt.user_id', 'left')
            ->where('DATE(dt.trafiic_date) >=', $from)->where('DATE(dt.trafiic_date) <=', $to);
        if ($uid) { $b->where('dt.user_id', $uid); }
        foreach ($b->orderBy('dt.trafiic_date', 'desc')->limit($limit)->get()->getResult() as $r) {
            $rows[] = (object) ['ts' => $r->ts, 'kind' => 'visit', 'user_id' => $r->user_id, 'user_name' => $r->user_name,
                'detail' => $r->action_type ?: $r->url, 'ip' => $r->ip_address, 'extra' => $r->url];
        }

        if ($include_entries && $this->hasEntryGeo()) {
            $b = $db->table('aa_entry_trace t')
                ->select("t.created_at ts, t.user_id, t.action, t.module, t.entry_id, t.ip_address, t.latitude, t.longitude,
                    COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''), t.user_name,'Guest / Unknown') user_name", false)
                ->join('users u', 'u.id = t.user_id', 'left')
                ->where('DATE(t.created_at) >=', $from)->where('DATE(t.created_at) <=', $to);
            if (($tid = $this->entryFirmId())) { $b->where('t.template_id', $tid); }
            if ($uid) { $b->where('t.user_id', $uid); }
            foreach ($b->orderBy('t.created_at', 'desc')->limit($limit)->get()->getResult() as $r) {
                $geo = ($r->latitude !== null && $r->longitude !== null) ? ($r->latitude . ',' . $r->longitude) : '';
                $rows[] = (object) ['ts' => $r->ts, 'kind' => 'entry_' . strtolower((string) $r->action), 'user_id' => $r->user_id,
                    'user_name' => $r->user_name, 'detail' => $r->module . ' #' . $r->entry_id, 'ip' => $r->ip_address, 'extra' => $geo];
            }
        }

        if ($db->tableExists('aa_login_detail')) {
            $b = $db->table('aa_login_detail ald')
                ->select("ald.added_date ts, ald.user_id, ald.REMOTE_ADDR ip, ald.REQUEST_URI uri,
                    COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''),'Guest / Unknown') user_name", false)
                ->join('users u', 'u.id = ald.user_id', 'left')
                ->where('DATE(ald.added_date) >=', $from)->where('DATE(ald.added_date) <=', $to);
            if ($uid) { $b->where('ald.user_id', $uid); }
            foreach ($b->orderBy('ald.added_date', 'desc')->limit($limit)->get()->getResult() as $r) {
                $rows[] = (object) ['ts' => $r->ts, 'kind' => 'login', 'user_id' => $r->user_id, 'user_name' => $r->user_name,
                    'detail' => 'Signed in', 'ip' => $r->ip, 'extra' => $r->uri];
            }
        }

        usort($rows, fn ($a, $b) => strtotime($b->ts) - strtotime($a->ts));
        return array_slice($rows, 0, $limit);
    }
}
