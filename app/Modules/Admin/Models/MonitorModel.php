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

    /* ==================== Timeline tab ==================== */
    public function timeline(array $f, int $limit = 200, bool $include_entries = true): array
    {
        return $this->recent_activity($f, $limit, $include_entries);
    }

    /* ==================== Traffic tab (daily_traffic) ==================== */
    private function dateScope($b, string $col, array $f)
    {
        return $b->where("DATE($col) >=", $f['from'])->where("DATE($col) <=", $f['to']);
    }

    public function trafficSummary(array $f): array
    {
        $db = $this->db();
        $total = (int) $db->table('daily_traffic')->countAllResults();
        $today = (int) $db->table('daily_traffic')->where('DATE(trafiic_date)', date('Y-m-d'))->countAllResults();
        $period = (int) $this->dateScope($db->table('daily_traffic'), 'trafiic_date', $f)->countAllResults();
        $up = $this->dateScope($db->table('daily_traffic'), 'trafiic_date', $f)->select('COUNT(DISTINCT url) c', false)->get()->getRow();
        $au = $this->dateScope($db->table('daily_traffic'), 'trafiic_date', $f)->select('COUNT(DISTINCT user_id) c', false)->where('user_id IS NOT NULL', null, false)->get()->getRow();
        return ['total' => $total, 'today' => $today, 'period' => $period,
            'unique_pages' => $up ? (int) $up->c : 0, 'active_users' => $au ? (int) $au->c : 0];
    }

    public function topPages(array $f): array
    {
        return $this->dateScope($this->db()->table('daily_traffic'), 'trafiic_date', $f)
            ->select('url, COUNT(id) total', false)->groupBy('url')->orderBy('total', 'desc')->limit(8)->get()->getResult();
    }

    public function userActivity(array $f): array
    {
        return $this->dateScope($this->db()->table('daily_traffic dt'), 'dt.trafiic_date', $f)
            ->select("dt.user_id, COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''),'Guest / Unknown') user_name,
                COUNT(dt.id) visits, COUNT(DISTINCT dt.action_type) actions, MAX(dt.trafiic_date) last_seen", false)
            ->join('users u', 'u.id = dt.user_id', 'left')->groupBy('dt.user_id, user_name')
            ->orderBy('visits', 'desc')->limit(8)->get()->getResult();
    }

    /** Visits DataTables feed (count + rows) — respects date filter + search. */
    public function countVisits(array $f, bool $withSearch, string $search = ''): int
    {
        $b = $this->dateScope($this->db()->table('daily_traffic'), 'trafiic_date', $f);
        if ($withSearch && $search !== '') { $b->like('url', $search); }
        return (int) $b->countAllResults();
    }

    public function visits(array $f, string $search, int $start, $length): array
    {
        $b = $this->dateScope($this->db()->table('daily_traffic dt'), 'dt.trafiic_date', $f)
            ->select("dt.url, dt.trafiic_date, dt.action_type, dt.ip_address,
                COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''),'Guest / Unknown') user_name", false)
            ->join('users u', 'u.id = dt.user_id', 'left')->orderBy('dt.trafiic_date', 'desc');
        if ($search !== '') { $b->like('dt.url', $search); }
        if ($length != -1) { $b->limit((int) $length, $start); }
        return $b->get()->getResult();
    }

    /* ==================== Logins tab (aa_login_detail) ==================== */
    public function loginsData(array $f, string $search, int $start, $length): array
    {
        $db = $this->db();
        if (! $db->tableExists('aa_login_detail')) { return ['total' => 0, 'filtered' => 0, 'rows' => []]; }
        $scope = function () use ($db, $f) {
            $b = $db->table('aa_login_detail ald')->join('users u', 'u.id = ald.user_id', 'left')
                ->where('DATE(ald.added_date) >=', $f['from'])->where('DATE(ald.added_date) <=', $f['to']);
            if (! empty($f['user'])) { $b->where('ald.user_id', $f['user']); }
            return $b;
        };
        $total = (int) $scope()->countAllResults();
        $fb = $scope();
        if ($search !== '') { $fb->groupStart()->like('ald.REMOTE_ADDR', $search)->orLike('u.first_name', $search)->orLike('u.last_name', $search)->groupEnd(); }
        $filtered = (int) $fb->countAllResults(false);
        $rb = $scope();
        if ($search !== '') { $rb->groupStart()->like('ald.REMOTE_ADDR', $search)->orLike('u.first_name', $search)->orLike('u.last_name', $search)->groupEnd(); }
        $rb->select("ald.user_id, ald.REMOTE_ADDR, ald.HTTP_USER_AGENT, ald.added_date,
            COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''),'Guest / Unknown') user_name", false)
            ->orderBy('ald.added_date', 'desc');
        if ($length != -1) { $rb->limit((int) $length, $start); }
        return ['total' => $total, 'filtered' => $filtered, 'rows' => $rb->get()->getResult()];
    }

    /* ==================== Login Security tab (aa_login_attempts) ==================== */
    private function attemptsScope(array $f, bool $onlyFailed = true)
    {
        $b = $this->db()->table('aa_login_attempts la')
            ->where('DATE(la.attempted_at) >=', $f['from'])->where('DATE(la.attempted_at) <=', $f['to']);
        if ($onlyFailed) { $b->where('la.success', 0); }
        return $b;
    }

    public function loginAttemptsStats(array $f): array
    {
        if (! $this->db()->tableExists('aa_login_attempts')) { return ['failures' => 0, 'success' => 0, 'ips' => 0, 'emails' => 0]; }
        $fails = (int) $this->attemptsScope($f, true)->countAllResults();
        $succ  = (int) $this->attemptsScope($f, false)->where('la.success', 1)->countAllResults();
        $ips   = (int) ($this->attemptsScope($f, true)->select('COUNT(DISTINCT la.ip_address) c', false)->get()->getRow()->c ?? 0);
        $emails= (int) ($this->attemptsScope($f, true)->select('COUNT(DISTINCT la.email) c', false)->get()->getRow()->c ?? 0);
        return ['failures' => $fails, 'success' => $succ, 'ips' => $ips, 'emails' => $emails];
    }

    public function loginAttemptsData(array $f, string $search, int $start, $length): array
    {
        if (! $this->db()->tableExists('aa_login_attempts')) { return ['total' => 0, 'filtered' => 0, 'rows' => []]; }
        $total = (int) $this->attemptsScope($f, true)->countAllResults();
        $fb = $this->attemptsScope($f, true);
        if ($search !== '') { $fb->groupStart()->like('la.email', $search)->orLike('la.ip_address', $search)->orLike('la.reason', $search)->groupEnd(); }
        $filtered = (int) $fb->countAllResults();
        $rb = $this->attemptsScope($f, true)->select('la.email, la.ip_address, la.user_agent, la.reason, la.attempted_at', false);
        if ($search !== '') { $rb->groupStart()->like('la.email', $search)->orLike('la.ip_address', $search)->orLike('la.reason', $search)->groupEnd(); }
        $rb->orderBy('la.attempted_at', 'desc');
        if ($length != -1) { $rb->limit((int) $length, $start); }
        return ['total' => $total, 'filtered' => $filtered, 'rows' => $rb->get()->getResult()];
    }

    public function loginAttemptsTopIps(array $f, int $limit = 10): array
    {
        if (! $this->db()->tableExists('aa_login_attempts')) { return []; }
        return $this->attemptsScope($f, true)
            ->select('la.ip_address, COUNT(*) fails, COUNT(DISTINCT la.email) emails, MAX(la.attempted_at) last_try', false)
            ->where('la.ip_address IS NOT NULL', null, false)
            ->groupBy('la.ip_address')->orderBy('fails', 'desc')->limit($limit)->get()->getResult();
    }

    /* ==================== IP & Location (rollup; external geo/maps deferred) ==================== */
    public function ip_intel(array $f, bool $include_entries = true): array
    {
        $db = $this->db();
        $from = $f['from']; $to = $f['to'];
        $map = [];
        $touch = function (&$map, $ip, $uid, $ts) {
            if ($ip === null || $ip === '') { return; }
            if (! isset($map[$ip])) { $map[$ip] = ['ip' => $ip, 'hits' => 0, 'users' => [], 'first' => $ts, 'last' => $ts]; }
            $map[$ip]['hits']++;
            if ($uid) { $map[$ip]['users'][(int) $uid] = 1; }
            if ($ts < $map[$ip]['first']) { $map[$ip]['first'] = $ts; }
            if ($ts > $map[$ip]['last']) { $map[$ip]['last'] = $ts; }
        };
        foreach ($this->dateScope($db->table('daily_traffic'), 'trafiic_date', $f)
            ->select('ip_address ip, user_id, trafiic_date ts', false)->where('ip_address IS NOT NULL', null, false)
            ->get()->getResult() as $r) { $touch($map, $r->ip, $r->user_id, $r->ts); }
        if ($include_entries && $this->hasEntryGeo()) {
            $b = $this->dateScope($db->table('aa_entry_trace'), 'created_at', $f)
                ->select('ip_address ip, user_id, created_at ts', false)->where('ip_address IS NOT NULL', null, false);
            if (($tid = $this->entryFirmId())) { $b->where('template_id', $tid); }
            foreach ($b->get()->getResult() as $r) { $touch($map, $r->ip, $r->user_id, $r->ts); }
        }
        if ($db->tableExists('aa_login_detail')) {
            foreach ($this->dateScope($db->table('aa_login_detail'), 'added_date', $f)
                ->select('REMOTE_ADDR ip, user_id, added_date ts', false)->where('REMOTE_ADDR IS NOT NULL', null, false)
                ->get()->getResult() as $r) { $touch($map, $r->ip, $r->user_id, $r->ts); }
        }
        $out = [];
        foreach ($map as $d) {
            $d['user_count'] = count($d['users']); unset($d['users']);
            $d['version'] = filter_var($d['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 6 : (filter_var($d['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 4 : 0);
            // Geo/MAC enrichment is deferred (needs an external geolocation API +
            // cache table); provide neutral defaults so the view renders cleanly.
            $d['geo'] = 0; $d['geo_status'] = 'unknown';
            $d['city'] = null; $d['country'] = null; $d['isp'] = null;
            $d['mac'] = null; $d['mac_vendor'] = null; $d['mac_source'] = 'remote';
            $d['modules'] = [];
            $out[] = (object) $d;
        }
        usort($out, fn ($a, $b) => $b->hits - $a->hits);
        return $out;
    }

    /* ==================== IP geolocation (ip-api.com, cached in aa_ip_geo) ==================== */
    private function ensureIpGeoTable(): void
    {
        static $done = false;
        if ($done) { return; }
        $done = true;
        $this->db()->query("CREATE TABLE IF NOT EXISTS `aa_ip_geo` (
            `ip` VARCHAR(45) NOT NULL, `version` TINYINT DEFAULT 0, `status` VARCHAR(10) DEFAULT 'ok',
            `lat` DECIMAL(10,7) NULL, `lng` DECIMAL(10,7) NULL, `city` VARCHAR(120) NULL,
            `region` VARCHAR(120) NULL, `country` VARCHAR(80) NULL, `isp` VARCHAR(160) NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP, PRIMARY KEY (`ip`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    private function ipPublic(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function ipVersion(string $ip): int
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) { return 6; }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) { return 4; }
        return 0;
    }

    /**
     * Resolve IPs to {version,status,lat,lng,city,region,country,isp}. Cached in
     * aa_ip_geo; private/reserved IPs are marked 'local'; uncached public IPs are
     * batch-looked-up from ip-api.com (free, no key). Best-effort — a network
     * failure just leaves those IPs unresolved. CI3 Monitor_mod::geolocate_ips().
     *
     * @return array<string,object> ip => row
     */
    public function geolocate_ips(array $ips, int $maxLookup = 100): array
    {
        $out = [];
        $ips = array_values(array_unique(array_filter(array_map(fn ($x) => trim((string) $x), $ips))));
        if (! $ips) { return $out; }
        $this->ensureIpGeoTable();
        $db = $this->db();

        foreach ($db->table('aa_ip_geo')->whereIn('ip', $ips)->get()->getResult() as $r) { $out[$r->ip] = $r; }

        $todo = [];
        foreach ($ips as $ip) {
            if (isset($out[$ip])) { continue; }
            $ver = $this->ipVersion($ip);
            if ($ver === 0) { continue; }
            if (! $this->ipPublic($ip)) {
                $row = ['ip' => $ip, 'version' => $ver, 'status' => 'local', 'updated_at' => date('Y-m-d H:i:s')];
                $db->table('aa_ip_geo')->replace($row);
                $out[$ip] = (object) $row;
                continue;
            }
            $todo[] = $ip;
        }
        if (! $todo) { return $out; }
        $todo = array_slice($todo, 0, $maxLookup);

        foreach (array_chunk($todo, 100) as $chunk) {
            $resolved = $this->ipapiBatch($chunk);
            foreach ($chunk as $ip) {
                $ver = $this->ipVersion($ip);
                $g = $resolved[$ip] ?? null;
                if ($g && ($g['status'] ?? '') === 'success') {
                    $row = ['ip' => $ip, 'version' => $ver, 'status' => 'ok', 'lat' => $g['lat'], 'lng' => $g['lon'],
                        'city' => $g['city'] ?? null, 'region' => $g['regionName'] ?? null,
                        'country' => $g['country'] ?? null, 'isp' => $g['isp'] ?? null, 'updated_at' => date('Y-m-d H:i:s')];
                } else {
                    $row = ['ip' => $ip, 'version' => $ver, 'status' => 'fail', 'updated_at' => date('Y-m-d H:i:s')];
                }
                $db->table('aa_ip_geo')->replace($row);
                $out[$ip] = (object) $row;
            }
        }
        return $out;
    }

    private function ipapiBatch(array $ips): array
    {
        $res = [];
        if (! $ips || ! function_exists('curl_init')) { return $res; }
        $payload = array_map(fn ($ip) => ['query' => $ip], $ips);
        $ch = curl_init('http://ip-api.com/batch?fields=status,message,query,country,regionName,city,lat,lon,isp');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 15, CURLOPT_CONNECTTIMEOUT => 6,
        ]);
        $body = curl_exec($ch);
        curl_close($ch);
        if ($body === false) { return $res; }
        $arr = json_decode($body, true);
        if (! is_array($arr)) { return $res; }
        foreach ($arr as $row) { if (isset($row['query'])) { $res[$row['query']] = $row; } }
        return $res;
    }

    /* ==================== MAC resolution (LAN clients only) ==================== */
    private function ensureIpMacTable(): void
    {
        static $done = false;
        if ($done) { return; }
        $done = true;
        $this->db()->query("CREATE TABLE IF NOT EXISTS `aa_ip_mac` (
            `ip` VARCHAR(45) NOT NULL, `mac` VARCHAR(17) NULL, `vendor` VARCHAR(120) NULL,
            `source` VARCHAR(16) DEFAULT 'remote', `first_seen` TIMESTAMP NULL DEFAULT NULL,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`ip`), KEY `idx_mac` (`mac`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    /** Parse the host's ARP (IPv4) + NDP (IPv6) neighbour tables → ip => MAC. */
    private function arpTable(): array
    {
        static $cache = null;
        if ($cache !== null) { return $cache; }
        $cache = [];
        if (! function_exists('shell_exec')) { return $cache; }

        if (stripos(PHP_OS, 'WIN') === 0) {
            $out  = (string) @shell_exec('arp -a 2>&1');
            $out .= "\n" . (string) @shell_exec('netsh interface ipv6 show neighbors 2>&1');
        } else {
            $out = (string) @shell_exec('ip neigh 2>&1');
        }

        foreach (preg_split('/\r?\n/', $out) as $line) {
            if (! preg_match('/([0-9a-f]{2}[:-]){5}[0-9a-f]{2}/i', $line, $mm)) { continue; }
            $mac = strtoupper(str_replace('-', ':', $mm[0]));
            if ($mac === 'FF:FF:FF:FF:FF:FF' || strpos($mac, '01:00:5E') === 0 || strpos($mac, '33:33:') === 0 || $mac === '00:00:00:00:00:00') { continue; }
            if (preg_match('/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/', $line, $ip4)) {
                $cache[$ip4[1]] = $mac;
            } elseif (preg_match('/([0-9a-f]{0,4}:){2,7}[0-9a-f]{0,4}/i', $line, $ip6)) {
                $cache[strtolower($ip6[0])] = $mac;
            }
        }
        return $cache;
    }

    /** Best-effort OUI → vendor lookup (macvendors.com); '' on failure. */
    private function macVendor(string $mac): string
    {
        if ($mac === '' || ! function_exists('curl_init')) { return ''; }
        $ch = curl_init('https://api.macvendors.com/' . rawurlencode($mac));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 6, CURLOPT_CONNECTTIMEOUT => 4]);
        $v = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($v !== false && $code == 200 && stripos($v, 'errors') === false) { return substr(trim($v), 0, 120); }
        return '';
    }

    /**
     * Resolve MACs for a list of IPs. Only LAN (private/link-local) clients that
     * share the server's segment can have a MAC (matched from the ARP/NDP table);
     * remote/internet IPs are recorded 'remote' (unobtainable). Cached in
     * aa_ip_mac. CI3 Monitor_mod::resolve_macs(). @return array<string,object>
     */
    public function resolve_macs(array $ips): array
    {
        $out = [];
        $ips = array_values(array_unique(array_filter(array_map(fn ($x) => trim((string) $x), $ips))));
        if (! $ips) { return $out; }
        $this->ensureIpMacTable();
        $db = $this->db();

        foreach ($db->table('aa_ip_mac')->whereIn('ip', $ips)->get()->getResult() as $r) {
            if ($r->source === 'unknown') { continue; } // allow LAN-unknown to be retried
            $out[$r->ip] = $r;
        }

        $arp = null;
        foreach ($ips as $ip) {
            if (isset($out[$ip])) { continue; }
            if ($this->ipVersion($ip) === 0) { continue; }

            if ($ip === '127.0.0.1' || $ip === '::1') {
                $row = ['ip' => $ip, 'mac' => null, 'vendor' => null, 'source' => 'loopback', 'updated_at' => date('Y-m-d H:i:s')];
                $db->table('aa_ip_mac')->replace($row); $out[$ip] = (object) $row; continue;
            }
            if ($this->ipPublic($ip)) {
                $row = ['ip' => $ip, 'mac' => null, 'vendor' => null, 'source' => 'remote', 'updated_at' => date('Y-m-d H:i:s')];
                $db->table('aa_ip_mac')->replace($row); $out[$ip] = (object) $row; continue;
            }

            if ($arp === null) { $arp = $this->arpTable(); }
            $mac = $arp[$ip] ?? ($arp[strtolower($ip)] ?? null);
            if ($mac) {
                $row = ['ip' => $ip, 'mac' => $mac, 'vendor' => $this->macVendor($mac), 'source' => 'arp', 'first_seen' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s')];
            } else {
                $row = ['ip' => $ip, 'mac' => null, 'vendor' => null, 'source' => 'unknown', 'updated_at' => date('Y-m-d H:i:s')];
            }
            $db->table('aa_ip_mac')->replace($row); $out[$ip] = (object) $row;
        }
        return $out;
    }

    /** Exact device-GPS points from the entry-audit log for the map (firm-scoped). */
    public function geo_points(array $f): array
    {
        if (! $this->hasEntryGeo()) { return []; }
        $db = $this->db();
        $hasAcc = $db->fieldExists('accuracy', 'aa_entry_trace');
        $sel = "t.latitude, t.longitude, " . ($hasAcc ? 't.accuracy' : 'NULL accuracy') . ", t.module, t.entry_id, t.action, t.ip_address, t.created_at,
            COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''), t.user_name,'Unknown') user_name";
        $b = $db->table('aa_entry_trace t')->select($sel, false)->join('users u', 'u.id = t.user_id', 'left')
            ->where('t.latitude IS NOT NULL', null, false)->where('t.longitude IS NOT NULL', null, false)
            ->where('DATE(t.created_at) >=', $f['from'])->where('DATE(t.created_at) <=', $f['to']);
        if (($tid = $this->entryFirmId())) { $b->where('t.template_id', $tid); }
        if (! empty($f['user'])) { $b->where('t.user_id', $f['user']); }
        return $b->orderBy('t.created_at', 'desc')->limit(500)->get()->getResult();
    }

    /* ==================== Anomalies tab ==================== */
    private function userName($id): string
    {
        if (! $id) { return 'Guest / Unknown'; }
        $r = $this->db()->table('users')->select("COALESCE(NULLIF(TRIM(CONCAT(first_name,' ',last_name)),''),'User #" . (int) $id . "') nm", false)
            ->where('id', (int) $id)->get()->getRow();
        return $r ? $r->nm : ('User #' . (int) $id);
    }

    public function anomalies(array $f, bool $include_entries = true): array
    {
        $flags = [];
        foreach ($this->ip_intel($f, $include_entries) as $ip) {
            if ($ip->user_count >= 2) {
                $flags[] = (object) ['sev' => $ip->user_count >= 3 ? 'high' : 'med', 'type' => 'Shared IP',
                    'title' => $ip->ip . ' used by ' . $ip->user_count . ' users',
                    'detail' => $ip->hits . ' hits · last ' . date('d M h:i A', strtotime($ip->last)), 'ip' => $ip->ip];
            }
        }
        if ($include_entries && $this->hasEntryGeo()) {
            $b = $this->db()->table('aa_entry_trace t')->join('users u', 'u.id = t.user_id', 'left')
                ->select("t.module, t.entry_id, t.action, t.ip_address, t.created_at,
                    COALESCE(NULLIF(TRIM(CONCAT(u.first_name,' ',u.last_name)),''), t.user_name,'Unknown') user_name", false)
                ->where('DATE(t.created_at) >=', $f['from'])->where('DATE(t.created_at) <=', $f['to'])->where('HOUR(t.created_at) <', 5);
            if (($tid = $this->entryFirmId())) { $b->where('t.template_id', $tid); }
            if (! empty($f['user'])) { $b->where('t.user_id', $f['user']); }
            foreach ($b->orderBy('t.created_at', 'desc')->limit(40)->get()->getResult() as $r) {
                $flags[] = (object) ['sev' => 'med', 'type' => 'Odd-hour entry',
                    'title' => $r->user_name . ' — ' . $r->module . ' #' . $r->entry_id . ' (' . $r->action . ')',
                    'detail' => date('d M Y h:i A', strtotime($r->created_at)) . ' · ' . ($r->ip_address ?: 'no IP'), 'ip' => $r->ip_address];
            }
        }
        $userIps = [];
        foreach ($this->dateScope($this->db()->table('daily_traffic'), 'trafiic_date', $f)
            ->select('user_id, ip_address', false)->where('user_id IS NOT NULL', null, false)->where('ip_address IS NOT NULL', null, false)
            ->get()->getResult() as $r) { $userIps[(int) $r->user_id][$r->ip_address] = 1; }
        foreach ($userIps as $u => $ips) {
            if (count($ips) >= 4) {
                $flags[] = (object) ['sev' => count($ips) >= 6 ? 'high' : 'med', 'type' => 'Multiple IPs',
                    'title' => $this->userName($u) . ' seen from ' . count($ips) . ' different IPs',
                    'detail' => 'Possible shared account or roaming', 'ip' => ''];
            }
        }
        return $flags;
    }

    /* ==================== Activity Scores tab ==================== */
    private function scoreWeights(): array
    {
        return ['create' => 5, 'update' => 2, 'delete' => 1, 'login' => 2, 'view' => 0.2];
    }

    private function scoreWindow(string $a, string $b, int $onlyUser, array $W): array
    {
        $db = $this->db();
        $out = [];
        $lo = $a . ' 00:00:00'; $hi = $b . ' 23:59:59';
        $ensure = function ($uid) use (&$out) {
            if (! isset($out[$uid])) { $out[$uid] = ['create' => 0, 'update' => 0, 'delete' => 0, 'login' => 0, 'view' => 0, 'days' => 0, 'score' => 0.0]; }
        };
        if ($db->tableExists('aa_entry_trace')) {
            $q = $db->table('aa_entry_trace')->select('user_id, LOWER(action) act, COUNT(*) c', false)
                ->where('created_at >=', $lo)->where('created_at <=', $hi)->groupBy('user_id, action');
            if ($onlyUser) { $q->where('user_id', $onlyUser); }
            foreach ($q->get()->getResult() as $r) {
                $uid = (int) $r->user_id; if (! $uid) { continue; } $ensure($uid);
                $act = in_array($r->act, ['create', 'update', 'delete'], true) ? $r->act : 'update';
                $out[$uid][$act] += (int) $r->c;
            }
        }
        if ($db->tableExists('aa_login_detail')) {
            $q = $db->table('aa_login_detail')->select('user_id, COUNT(*) c', false)->where('added_date >=', $lo)->where('added_date <=', $hi)->groupBy('user_id');
            if ($onlyUser) { $q->where('user_id', $onlyUser); }
            foreach ($q->get()->getResult() as $r) { $uid = (int) $r->user_id; if (! $uid) { continue; } $ensure($uid); $out[$uid]['login'] += (int) $r->c; }
        }
        if ($db->tableExists('daily_traffic')) {
            $q = $db->table('daily_traffic')->select('user_id, COUNT(*) c', false)->where('trafiic_date >=', $lo)->where('trafiic_date <=', $hi)->groupBy('user_id');
            if ($onlyUser) { $q->where('user_id', $onlyUser); }
            foreach ($q->get()->getResult() as $r) { $uid = (int) $r->user_id; if (! $uid) { continue; } $ensure($uid); $out[$uid]['view'] += (int) $r->c; }
        }
        $unions = [];
        $eLo = $db->escape($lo); $eHi = $db->escape($hi);
        if ($db->tableExists('aa_entry_trace')) { $unions[] = "SELECT user_id, DATE(created_at) d FROM aa_entry_trace WHERE created_at BETWEEN $eLo AND $eHi"; }
        if ($db->tableExists('daily_traffic')) { $unions[] = "SELECT user_id, DATE(trafiic_date) d FROM daily_traffic WHERE trafiic_date BETWEEN $eLo AND $eHi"; }
        if ($db->tableExists('aa_login_detail')) { $unions[] = "SELECT user_id, DATE(added_date) d FROM aa_login_detail WHERE added_date BETWEEN $eLo AND $eHi"; }
        if ($unions) {
            $sql = 'SELECT user_id, COUNT(DISTINCT d) days FROM (' . implode(' UNION ALL ', $unions) . ') t WHERE user_id IS NOT NULL';
            if ($onlyUser) { $sql .= ' AND user_id = ' . $onlyUser; }
            $sql .= ' GROUP BY user_id';
            foreach ($db->query($sql)->getResult() as $r) { $uid = (int) $r->user_id; if (! $uid || ! isset($out[$uid])) { continue; } $out[$uid]['days'] = (int) $r->days; }
        }
        foreach ($out as $uid => $x) {
            $out[$uid]['score'] = $x['create'] * $W['create'] + $x['update'] * $W['update'] + $x['delete'] * $W['delete'] + $x['login'] * $W['login'] + $x['view'] * $W['view'];
        }
        return $out;
    }

    public function user_scores(array $f): array
    {
        $W = $this->scoreWeights();
        $from = $f['from']; $to = $f['to'];
        $days = max(1, (int) floor((strtotime($to) - strtotime($from)) / 86400) + 1);
        $prevTo = date('Y-m-d', strtotime($from . ' -1 day'));
        $prevFrom = date('Y-m-d', strtotime($from . ' -' . $days . ' day'));
        $only = ! empty($f['user']) ? (int) $f['user'] : 0;
        $cur = $this->scoreWindow($from, $to, $only, $W);
        $prev = $this->scoreWindow($prevFrom, $prevTo, $only, $W);
        $rows = [];
        foreach ($cur as $uid => $b) {
            $prevScore = isset($prev[$uid]) ? (float) $prev[$uid]['score'] : 0.0;
            $delta = ($prevScore > 0) ? round((($b['score'] - $prevScore) / $prevScore) * 100) : null;
            $rows[] = (object) ['user_id' => $uid, 'user_name' => $this->userName($uid),
                'create' => (int) $b['create'], 'update' => (int) $b['update'], 'delete' => (int) $b['delete'],
                'login' => (int) $b['login'], 'view' => (int) $b['view'], 'days' => (int) $b['days'],
                'score' => round($b['score'], 1), 'avg_day' => $b['days'] > 0 ? round($b['score'] / $b['days'], 1) : round($b['score'], 1),
                'prev_score' => round($prevScore, 1), 'delta' => $delta];
        }
        usort($rows, fn ($x, $y) => ($y->score <=> $x->score));
        $rank = 0;
        foreach ($rows as $r) { $r->rank = ++$rank; }
        return ['rows' => $rows, 'weights' => $W, 'window' => ['from' => $from, 'to' => $to, 'days' => $days], 'prev_window' => ['from' => $prevFrom, 'to' => $prevTo]];
    }

    /* ==================== Entry Audit tab (aa_entry_trace, firm-scoped) ==================== */
    public function entryModules(): array
    {
        $b = $this->db()->table('aa_entry_trace')->distinct()->select('module');
        if (($tid = $this->entryFirmId())) { $b->where('template_id', $tid); }
        return $b->orderBy('module', 'asc')->get()->getResult();
    }

    private function entryFilterScope($b, array $p)
    {
        if (($tid = $this->entryFirmId())) { $b->where('t.template_id', $tid); }
        foreach (['f_module' => 't.module', 'f_source' => 't.entry_source', 'f_action' => 't.action'] as $k => $col) {
            $v = $p[$k] ?? '';
            if ($v !== '' && $v !== 'all') { $b->where($col, $v); }
        }
        if (! empty($p['f_user']) && $p['f_user'] !== 'all') { $b->where('t.user_id', (int) $p['f_user']); }
        if (! empty($p['f_ip'])) { $b->like('t.ip_address', trim((string) $p['f_ip'])); }
        if (! empty($p['f_from'])) { $b->where('DATE(t.created_at) >=', $p['f_from']); }
        if (! empty($p['f_to'])) { $b->where('DATE(t.created_at) <=', $p['f_to']); }
        if (($p['f_geo'] ?? '') === '1') { $b->where('t.latitude IS NOT NULL', null, false); }
        $sv = trim((string) ($p['search']['value'] ?? ''));
        if ($sv !== '') { $b->groupStart()->like('t.ip_address', $sv)->orLike('t.module', $sv)->orLike('t.user_name', $sv)->orLike('t.entry_id', $sv)->groupEnd(); }
        return $b;
    }

    public function entryData(array $p): array
    {
        $db = $this->db();
        $tid = $this->entryFirmId();
        $totalB = $db->table('aa_entry_trace');
        if ($tid) { $totalB->where('template_id', $tid); }
        $total = (int) $totalB->countAllResults();
        $filtered = (int) $this->entryFilterScope($db->table('aa_entry_trace t'), $p)->countAllResults();
        $rb = $this->entryFilterScope($db->table('aa_entry_trace t'), $p)
            ->select('t.*, TRIM(CONCAT(COALESCE(u.first_name,""),\' \',COALESCE(u.last_name,""))) full_name', false)
            ->join('users u', 'u.id = t.user_id', 'left')->orderBy('t.id', 'DESC');
        $len = (int) ($p['length'] ?? 25); if ($len <= 0) { $len = 25; }
        $rb->limit($len, (int) ($p['start'] ?? 0));
        $stats = $this->entryFilterScope($db->table('aa_entry_trace t'), $p)
            ->select("COUNT(*) total, SUM(t.entry_source='App') app_cnt, SUM(t.latitude IS NOT NULL) geo_cnt,
                COUNT(DISTINCT t.ip_address) ip_cnt, COUNT(DISTINCT t.user_id) user_cnt", false)->get()->getRow();
        return ['total' => $total, 'filtered' => $filtered, 'rows' => $rb->get()->getResult(), 'stats' => [
            'total' => $stats ? (int) $stats->total : 0, 'app_cnt' => $stats ? (int) $stats->app_cnt : 0,
            'geo_cnt' => $stats ? (int) $stats->geo_cnt : 0, 'ip_cnt' => $stats ? (int) $stats->ip_cnt : 0,
            'user_cnt' => $stats ? (int) $stats->user_cnt : 0]];
    }
}
