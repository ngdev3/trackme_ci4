<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * CdNoteModel — CI4 port of admin/models/Cdnote_mod listing queries
 * (count_Billing_data + get_Billing_data). Faithful to CI3: the Credit/Debit
 * Note listing is scoped by template_id ONLY (the credit_debit_note table has
 * no FY/product_type predicate in the CI3 listing and no soft-delete column).
 * Every note is resolved back to its source document (Tax Invoice / Bill of
 * Supply / UBOS) through the same LEFT JOINs the CI3 model uses. DataTables
 * params come from the POST body; the type_of_invoice filter from GET.
 */
class CdNoteModel
{
    protected function db()
    {
        return Database::connect();
    }

    /**
     * Shared LEFT JOINs that resolve every credit/debit note back to its source
     * document, whichever of the three systems it was raised against.
     * type_of_invoice: 2 = Tax Invoice, 1 = Bill of Supply, 3 = UBOS.
     * Raw (unescaped) join conditions, verbatim from CI3 source_joins().
     */
    private function sourceJoins($builder): void
    {
        $tpl = (int) fy()->template_id;
        $builder->join('tax_invoice_system tis', "tis.tax_invoice_fy_id = cdn.invoice_number AND cdn.type_of_invoice = 2 AND tis.template_id = $tpl", 'left', false);
        $builder->join('invoice_system ivs',     "ivs.invoice_id = cdn.invoice_number AND cdn.type_of_invoice = 1 AND ivs.template_id = $tpl", 'left', false);
        $builder->join('uninvoice_system uvs',   "uvs.invoice_id = cdn.invoice_number AND cdn.type_of_invoice = 3 AND uvs.template_id = $tpl", 'left', false);
        $builder->join('aa_account_name acn',    'acn.account_id = COALESCE(tis.account_id, ivs.account_id, uvs.account_id)', 'left', false);
    }

    /** Document-type filter (GET), search (POST) and template_id scope. */
    private function sourceFilters($builder): void
    {
        $req  = service('request');
        $post = $req->getPost();

        $type = $req->getGet('type_of_invoice');
        if ($type !== null && $type !== 'none' && $type !== '') {
            $builder->where('cdn.type_of_invoice', $type);
        }

        if (! empty($post['search']['value'])) {
            $searchVal = $post['search']['value'];
            $builder->groupStart()
                ->like('cdn.invoice_number', $searchVal)
                ->orLike('cdn.tax_invoice_fy_id', $searchVal)
                ->orLike('acn.name', $searchVal)
                ->orLike('COALESCE(tis.product_name, ivs.product_name, uvs.product_name)', $searchVal, 'both', false)
                ->groupEnd();
        }

        $builder->where('cdn.template_id', fy()->template_id);
    }

    /** Total (search-aware) count for the current scope — CI3 count_Billing_data. */
    public function countData(): int
    {
        $builder = $this->db()->table('credit_debit_note cdn');
        $this->sourceJoins($builder);
        $this->sourceFilters($builder);
        return $builder->countAllResults();
    }

    /** One page of rows for the DataTable — CI3 get_Billing_data. */
    public function getData(): array
    {
        $req  = service('request');
        $post = $req->getPost();

        $builder = $this->db()->table('credit_debit_note cdn')
            ->select("cdn.*,
                acn.name as account_name,
                COALESCE(tis.product_name, ivs.product_name, uvs.product_name) as product_name,
                COALESCE(tis.hsn_code, ivs.hsn_code, uvs.hsn_code) as hsn_code,
                COALESCE(tis.billing_date, ivs.billing_date, uvs.billing_date) as billing_date,
                COALESCE(tis.total_invoice, ivs.total_invoice, uvs.total_invoice) as src_total_invoice", false);
        $this->sourceJoins($builder);
        $this->sourceFilters($builder);

        $builder->orderBy('cdn.credit_debit_id', 'desc');

        if (! empty($post['length']) && $post['length'] != '-1') {
            $builder->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }

        return $builder->get()->getResult();
    }
}
