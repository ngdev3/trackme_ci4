<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * InvoiceModel — CI4 port of admin/models/Invoice_mod listing queries
 * (count_Billing_data + get_Billing_data). Scoped by template_id + FY +
 * product_type and soft-delete-aware, identical to CI3. DataTables params come
 * from the POST body; date/hsn filters from GET (unchanged contract).
 */
class InvoiceModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Total (unfiltered-by-search) count for the current scope. */
    public function countBillingData(): int
    {
        $req = service('request');
        $builder = $this->db()->table('invoice_system ab')
            ->select('ab.id')
            ->join('aa_account_name acn', 'acn.account_id = ab.account_id', 'left')
            ->where('ab.FY', fy()->FY)
            ->where('ab.product_type', fy()->product_type)
            ->where('ab.template_id', fy()->template_id)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);

        $this->applyDateFilter($builder, $req);
        if ($req->getGet('hsn_code') !== null && $req->getGet('hsn_code') !== 'none') {
            $builder->where('ab.hsn_code', $req->getGet('hsn_code'));
        }
        return $builder->countAllResults();
    }

    /** One page of rows for the DataTable, honouring search/order/paging. */
    public function getBillingData(): array
    {
        $req  = service('request');
        $post = $req->getPost();

        $columns = [1 => 'invoice_id', 2 => 'account_id'];

        $builder = $this->db()->table('invoice_system ab')
            ->select('ab.*, acn.name as account_name')
            ->join('aa_account_name acn', 'acn.account_id = ab.account_id', 'left')
            ->where('ab.FY', fy()->FY)
            ->where('ab.product_type', fy()->product_type)
            ->where('ab.template_id', fy()->template_id)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);

        $this->applyDateFilter($builder, $req);

        if (! empty($post['search']['value'])) {
            $builder->like("(CONCAT(ab.driver_name,' ',ab.product_name,' ',ab.hsn_code,' ',ab.quantity,' ',ab.rate,' ',ab.amount,' ',acn.name))", $post['search']['value']);
        }

        if ($req->getGet('hsn_code') !== null && $req->getGet('hsn_code') !== 'none') {
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

    /** One invoice by bos_id, scoped, for the PDF (CI3 get_invoice_details). */
    public function getInvoiceDetails($id): ?array
    {
        $row = $this->db()->table('invoice_system iv')
            ->select('iv.updated_date as isupdated_date, iv.*, acn.*, iv.remark as invoice_remark, iv.status as status,
                iv.added_by as added_by, iv.template_id as template_id,
                act.name as del_name, act.purchaser_address as del_purchaser_address, act.purchaser_gst_no as del_purchaser_gst_no,
                act.state as del_state, act.state_code as del_state_code')
            ->join('aa_account_name acn', 'acn.account_id = iv.account_id', 'left')
            ->join('aa_account_name act', 'act.account_id = iv.delivery_at_account', 'left')
            ->where('iv.bos_id', $id)
            ->where('iv.FY', fy()->FY)
            ->where('iv.product_type', fy()->product_type)
            ->where('iv.template_id', fy()->template_id)
            ->get()->getRowArray();

        return $row ?: null;
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
