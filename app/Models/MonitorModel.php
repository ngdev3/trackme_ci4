<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Activity & Audit Monitor aggregates — "who is doing what" across ALL users.
 *
 * Sourced from the two per-user audit tables the app already records for every
 * account: activity_logs (module actions + IP) and login_logs (logins + last
 * activity for "online now"). Every query is scoped by a date range and an
 * optional single user id (0 = everyone). This data is superadmin-only.
 */
class MonitorModel extends Model
{
    protected $table      = 'activity_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';

    /** activity_logs.action values that represent business-entry changes. */
    private const ENTRY_ACTIONS = ['Add', 'Edit', 'Delete'];

    /**
     * 6 KPI headline numbers + the entry create/update/delete breakdown.
     *
     * @return array<string,int>
     */
    public function overviewKpis(string $start, string $end, int $userId = 0): array
    {
        $act = fn () => $this->db->table('activity_logs')->where('created_at >=', $start)->where('created_at <=', $end);
        $log = fn () => $this->db->table('login_logs')->where('created_at >=', $start)->where('created_at <=', $end);
        $scope = static function ($b) use ($userId) {
            if ($userId > 0) {
                $b->where('user_id', $userId);
            }
            return $b;
        };

        $visits = $scope($act())->countAllResults();
        $create = $scope($act())->where('action', 'Add')->countAllResults();
        $update = $scope($act())->where('action', 'Edit')->countAllResults();
        $delete = $scope($act())->where('action', 'Delete')->countAllResults();
        $logins = $scope($log())->where('status', 'success')->countAllResults();

        // Online now: distinct users active in the last 15 minutes, not logged out.
        $online = (int) $this->db->table('login_logs')
            ->where('last_activity_at >=', date('Y-m-d H:i:s', time() - 15 * 60))
            ->where('logout_at', null)
            ->where('user_id !=', null)
            ->select('COUNT(DISTINCT user_id) AS c')
            ->get()->getRowArray()['c'];

        // Distinct users + IPs across BOTH tables in range.
        $userFilterA = $userId > 0 ? ' AND user_id = ' . $userId : '';
        $rangeA = $this->db->escape($start) . ' AND ' . $this->db->escape($end);
        $usersRow = $this->db->query(
            "SELECT COUNT(DISTINCT user_id) AS c FROM (
                SELECT user_id FROM activity_logs WHERE created_at BETWEEN {$rangeA}{$userFilterA}
                UNION
                SELECT user_id FROM login_logs WHERE created_at BETWEEN {$rangeA}{$userFilterA}
             ) t WHERE user_id IS NOT NULL"
        )->getRowArray();
        $ipsRow = $this->db->query(
            "SELECT COUNT(DISTINCT ip_address) AS c FROM (
                SELECT ip_address FROM activity_logs WHERE created_at BETWEEN {$rangeA}{$userFilterA}
                UNION
                SELECT ip_address FROM login_logs WHERE created_at BETWEEN {$rangeA}{$userFilterA}
             ) t WHERE ip_address IS NOT NULL AND ip_address <> ''"
        )->getRowArray();

        return [
            'visits'  => $visits,
            'logins'  => $logins,
            'entries' => $create + $update + $delete,
            'create'  => $create,
            'update'  => $update,
            'delete'  => $delete,
            'geo'     => 0,
            'online'  => $online,
            'users'   => (int) ($usersRow['c'] ?? 0),
            'ips'     => (int) ($ipsRow['c'] ?? 0),
        ];
    }

    /**
     * Daily series (capped to the last 92 days of the range): visits + entries
     * per calendar day, gap-filled so labels/visits/entries are equal length.
     *
     * @return array{labels:list<string>,visits:list<int>,entries:list<int>}
     */
    public function activitySeries(string $start, string $end, int $userId = 0): array
    {
        // Cap the axis to the last 92 days.
        $startTs = strtotime($start);
        $endTs   = strtotime($end);
        if (($endTs - $startTs) > 92 * 86400) {
            $startTs = $endTs - 92 * 86400;
        }
        $dayStart = date('Y-m-d 00:00:00', $startTs);
        $dayEnd   = date('Y-m-d 23:59:59', $endTs);

        $rows = $this->db->table('activity_logs')
            ->select("DATE(created_at) AS d,
                      COUNT(*) AS visits,
                      SUM(CASE WHEN action IN ('Add','Edit','Delete') THEN 1 ELSE 0 END) AS entries")
            ->where('created_at >=', $dayStart)
            ->where('created_at <=', $dayEnd);
        if ($userId > 0) {
            $rows->where('user_id', $userId);
        }
        $byDay = [];
        foreach ($rows->groupBy('DATE(created_at)')->get()->getResultArray() as $r) {
            $byDay[$r['d']] = ['visits' => (int) $r['visits'], 'entries' => (int) $r['entries']];
        }

        $labels = $visits = $entries = [];
        for ($ts = $startTs; $ts <= $endTs; $ts += 86400) {
            $key = date('Y-m-d', $ts);
            $labels[]  = date('d M', $ts);
            $visits[]  = $byDay[$key]['visits'] ?? 0;
            $entries[] = $byDay[$key]['entries'] ?? 0;
        }

        return ['labels' => $labels, 'visits' => $visits, 'entries' => $entries];
    }

    /**
     * 24 integers (index = hour 0–23) of total activity within the range.
     *
     * @return list<int>
     */
    public function hourlySeries(string $start, string $end, int $userId = 0): array
    {
        $rows = $this->db->table('activity_logs')
            ->select('HOUR(created_at) AS h, COUNT(*) AS c')
            ->where('created_at >=', $start)
            ->where('created_at <=', $end);
        if ($userId > 0) {
            $rows->where('user_id', $userId);
        }
        $hours = array_fill(0, 24, 0);
        foreach ($rows->groupBy('HOUR(created_at)')->get()->getResultArray() as $r) {
            $hours[(int) $r['h']] = (int) $r['c'];
        }
        return $hours;
    }

    /**
     * Users active in the last 15 minutes (one row per user, latest activity).
     *
     * @return list<array<string,mixed>>
     */
    public function onlineNow(): array
    {
        $since = date('Y-m-d H:i:s', time() - 15 * 60);
        $rows = $this->db->table('login_logs ll')
            ->select('ll.user_id, ll.ip_address, ll.last_activity_at, u.name AS user_name')
            ->join('users u', 'u.id = ll.user_id', 'left')
            ->where('ll.last_activity_at >=', $since)
            ->where('ll.logout_at', null)
            ->where('ll.user_id !=', null)
            ->orderBy('ll.last_activity_at', 'DESC')
            ->get()->getResultArray();

        $seen = [];
        $out  = [];
        foreach ($rows as $r) {
            $uid = (int) $r['user_id'];
            if (isset($seen[$uid])) {
                continue;
            }
            $seen[$uid] = true;
            // Last action = the user's most recent recorded activity.
            $last = $this->db->table('activity_logs')
                ->select('module, action, description')
                ->where('user_id', $uid)
                ->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
            $action = $last
                ? trim(($last['module'] ?? '') . ' · ' . ($last['action'] ?? ''), ' ·')
                : 'Signed in';
            $out[] = [
                'user_id'         => $uid,
                'user_name'       => $r['user_name'] ?: 'Guest / Unknown',
                'ip'              => $r['ip_address'] ?? '',
                'last_action'     => $action,
                'last_seen'       => $r['last_activity_at'],
                'last_seen_label' => $r['last_activity_at'] ? date('h:i A', strtotime($r['last_activity_at'])) : '',
            ];
        }
        return $out;
    }

    /**
     * Reverse-chronological feed merging recent activity + login events.
     *
     * @return list<array<string,mixed>>
     */
    public function recentActivity(string $start, string $end, int $userId = 0, int $limit = 18): array
    {
        $acts = $this->db->table('activity_logs al')
            ->select('al.action, al.module, al.description, al.ip_address, al.created_at, u.name AS user_name')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->where('al.created_at >=', $start)->where('al.created_at <=', $end);
        if ($userId > 0) {
            $acts->where('al.user_id', $userId);
        }
        $acts = $acts->orderBy('al.id', 'DESC')->limit($limit)->get()->getResultArray();

        $kindMap = ['Add' => 'entry_create', 'Edit' => 'entry_update', 'Delete' => 'entry_delete'];
        $feed = [];
        foreach ($acts as $a) {
            $feed[] = [
                'kind'      => $kindMap[$a['action']] ?? 'visit',
                'user_name' => $a['user_name'] ?: 'Guest / Unknown',
                'ts'        => $a['created_at'],
                'ts_label'  => date('d M h:i A', strtotime($a['created_at'])),
                'detail'    => $a['description'] ?: trim(($a['module'] ?? '') . ' ' . ($a['action'] ?? '')),
                'ip'        => $a['ip_address'] ?? '',
            ];
        }

        $logs = $this->db->table('login_logs lg')
            ->select('lg.username, lg.ip_address, lg.login_at, lg.created_at, u.name AS user_name')
            ->join('users u', 'u.id = lg.user_id', 'left')
            ->where('lg.status', 'success')
            ->where('lg.created_at >=', $start)->where('lg.created_at <=', $end);
        if ($userId > 0) {
            $logs->where('lg.user_id', $userId);
        }
        foreach ($logs->orderBy('lg.id', 'DESC')->limit($limit)->get()->getResultArray() as $l) {
            $ts = $l['login_at'] ?: $l['created_at'];
            $feed[] = [
                'kind'      => 'login',
                'user_name' => $l['user_name'] ?: ($l['username'] ?: 'Guest / Unknown'),
                'ts'        => $ts,
                'ts_label'  => date('d M h:i A', strtotime($ts)),
                'detail'    => 'Logged in',
                'ip'        => $l['ip_address'] ?? '',
            ];
        }

        // Merge, newest first, cap.
        usort($feed, static fn ($a, $b) => strcmp((string) $b['ts'], (string) $a['ts']));
        return array_slice($feed, 0, $limit);
    }
}
