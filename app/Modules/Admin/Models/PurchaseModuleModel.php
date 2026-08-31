<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * PurchaseModuleModel — CI4 port of the purchase_bills listing. Scope:
 * template_id + status (no FY column; purchase uses invoice_date). Joins
 * aa_account_name (party) and hsn_codes (product/HSN names, since the row
 * stores hsn_code_id). Listing only.
 */
class PurchaseModuleModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function base()
    {
        return $this->db()->table('purchase_bills pb')
            ->join('aa_account_name acn', 'acn.account_id = pb.account_id', 'left')
            ->join('hsn_codes h', 'h.id = pb.hsn_code_id', 'left')
            ->where('pb.template_id', fy()->template_id)
            ->where("COALESCE(pb.status,'') != 'Delete'", null, false);
    }

    public function countData(): int
    {
        return $this->base()->select('pb.id')->countAllResults();
    }

    public function getData(): array
    {
        $post = service('request')->getPost();
        $b = $this->base()
            ->select('pb.*, acn.name as account_name, h.product_name, h.hsn_code')
            ->orderBy('pb.id', 'desc');
        if (! empty($post['search']['value'])) {
            $b->like("(CONCAT(pb.invoice_no,' ',pb.vehicle_no,' ',h.product_name,' ',acn.name))", $post['search']['value']);
        }
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }
}
