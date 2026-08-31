<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/** PaddyLotsystemModel — CI4 port of the paddy_lot_system listing. */
class PaddyLotsystemModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function base()
    {
        return $this->db()->table('paddy_lot_system')
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

    /**
     * Active centers for the Add-form dropdown.
     * 1:1 port of AccountMapping_mod::center_list() (aa_center_name, status='Active').
     */
    public function center_list()
    {
        $rows = $this->db()->table('aa_center_name')
            ->where('status', 'Active')
            ->get()->getResult();
        return $rows ?: false;
    }

    /** 1:1 port of Paddy_Lot_system_mod::get_truck_list() (aa_truck, status='Active'). */
    public function get_truck_list()
    {
        $rows = $this->db()->table('aa_truck')
            ->where('status', 'Active')
            ->get()->getResult();
        return $rows ?: false;
    }

    /** 1:1 port of Paddy_Lot_system_mod::get_driver_list() (aa_driver, status='Active'). */
    public function get_driver_list()
    {
        $rows = $this->db()->table('aa_driver')
            ->where('status', 'Active')
            ->get()->getResult();
        return $rows ?: false;
    }
}
