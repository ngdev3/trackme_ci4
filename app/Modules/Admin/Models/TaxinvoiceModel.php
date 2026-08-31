<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * TaxinvoiceModel — CI4 port of the tax_invoice_system listing (primary flow).
 * Scope: template_id + FY + product_type, soft-delete-aware, joined to
 * aa_account_name. Listing only.
 */
class TaxinvoiceModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function base()
    {
        return $this->db()->table('tax_invoice_system ti')
            ->join('aa_account_name acn', 'acn.account_id = ti.account_id', 'left')
            ->where('ti.FY', fy()->FY)
            ->where('ti.product_type', fy()->product_type)
            ->where('ti.template_id', fy()->template_id)
            ->where("COALESCE(ti.status,'') != 'Delete'", null, false);
    }

    public function countData(): int
    {
        return $this->base()->select('ti.tax_invoice_id')->countAllResults();
    }

    public function getData(): array
    {
        $post = service('request')->getPost();
        $b = $this->base()->select('ti.*, acn.name as account_name')->orderBy('ti.tax_invoice_id', 'desc');
        if (! empty($post['search']['value'])) {
            $b->like("(CONCAT(ti.tax_invoice_fy_id,' ',ti.product_name,' ',ti.hsn_code,' ',acn.name))", $post['search']['value']);
        }
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }
}
