<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * PaymentReceiptModel — CI4 port of admin/models/Payment_receipt_mod listing
 * queries (count_Billing_data + get_Billing_data). "Purchase From Farmers"
 * receipts stored in the `payment_receipt` table. Scoped by template_id + FY +
 * product_type; soft-delete-aware (status != 'Delete'). DataTables params come
 * from the POST body; date/hsn filters from GET (same contract as InvoiceModel).
 * Read-only — LISTING slice only.
 */
class PaymentReceiptModel
{
    protected function db()
    {
        return Database::connect();
    }

    /**
     * Total (unfiltered-by-search) count for the current scope.
     * CI3 count_Billing_data() scoped only by template_id; kept faithful.
     */
    public function countData(): int
    {
        $req = service('request');
        $builder = $this->db()->table('payment_receipt ab')
            ->select('ab.bos_id')
            ->join('aa_account_name acn', 'acn.account_id = ab.account_id', 'left')
            ->where('ab.template_id', fy()->template_id)
            ->where("COALESCE(ab.status,'') != 'Delete'", null, false);

        $this->applyDateFilter($builder, $req);
        if ($req->getGet('hsn_code') !== null && $req->getGet('hsn_code') !== 'none') {
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

        // hsn_code_id resolved from the HSN master (payment_receipt stores only
        // the code text) so the listing can show a running stock balance per bill.
        $builder = $this->db()->table('payment_receipt ab')
            ->select('ab.*, acn.name as account_name, hsc.id as hsn_code_id')
            ->join('aa_account_name acn', 'acn.account_id = ab.account_id', 'left')
            ->join('hsn_codes hsc', "hsc.hsn_code = ab.hsn_code AND COALESCE(hsc.status,'') != 'Delete'", 'left')
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

    /**
     * RUNNING stock balance for one commodity as on a specific bill — opening +
     * net ledger movement (purchase + production - sale) up to and INCLUDING that
     * bill's own ledger row. Ported inline from Stock_mod::running_balance_for_bill
     * so the listing stays self-contained. Firm-scoped; opening cached per HSN.
     */
    public function runningBalanceForBill(int $hsnCodeId, string $invoiceDate, int $rokadNamaId): array
    {
        static $openCache = [];
        $out = ['balance' => 0, 'product' => '', 'unit' => '', 'opening' => 0, 'net' => 0];
        if ($hsnCodeId <= 0) {
            return $out;
        }
        $db  = $this->db();
        $tid = fy()->template_id;

        if (! isset($openCache[$hsnCodeId])) {
            $m = $db->table('stock_detail')
                ->select('product_name, opening_stock, stock_unit')
                ->where('template_id', $tid)
                ->where('hsn_code_id', $hsnCodeId)
                ->orderBy('id', 'desc')->limit(1)
                ->get()->getRow();
            $openCache[$hsnCodeId] = [
                'opening' => $m ? (float) $m->opening_stock : 0,
                'unit'    => $m ? $m->stock_unit : '',
                'product' => $m ? $m->product_name : '',
            ];
        }
        $out['opening'] = $openCache[$hsnCodeId]['opening'];
        $out['unit']    = $openCache[$hsnCodeId]['unit'];
        $out['product'] = $openCache[$hsnCodeId]['product'];

        // Locate THIS bill's own ledger row (via its rokad nama id) so same-date
        // bills still get distinct, progressive balances.
        $self = null;
        if ($rokadNamaId > 0) {
            $self = $db->table('stock_log_details')
                ->select('id, date, type_of_invoice')
                ->where('rokad_nama_id', $rokadNamaId)
                ->where('hsn_code_id', $hsnCodeId)
                ->where('template_id', $tid)
                ->orderBy('id', 'asc')->limit(1)
                ->get()->getRow();
        }

        $builder = $db->table('stock_log_details')
            ->select("IFNULL(SUM(CASE
                    WHEN type_of_invoice='purchase'   THEN IFNULL(purchase_stock,0)
                    WHEN type_of_invoice='production' THEN IFNULL(production_stock,0)
                    WHEN type_of_invoice='sale'       THEN -IFNULL(sales_stock,0)
                    ELSE 0 END), 0) AS net", false)
            ->where('template_id', $tid)
            ->where('hsn_code_id', $hsnCodeId)
            ->where("COALESCE(status,'') NOT IN ('Inactive','Delete')", null, false);

        if ($self) {
            $sd  = $db->escape($self->date);
            $st  = $db->escape($self->type_of_invoice);
            $sid = (int) $self->id;
            $builder->where("(
                    STR_TO_DATE(date,'%Y-%m-%d') < STR_TO_DATE($sd,'%Y-%m-%d')
                    OR ( STR_TO_DATE(date,'%Y-%m-%d') = STR_TO_DATE($sd,'%Y-%m-%d') AND type_of_invoice < $st )
                    OR ( STR_TO_DATE(date,'%Y-%m-%d') = STR_TO_DATE($sd,'%Y-%m-%d') AND type_of_invoice = $st AND id <= $sid )
                )", null, false);
        } else {
            $d = $db->escape($invoiceDate);
            $builder->where("STR_TO_DATE(date,'%Y-%m-%d') <= STR_TO_DATE($d,'%Y-%m-%d')", null, false);
        }

        $r = $builder->get()->getRow();
        $out['net']     = $r ? (float) $r->net : 0;
        $out['balance'] = round($out['opening'] + $out['net'], 2);
        return $out;
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
