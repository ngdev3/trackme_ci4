<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * BillingRegisterModel — CI4 port of admin/models/Billing_register_mod (listing).
 * aa_billing register, scoped by template_id + FY, soft-delete-aware. Filters
 * (billing_type/type_of_account/account_id/date) from GET; search from POST.
 */
class BillingRegisterModel
{
    protected function db()
    {
        return Database::connect();
    }

    private function applyFilters($b, bool $includeSearch = true): void
    {
        $req = service('request');
        $b->where('template_id', fy()->template_id)->where('FY', fy()->FY)->where('status <>', 'Delete');

        if ($req->getGet('billing_type'))    { $b->where('billing_type', $req->getGet('billing_type')); }
        if ($req->getGet('type_of_account')) { $b->where('type_of_account', $req->getGet('type_of_account')); }
        if ($req->getGet('account_id'))      { $b->where('purchaser_account_no', (int) $req->getGet('account_id')); }
        if ($req->getGet('from_date') && $req->getGet('to_date')) {
            $b->where('billing_date >=', date('Y-m-d', strtotime($req->getGet('from_date'))));
            $b->where('billing_date <=', date('Y-m-d', strtotime($req->getGet('to_date'))));
        }
        if ($includeSearch) {
            $post = $req->getPost();
            $s = isset($post['search']['value']) ? trim($post['search']['value']) : '';
            if ($s !== '') {
                $b->groupStart()->like('purchaser_account_name', $s)->orLike('khata_entry_no', $s)
                  ->orLike('challan_no', $s)->orLike('name', $s)->orLike('final_amount', $s)->groupEnd();
            }
        }
    }

    public function countTotal(): int
    {
        $b = $this->db()->table('aa_billing');
        $this->applyFilters($b, false);
        return $b->countAllResults();
    }

    public function countAll(): int
    {
        $b = $this->db()->table('aa_billing');
        $this->applyFilters($b, true);
        return $b->countAllResults();
    }

    public function getData(): array
    {
        $post = service('request')->getPost();
        $b = $this->db()->table('aa_billing');
        $this->applyFilters($b, true);
        $b->orderBy('billing_id', 'desc');
        if (isset($post['length']) && (int) $post['length'] !== -1) {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    public function totalAmount(): float
    {
        $b = $this->db()->table('aa_billing')->select("SUM(CAST(COALESCE(NULLIF(final_amount,''),'0') AS DECIMAL(15,2))) AS t", false);
        $this->applyFilters($b, true);
        $row = $b->get()->getRow();
        return $row ? (float) $row->t : 0;
    }

    public function usedAccounts(): array
    {
        return $this->db()->table('aa_billing b')
            ->select('b.purchaser_account_no AS account_id', false)
            ->select('COALESCE(MAX(a.name), MAX(b.purchaser_account_name)) AS name', false)
            ->join('aa_account_name a', 'a.account_id = b.purchaser_account_no', 'left')
            ->where('b.template_id', fy()->template_id)->where('b.FY', fy()->FY)
            ->where('b.status <>', 'Delete')->where('b.purchaser_account_no <>', '')
            ->groupBy('b.purchaser_account_no')->orderBy('name', 'asc')
            ->get()->getResult();
    }

    public function softDelete(int $billingId): bool
    {
        $this->db()->table('aa_billing')->where('billing_id', $billingId)->where('template_id', fy()->template_id)
            ->update(['status' => 'Delete', 'updated_date' => date('Y-m-d')]);
        return true;
    }
}
