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
}
