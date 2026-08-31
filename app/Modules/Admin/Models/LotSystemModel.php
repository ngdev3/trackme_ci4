<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/** LotSystemModel — CI4 port of the lot_detail listing (general lot system). */
class LotSystemModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function base()
    {
        return $this->db()->table('lot_detail')
            ->where('template_id', fy()->template_id)->where('FY', fy()->FY)
            ->where('product_type', fy()->product_type)
            ->where("COALESCE(status,'') != 'Delete'", null, false);
    }

    public function countData(): int
    {
        return $this->base()->select('lot_id')->countAllResults();
    }

    public function getData(): array
    {
        $post = service('request')->getPost();
        $b = $this->base()->orderBy('lot_id', 'desc');
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    /** Active centers for the Add form (aa_center_name). CI3: AccountMapping_mod::center_list(). */
    public function centerList(): array
    {
        return $this->db()->table('aa_center_name')
            ->where('status', 'Active')
            ->get()->getResult();
    }

    /** Active trucks (aa_truck). CI3: Lot_system_mod::get_truck_list(). */
    public function getTruckList(): array
    {
        return $this->db()->table('aa_truck')
            ->where('status', 'Active')
            ->get()->getResult();
    }

    /** Active drivers (aa_driver). CI3: Lot_system_mod::get_driver_list(). */
    public function getDriverList(): array
    {
        return $this->db()->table('aa_driver')
            ->where('status', 'Active')
            ->get()->getResult();
    }
}
