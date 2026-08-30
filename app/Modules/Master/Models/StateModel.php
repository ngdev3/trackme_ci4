<?php

namespace App\Modules\Master\Models;

use Config\Database;

/**
 * StateModel — CI4 port of master/State. Simplest lookup: am_state
 * (state_id, name, status). Soft-deleted (status='Delete'). Feeds the City
 * master's state dropdown.
 */
class StateModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function listRows(?string $search = null): array
    {
        $b = $this->db()->table('am_state')
            ->where("COALESCE(status,'') != 'Delete'", null, false)
            ->orderBy('name', 'asc');
        if ($search) {
            $b->like('name', $search);
        }
        return $b->get()->getResult();
    }

    public function countActive(): int
    {
        return $this->db()->table('am_state')->where("COALESCE(status,'') != 'Delete'", null, false)->countAllResults();
    }

    public function find(int $id)
    {
        return $this->db()->table('am_state')->where('state_id', $id)->get()->getRow();
    }

    public function isDuplicate(string $name, int $exceptId = 0): bool
    {
        $b = $this->db()->table('am_state')->where('name', $name)->where("COALESCE(status,'') != 'Delete'", null, false);
        if ($exceptId > 0) {
            $b->where('state_id !=', $exceptId);
        }
        return (bool) $b->get()->getRow();
    }

    public function saveRow(array $data, int $id = 0): int
    {
        if ($id > 0) {
            $this->db()->table('am_state')->where('state_id', $id)->update(['name' => $data['name'], 'status' => $data['status']]);
            return $id;
        }
        $this->db()->table('am_state')->insert(['name' => $data['name'], 'status' => $data['status']]);
        return (int) $this->db()->insertID();
    }

    public function softDelete(int $id): bool
    {
        $this->db()->table('am_state')->where('state_id', $id)->update(['status' => 'Delete']);
        return $this->db()->affectedRows() > 0;
    }
}
