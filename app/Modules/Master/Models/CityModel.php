<?php

namespace App\Modules\Master\Models;

use Config\Database;

/**
 * CityModel — CI4 port of master/City_mod. Simple lookup CRUD over am_city
 * (name, state_id FK → am_state, status). Soft-deleted (status='Delete').
 * Global master (no tenant scope).
 */
class CityModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Active states for the FK dropdown. */
    public function states(): array
    {
        return $this->db()->table('am_state')->where('status', 'Active')->orderBy('name', 'asc')->get()->getResult();
    }

    public function listRows(?string $search = null): array
    {
        $b = $this->db()->table('am_city c')
            ->select('c.city_id, c.name, c.status, s.name as state_name')
            ->join('am_state s', 's.state_id = c.state_id', 'left')
            ->where("COALESCE(c.status,'') != 'Delete'", null, false)
            ->orderBy('c.city_id', 'desc');
        if ($search) {
            $b->groupStart()->like('c.name', $search)->orLike('s.name', $search)->groupEnd();
        }
        return $b->get()->getResult();
    }

    public function countActive(): int
    {
        return $this->db()->table('am_city')->where("COALESCE(status,'') != 'Delete'", null, false)->countAllResults();
    }

    public function find(int $id)
    {
        return $this->db()->table('am_city')->where('city_id', $id)->get()->getRow();
    }

    public function isDuplicate(string $name, int $exceptId = 0): bool
    {
        $b = $this->db()->table('am_city')->where('name', $name)->where("COALESCE(status,'') != 'Delete'", null, false);
        if ($exceptId > 0) {
            $b->where('city_id !=', $exceptId);
        }
        return (bool) $b->get()->getRow();
    }

    public function saveRow(array $data, int $id = 0): int
    {
        if ($id > 0) {
            $this->db()->table('am_city')->where('city_id', $id)->update([
                'name' => $data['name'], 'state_id' => $data['state_id'], 'status' => $data['status'],
            ]);
            return $id;
        }
        $this->db()->table('am_city')->insert([
            'name' => $data['name'], 'state_id' => $data['state_id'], 'status' => $data['status'],
        ]);
        return (int) $this->db()->insertID();
    }

    public function softDelete(int $id): bool
    {
        $this->db()->table('am_city')->where('city_id', $id)->update(['status' => 'Delete']);
        return $this->db()->affectedRows() > 0;
    }
}
