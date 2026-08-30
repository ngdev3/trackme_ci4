<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * NotificationModel — CI4 port of admin/models/Notification_mod (the real
 * notification-centre methods; the tax-invoice/rokad boilerplate is dead).
 * `notification` rows are scoped by user_id + FY + product_type; a row with
 * msg_global=1 is a broadcast visible to everyone. is_seen 0=unread/1=read.
 */
class NotificationModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Total / unread / read counts for the current user + scope. */
    public function getCounts()
    {
        return $this->db()->table('notification')
            ->select('COUNT(*) AS total,
                SUM(CASE WHEN is_seen = 0 THEN 1 ELSE 0 END) AS unread,
                SUM(CASE WHEN is_seen = 1 THEN 1 ELSE 0 END) AS `read`', false)
            ->where('user_id', (int) currentuserinfo()->id)
            ->where('FY', fy()->FY)
            ->where('product_type', fy()->product_type)
            ->get()->getRow();
    }

    /** Mark all of the current user's unread notifications as read. */
    public function markAllRead(): int
    {
        $this->db()->table('notification')
            ->where('user_id', (int) currentuserinfo()->id)
            ->where('is_seen', 0)
            ->update(['is_seen' => 1, 'updated_date' => date('Y-m-d H:i:s')]);
        return $this->db()->affectedRows();
    }

    public function getOne(int $id)
    {
        return $this->db()->table('notification')->where('id', $id)->get()->getRow();
    }

    public function markRead(int $id): int
    {
        $this->db()->table('notification')->where('id', $id)
            ->update(['is_seen' => 1, 'updated_date' => date('Y-m-d H:i:s')]);
        return $this->db()->affectedRows();
    }

    /** Toggle one row's seen state (per-row action). $flag 'checked' => unread. */
    public function toggleSeen(int $id, string $flag): bool
    {
        $this->db()->table('notification')->where('id', $id)->update([
            'is_seen'      => $flag === 'checked' ? 0 : 1,
            'updated_date' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /** DataTable total for the current user + scope (search-aware). */
    public function countBillingData(): int
    {
        $req     = service('request');
        $post    = $req->getPost();
        $builder = $this->db()->table('notification')
            ->where('user_id', (int) currentuserinfo()->id)
            ->where('FY', fy()->FY)
            ->where('product_type', fy()->product_type);
        if (! empty($post['search']['value'])) {
            $builder->like('name', $post['search']['value']);
        }
        return $builder->countAllResults();
    }

    /**
     * DataTable feed: the user's own notifications + global broadcasts, joined to
     * the acting user's name. Super admins see all in-scope; others see their own
     * + globals. Honours search / order / paging.
     */
    public function getBillingData(): array
    {
        $req  = service('request');
        $post = $req->getPost();
        $uid  = (int) currentuserinfo()->id;

        $builder = $this->db()->table('notification ab')
            ->select("ab.*, CONCAT(acn.first_name, ' ', acn.last_name) as user_name")
            ->join('users acn', 'acn.id = ab.user_id', 'left')
            ->where('ab.FY', fy()->FY)
            ->where('ab.product_type', fy()->product_type);

        // own + broadcast unless super admin (who sees everything in scope)
        if (! (function_exists('erp_is_super_admin') && erp_is_super_admin())) {
            $builder->groupStart()->where('ab.msg_global', 1)->orWhere('ab.user_id', $uid)->groupEnd();
        }

        if (! empty($post['search']['value'])) {
            $builder->like('ab.name', $post['search']['value']);
        }

        $columns = [1 => 'id', 2 => 'name'];
        if (! empty($post['order'][0]['column']) && ! empty($post['order'][0]['dir'])) {
            $builder->orderBy($columns[$post['order'][0]['column']] ?? 'ab.id', $post['order'][0]['dir']);
        } else {
            $builder->orderBy('ab.id', 'desc');
        }
        if (! empty($post['length']) && $post['length'] != '-1') {
            $builder->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }

        return $builder->get()->getResult();
    }
}
