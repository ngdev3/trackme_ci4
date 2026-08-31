<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * TrafficModel — CI4 port of admin/models/Traffic_mod (page-traffic viewer).
 * daily_traffic joined to users, date-range + search + paging. (Retention/prune
 * lives with the Monitor module and is not part of this read slice.)
 */
class TrafficModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function applyDateFilter($b, array $filters): void
    {
        if (! empty($filters['from'])) { $b->where('DATE(trafiic_date) >=', $filters['from']); }
        if (! empty($filters['to']))   { $b->where('DATE(trafiic_date) <=', $filters['to']); }
    }

    public function countVisits(array $filters, bool $withSearch = false): int
    {
        $b = $this->db()->table('daily_traffic');
        $this->applyDateFilter($b, $filters);
        if ($withSearch) {
            $s = service('request')->getPost('search');
            if (is_array($s) && ! empty($s['value'])) { $b->like('url', $s['value']); }
        }
        return $b->countAllResults();
    }

    public function visits(array $filters): array
    {
        $post = service('request')->getPost();
        $b = $this->db()->table('daily_traffic dt')
            ->select("dt.id, dt.url, dt.counter, dt.trafiic_date, dt.action_type, dt.ip_address,
                COALESCE(NULLIF(CONCAT(u.first_name,' ',u.last_name),' '),'Guest / Unknown') as user_name", false)
            ->join('users u', 'u.id = dt.user_id', 'left');
        $this->applyDateFilter($b, $filters);
        if (! empty($post['search']['value'])) { $b->like('dt.url', $post['search']['value']); }
        $b->orderBy('dt.trafiic_date', 'desc');
        if (isset($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }
}
