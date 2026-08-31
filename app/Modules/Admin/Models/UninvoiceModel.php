<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * UninvoiceModel — CI4 port of the uninvoice_system (Unregistered Bill of
 * Supply) listing. Same scope + shape as InvoiceModel.
 */
class UninvoiceModel
{
    protected function db()
    {
        return Database::connect();
    }

    public function countData(): int
    {
        $req = service('request');
        $b = $this->db()->table('uninvoice_system ab')
            ->select('ab.id')
            ->join('aa_account_name acn', 'acn.account_id = ab.account_id', 'left')
            ->where('ab.FY', fy()->FY)
            ->where('ab.product_type', fy()->product_type)
            ->where('ab.template_id', fy()->template_id)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);
        $this->applyDateFilter($b, $req);
        return $b->countAllResults();
    }

    public function getData(): array
    {
        $req  = service('request');
        $post = $req->getPost();

        $b = $this->db()->table('uninvoice_system ab')
            ->select('ab.*, acn.name as account_name')
            ->join('aa_account_name acn', 'acn.account_id = ab.account_id', 'left')
            ->where('ab.FY', fy()->FY)
            ->where('ab.product_type', fy()->product_type)
            ->where('ab.template_id', fy()->template_id)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);

        $this->applyDateFilter($b, $req);

        if (! empty($post['search']['value'])) {
            $b->like("(CONCAT(ab.product_name,' ',ab.hsn_code,' ',ab.quantity,' ',ab.rate,' ',ab.amount,' ',acn.name))", $post['search']['value']);
        }
        $b->orderBy('invoice_id', 'desc');
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    private function applyDateFilter($b, $req): void
    {
        $from = $req->getGet('from_billing_date');
        $to   = $req->getGet('to_billing_date');
        if ($from && $to) {
            $b->where('ab.billing_date >=', date('Y-m-d', strtotime($from)));
            $b->where('ab.billing_date <=', date('Y-m-d', strtotime($to)));
        }
    }
}
