<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * DeviceModel — CI4 port of admin/models/Device_mod (registered devices).
 * aa_whitelist_device joined to users; status Active/Inactive/Delete.
 */
class DeviceModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function countDeviceData(): int
    {
        return $this->db()->table('aa_whitelist_device')->where('status <>', 'Delete')->countAllResults();
    }

    public function getDeviceData(): array
    {
        $post = service('request')->getPost();
        $b = $this->db()->table('aa_whitelist_device d')
            ->select('d.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = d.user_id', 'left')
            ->where('d.status <>', 'Delete');
        if (! empty($post['search']['value'])) {
            $b->groupStart()->like('d.device_name', $post['search']['value'])->orLike('d.platform', $post['search']['value'])->orLike('u.first_name', $post['search']['value'])->groupEnd();
        }
        $b->orderBy('d.id', 'desc');
        if (isset($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    public function view(int $id)
    {
        return $this->db()->table('aa_whitelist_device d')->select('d.*, u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = d.user_id', 'left')->where('d.id', $id)->get()->getRow();
    }

    public function updateStatus(int $id, string $status): bool
    {
        $status = in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
        $this->db()->table('aa_whitelist_device')->where('id', $id)->update(['status' => $status, 'updated_at' => date('Y-m-d')]);
        return true;
    }

    public function delete(int $id): bool
    {
        $this->db()->table('aa_whitelist_device')->where('id', $id)->update(['status' => 'Delete', 'updated_at' => date('Y-m-d')]);
        return true;
    }

    /* ----- Push notifications (port of Device_mod) ----- */

    /**
     * Active devices that carry a usable push token.
     */
    public function activePushDevices($device_id = null): array
    {
        $b = $this->db()->table('aa_whitelist_device d')
            ->select('d.id, d.device_id, d.device_name, d.device_type, d.platform,
                      d.push_token, d.push_provider,
                      u.first_name, u.last_name, u.email')
            ->join('users u', 'u.id = d.user_id', 'left')
            ->where('d.status', 'Active')
            ->where('d.push_token IS NOT NULL', null, false)
            ->where("d.push_token != ''", null, false);
        if ($device_id) {
            $b->where('d.device_id', $device_id);
        }
        $b->orderBy('u.first_name', 'asc')->orderBy('d.device_name', 'asc');
        return $b->get()->getResult();
    }

    public function recentPushLogs(int $limit = 15): array
    {
        $db = $this->db();
        if (! $db->tableExists('aa_push_log')) {
            return [];
        }
        return $db->table('aa_push_log')->orderBy('id', 'DESC')->limit($limit)->get()->getResult();
    }
}
