<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * TaxinvoiceModel — CI4 port of admin/models/Taxinvoice_mod listing queries
 * (count_Billing_data + get_Billing_data) for the MAIN Tax Invoice register
 * (is_einvoice != 1). Scoped by template_id + FY + product_type and
 * soft-delete-aware. DataTables params come from the POST body; date filter
 * from GET, mirroring the CI3 contract. Listing-only.
 */
class TaxinvoiceModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Total (unfiltered-by-search) count for the current scope. */
    public function countData(): int
    {
        $req = service('request');
        $builder = $this->db()->table('tax_invoice_system ab')
            ->select('ab.tax_invoice_id')
            ->where('ab.FY', fy()->FY)
            ->where('ab.product_type', fy()->product_type)
            ->where('ab.template_id', fy()->template_id)
            ->where('ab.is_einvoice !=', 1)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);

        $this->applyDateFilter($builder, $req);
        if ($req->getGet('status') !== null) {
            $builder->where('ab.status', $req->getGet('status'));
        }
        if ($req->getGet('type_of_invoice') !== null) {
            $builder->where('ab.type_of_invoice', $req->getGet('type_of_invoice'));
        }
        return $builder->countAllResults();
    }

    /** One page of rows for the DataTable, honouring search/order/paging. */
    public function getData(): array
    {
        $req  = service('request');
        $post = $req->getPost();

        $columns = [1 => 'ab.tax_invoice_id', 2 => 'acn.account_id'];

        $builder = $this->db()->table('tax_invoice_system ab')
            ->select('ab.*, acn.name as account_name, isp.invoice_id, isp.updated_date as isupdated_date, ab.billing_date')
            ->join('invoice_system isp', 'isp.bos_id = ab.bos_number', 'left')
            ->join('aa_account_name acn', 'acn.account_id = isp.account_id', 'left')
            ->where('ab.FY', fy()->FY)
            ->where('ab.product_type', fy()->product_type)
            ->where('ab.template_id', fy()->template_id)
            ->where('ab.is_einvoice !=', 1)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);

        $this->applyDateFilter($builder, $req);

        if ($req->getGet('type_of_invoice') !== null) {
            $builder->where('ab.type_of_invoice', $req->getGet('type_of_invoice'));
        }
        if ($req->getGet('status') !== null) {
            $builder->where('ab.status', $req->getGet('status'));
        }

        if (! empty($post['search']['value'])) {
            $builder->like("(CONCAT(acn.name,' ',acn.contact_person_number))", $post['search']['value']);
        }

        if (! empty($post['order'][0]['column']) && ! empty($post['order'][0]['dir'])) {
            $col = $columns[$post['order'][0]['column']] ?? 'ab.tax_invoice_id';
            $builder->orderBy($col, $post['order'][0]['dir']);
        } else {
            $builder->orderBy('ab.tax_invoice_id', 'desc');
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
