<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/** DeliveryChallanModel — CI4 port of the deliverychallan listing (default DB). */
class DeliveryChallanModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function base()
    {
        return $this->db()->table('deliverychallan dc')
            ->join('aa_account_name acn', 'acn.account_id = dc.account_id', 'left')
            ->where('dc.template_id', fy()->template_id)
            ->where('dc.FY', fy()->FY)
            ->where('dc.product_type', fy()->product_type)
            ->where("COALESCE(dc.status,'') != 'Delete'", null, false);
    }

    public function countData(): int
    {
        return $this->base()->select('dc.invoice_id')->countAllResults();
    }

    public function getData(): array
    {
        $post = service('request')->getPost();
        $b = $this->base()->select('dc.*, acn.name as account_name')->orderBy('dc.invoice_id', 'desc');
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }
}
