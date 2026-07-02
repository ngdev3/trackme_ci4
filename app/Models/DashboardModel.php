<?php

namespace App\Models;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Read-only analytics aggregator for the dashboard.
 *
 * Every method here only READS from the existing tables (users, roles,
 * user_types, modules, login_logs, activity_logs). No schema changes.
 *
 * Finance-style metrics (income/expense/debtors/parties/firm balance...) are
 * NOT part of this admin schema, so financeKpis()/financeSeries() return a
 * clearly-flagged placeholder structure. Search for "CONNECT-DATA" to see
 * exactly where to plug real queries once those tables exist. Suggested SQL
 * is provided at the bottom of README/answer.
 */
class DashboardModel
{
    protected BaseConnection $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    // ------------------------------------------------------------------
    // KPI counters (real data)
    // ------------------------------------------------------------------
    public function kpis(): array
    {
        $users = $this->db->table('users')->where('deleted_at', null);
        $total = (clone $users)->countAllResults();
        $active = $this->db->table('users')->where('deleted_at', null)->where('status', 1)->countAllResults();

        return [
            'total_users'    => $total,
            'active_users'   => $active,
            'inactive_users' => max(0, $total - $active),
            'total_roles'    => $this->db->table('roles')->where('deleted_at', null)->countAllResults(),
            'total_modules'  => $this->db->table('modules')->where('deleted_at', null)->countAllResults(),
            'total_types'    => $this->db->table('user_types')->where('deleted_at', null)->countAllResults(),
            'logins_today'   => $this->db->table('login_logs')
                ->where('status', 'success')->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
            'activity_today' => $this->db->table('activity_logs')
                ->where('DATE(created_at)', date('Y-m-d'))->countAllResults(),
        ];
    }

    // ------------------------------------------------------------------
    // Login trend — successes vs failures over N days (line chart)
    // ------------------------------------------------------------------
    public function loginTrend(int $days = 14): array
    {
        $labels = $success = $failed = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $labels[]  = date('d M', strtotime($day));
            $success[] = $this->db->table('login_logs')->where('status', 'success')->where('DATE(created_at)', $day)->countAllResults();
            $failed[]  = $this->db->table('login_logs')->where('status', 'failed')->where('DATE(created_at)', $day)->countAllResults();
        }
        return ['labels' => $labels, 'success' => $success, 'failed' => $failed];
    }

    // ------------------------------------------------------------------
    // Users grouped by user type (doughnut chart)
    // ------------------------------------------------------------------
    public function usersByType(): array
    {
        $rows = $this->db->table('users u')
            ->select('COALESCE(ut.name, "Unassigned") AS label, COUNT(u.id) AS total')
            ->join('user_types ut', 'ut.id = u.user_type_id', 'left')
            ->where('u.deleted_at', null)
            ->groupBy('u.user_type_id')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        return [
            'labels' => array_map(static fn ($r) => $r['label'], $rows),
            'data'   => array_map(static fn ($r) => (int) $r['total'], $rows),
        ];
    }

    // ------------------------------------------------------------------
    // Users grouped by role (pie chart)
    // ------------------------------------------------------------------
    public function usersByRole(): array
    {
        $rows = $this->db->table('roles r')
            ->select('r.name AS label, COUNT(ur.user_id) AS total')
            ->join('user_roles ur', 'ur.role_id = r.id', 'left')
            ->where('r.deleted_at', null)
            ->groupBy('r.id')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        return [
            'labels' => array_map(static fn ($r) => $r['label'], $rows),
            'data'   => array_map(static fn ($r) => (int) $r['total'], $rows),
        ];
    }

    // ------------------------------------------------------------------
    // Activity grouped by action type (bar chart)
    // ------------------------------------------------------------------
    public function activityByAction(int $days = 30): array
    {
        $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
        $rows  = $this->db->table('activity_logs')
            ->select('action AS label, COUNT(id) AS total')
            ->where('created_at >=', $since)
            ->groupBy('action')
            ->orderBy('total', 'DESC')
            ->get()->getResultArray();

        return [
            'labels' => array_map(static fn ($r) => $r['label'] ?: 'Other', $rows),
            'data'   => array_map(static fn ($r) => (int) $r['total'], $rows),
        ];
    }

    // ------------------------------------------------------------------
    // Monthly user growth — new users per month, last 6 months (bar chart)
    // ------------------------------------------------------------------
    public function userGrowth(int $months = 6): array
    {
        $labels = $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01 00:00:00', strtotime("-{$i} months"));
            $end   = date('Y-m-t 23:59:59', strtotime("-{$i} months"));
            $labels[] = date('M Y', strtotime($start));
            $data[]   = $this->db->table('users')
                ->where('deleted_at', null)
                ->where('created_at >=', $start)->where('created_at <=', $end)
                ->countAllResults();
        }
        return ['labels' => $labels, 'data' => $data];
    }

    // ------------------------------------------------------------------
    // Top active users by activity-log count (horizontal bar / table)
    // ------------------------------------------------------------------
    public function topActiveUsers(int $limit = 10, int $days = 30): array
    {
        $since = date('Y-m-d 00:00:00', strtotime("-{$days} days"));
        $rows  = $this->db->table('activity_logs al')
            ->select('u.name AS label, COUNT(al.id) AS total')
            ->join('users u', 'u.id = al.user_id', 'left')
            ->where('al.created_at >=', $since)
            ->where('al.user_id IS NOT NULL')
            ->groupBy('al.user_id')
            ->orderBy('total', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();

        return [
            'labels' => array_map(static fn ($r) => $r['label'] ?: 'Unknown', $rows),
            'data'   => array_map(static fn ($r) => (int) $r['total'], $rows),
        ];
    }

    // ------------------------------------------------------------------
    // FINANCE PLACEHOLDERS
    // ------------------------------------------------------------------
    // This admin build has no accounting tables. The structure below matches
    // the requested ERP finance widgets so the UI is ready. Replace the zeroed
    // values with real aggregates once transaction/account tables exist.
    // ------------------------------------------------------------------
    public function financeKpis(): array
    {
        // CONNECT-DATA: replace with SUM() queries over your transactions table, e.g.
        //   income  = SELECT SUM(amount) FROM transactions WHERE type='credit'
        //   expense = SELECT SUM(amount) FROM transactions WHERE type='debit'
        return [
            'placeholder'    => true,
            'total_income'   => 0,
            'total_expense'  => 0,
            'total_balance'  => 0,
            'debtors'        => 0,
            'creditors'      => 0,
            'received'       => 0,
            'paid'           => 0,
        ];
    }

    public function financeSeries(): array
    {
        // CONNECT-DATA: monthly income vs expense, firm-wise balance, top parties.
        $labels = [];
        for ($i = 5; $i >= 0; $i--) {
            $labels[] = date('M Y', strtotime("-{$i} months"));
        }
        return [
            'placeholder'      => true,
            'monthly_labels'   => $labels,
            'monthly_income'   => array_fill(0, 6, 0),
            'monthly_expense'  => array_fill(0, 6, 0),
            'received_vs_paid' => ['received' => 0, 'paid' => 0],
            'top_parties'      => ['labels' => [], 'data' => []],
            'firm_balance'     => ['labels' => [], 'data' => []],
        ];
    }
}
