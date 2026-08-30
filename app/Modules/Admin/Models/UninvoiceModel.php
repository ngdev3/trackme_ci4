<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * UninvoiceModel — CI4 port of admin/models/Uninvoice_mod listing queries
 * (count_Billing_data + get_Billing_data) for the Unregistered Bill of Supply
 * (UBOS) register. Scoped exactly like CI3: by template_id + soft-delete-aware
 * (COALESCE(status,'') != 'Delete'). NOTE: the CI3 UBOS listing does NOT filter
 * by FY or product_type — it scopes by template_id (firm) only — so this port
 * mirrors that to keep the row set identical. DataTables params come from the
 * POST body; date/hsn/status filters from GET (unchanged contract).
 */
class UninvoiceModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Total (unfiltered-by-search) count for the current scope. */
    public function countData(): int
    {
        $req = service('request');
        $builder = $this->db()->table('uninvoice_system ab')
            ->select('ab.bos_id')
            ->join('aa_account_name acn', 'acn.account_id = ab.account_id', 'left')
            ->where('ab.template_id', fy()->template_id)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);

        if ($req->getGet('status') !== null) {
            $builder->where('ab.status', $req->getGet('status'));
        }
        $this->applyDateFilter($builder, $req);
        if (! empty($req->getGet('hsn_code')) && $req->getGet('hsn_code') !== 'none') {
            $builder->where('ab.hsn_code', $req->getGet('hsn_code'));
        }
        return $builder->countAllResults();
    }

    /** One page of rows for the DataTable, honouring search/order/paging. */
    public function getData(): array
    {
        $req  = service('request');
        $post = $req->getPost();

        $columns = [1 => 'invoice_id', 2 => 'account_id'];

        $builder = $this->db()->table('uninvoice_system ab')
            ->select('ab.*, acn.name as account_name, hsc.id as hsn_code_id')
            ->join('aa_account_name acn', 'acn.account_id = ab.account_id', 'left')
            ->join('hsn_codes hsc', "hsc.hsn_code = ab.hsn_code AND COALESCE(hsc.status,'') != 'Delete'", 'left')
            ->where('ab.template_id', fy()->template_id)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);

        if ($req->getGet('type_of_invoice') !== null) {
            $builder->where('ab.type_of_invoice', $req->getGet('type_of_invoice'));
        }

        $this->applyDateFilter($builder, $req);

        if (! empty($post['search']['value'])) {
            $builder->like("(CONCAT(acn.name,' ',ab.product_name,' ',ab.hsn_code,' ',ab.quantity,' ',ab.rate,' ',ab.amount))", $post['search']['value']);
        }

        if (! empty($req->getGet('hsn_code')) && $req->getGet('hsn_code') !== 'none') {
            $builder->where('ab.hsn_code', $req->getGet('hsn_code'));
        }

        if (! empty($post['order'][0]['column']) && ! empty($post['order'][0]['dir'])) {
            $col = $columns[$post['order'][0]['column']] ?? 'invoice_id';
            $builder->orderBy($col, $post['order'][0]['dir']);
        } else {
            $builder->orderBy('invoice_id', 'desc');
        }

        if (! empty($post['length']) && $post['length'] != '-1') {
            $builder->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }

        return $builder->get()->getResult();
    }

    private function applyDateFilter($builder, $req): void
    {
        $from = $req->getGet('from_billing_date');
        $to   = $req->getGet('to_billing_date');
        if ($from && $to) {
            $builder->where('ab.billing_date >=', date('Y-m-d', strtotime($from)));
            $builder->where('ab.billing_date <=', date('Y-m-d', strtotime($to)));
        }
    }
}
