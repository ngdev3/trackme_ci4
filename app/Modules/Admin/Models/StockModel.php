<?php

namespace App\Modules\Admin\Models;

use Config\Database;

/**
 * StockModel — CI4 port of the read-only stock-balance core from Stock_mod.
 * `currentBalance()` is the STRICT STOCK GUARD used by invoice/sale add: a sale
 * may never exceed available stock. Balance = master opening_stock + net ledger
 * movement (purchase + production − sale), scoped by firm, excluding
 * Inactive/Delete rows. Pure read — safe.
 */
class StockModel
{
    protected function db()
    {
        return Database::connect();
    }

    /** Stock master listing (stock_detail), scoped by firm + FY. */
    public function countList(): int
    {
        return $this->db()->table('stock_detail')
            ->where('template_id', fy()->template_id)->where('FY', fy()->FY)
            ->where("COALESCE(status,'') != 'Delete'", null, false)->countAllResults();
    }

    public function getList(): array
    {
        $post = service('request')->getPost();
        $b = $this->db()->table('stock_detail')
            ->where('template_id', fy()->template_id)->where('FY', fy()->FY)
            ->where("COALESCE(status,'') != 'Delete'", null, false)
            ->orderBy('id', 'desc');
        if (! empty($post['length']) && $post['length'] != '-1') {
            $b->limit((int) $post['length'], (int) ($post['start'] ?? 0));
        }
        return $b->get()->getResult();
    }

    public function currentBalance(int $hsnCodeId): array
    {
        $out = ['balance' => 0, 'product' => '', 'unit' => '', 'opening' => 0, 'net' => 0];
        if ($hsnCodeId <= 0) {
            return $out;
        }
        $tid = fy()->template_id;

        // Master opening + product meta (latest stock_detail row for this product).
        $master = $this->db()->table('stock_detail')
            ->select('product_name, opening_stock, stock_unit')
            ->where('template_id', $tid)
            ->where('hsn_code_id', $hsnCodeId)
            ->orderBy('id', 'desc')->limit(1)
            ->get()->getRow();
        $out['opening'] = $master ? (float) $master->opening_stock : 0;
        $out['product'] = $master ? $master->product_name : '';
        $out['unit']    = $master ? $master->stock_unit : '';

        // Net movement from the stock ledger.
        $row = $this->db()->table('stock_log_details')
            ->select("
                SUM(CASE WHEN type_of_invoice='purchase'   THEN IFNULL(purchase_stock,0)   ELSE 0 END)
              + SUM(CASE WHEN type_of_invoice='production' THEN IFNULL(production_stock,0) ELSE 0 END)
              - SUM(CASE WHEN type_of_invoice='sale'       THEN IFNULL(sales_stock,0)      ELSE 0 END) AS net", false)
            ->where('template_id', $tid)
            ->where('hsn_code_id', $hsnCodeId)
            ->where("COALESCE(status,'') NOT IN ('Inactive','Delete')", null, false)
            ->get()->getRow();
        $out['net'] = $row ? (float) $row->net : 0;

        $out['balance'] = round($out['opening'] + $out['net'], 2);
        return $out;
    }

    /* =====================================================================
     * STOCK POSITION / STOCK STATEMENT — 1:1 port of Stock_mod movement engine.
     * All firm-scoped (fy()->template_id), soft-deletes excluded exactly as CI3.
     * ===================================================================== */

    /** All Active firm/FY templates (CI3 Setting_mod::get_all_financial_year). */
    public function getAllFinancialYear()
    {
        $query = $this->db()->table('aa_template as atp')
            ->select('atp.*, frn.name as firm_name, acn.name as account_name, acn.account_id as account_id')
            ->where('atp.status', 'Active')
            ->join('firm_name as frn', 'frn.id = atp.firm_name_id', 'left')
            ->join('aa_account_name as acn', 'frn.account_id_map = acn.account_id', 'left')
            ->get();
        $rows = $query->getResult();
        return $rows ?: [];
    }

    /** Products (stock_detail) for the active firm, optionally one HSN. */
    public function position_products($hsn = null)
    {
        $b = $this->db()->table('stock_detail as sd')
            ->select('sd.id, sd.product_name, sd.hsn_code_id, sd.opening_stock,
                       sd.stock_unit, sd.rate, hsc.hsn_code')
            ->join('hsn_codes as hsc', 'hsc.id = sd.hsn_code_id', 'left')
            ->where('sd.template_id', fy()->template_id);
        if (! empty($hsn) && $hsn !== 'none') {
            $b->where('hsc.hsn_code', $hsn);
        }
        $b->orderBy('sd.product_name', 'asc');
        return $b->get()->getResult();
    }

    /** Net movement per hsn_code_id strictly BEFORE $from. */
    public function position_net_before($from, $hsn = null)
    {
        $b = $this->db()->table('stock_log_details as sld')
            ->select("sld.hsn_code_id,
                SUM(CASE WHEN sld.type_of_invoice='purchase'   THEN IFNULL(sld.purchase_stock,0)   ELSE 0 END)
              + SUM(CASE WHEN sld.type_of_invoice='production' THEN IFNULL(sld.production_stock,0) ELSE 0 END)
              - SUM(CASE WHEN sld.type_of_invoice='sale'       THEN IFNULL(sld.sales_stock,0)      ELSE 0 END) as net", false);
        if (! empty($hsn) && $hsn !== 'none') {
            $b->join('hsn_codes as hsc', 'hsc.id = sld.hsn_code_id', 'left');
            $b->where('hsc.hsn_code', $hsn);
        }
        $b->where('sld.template_id', fy()->template_id);
        $b->where("COALESCE(sld.status,'') NOT IN ('Inactive','Delete')", null, false);
        if (! empty($from)) {
            $b->where('STR_TO_DATE(sld.date, "%Y-%m-%d") <', $from);
        }
        $b->groupBy('sld.hsn_code_id');

        $out = [];
        foreach ($b->get()->getResult() as $r) {
            $out[$r->hsn_code_id] = (float) $r->net;
        }
        return $out;
    }

    /** Purchase/production/sale totals per hsn_code_id within [$from,$to]. */
    public function position_between($from, $to, $hsn = null)
    {
        $b = $this->db()->table('stock_log_details as sld')
            ->select("sld.hsn_code_id,
                SUM(CASE WHEN sld.type_of_invoice='purchase'   THEN IFNULL(sld.purchase_stock,0)   ELSE 0 END) as purchase,
                SUM(CASE WHEN sld.type_of_invoice='production' THEN IFNULL(sld.production_stock,0) ELSE 0 END) as production,
                SUM(CASE WHEN sld.type_of_invoice='sale'       THEN IFNULL(sld.sales_stock,0)      ELSE 0 END) as sale", false);
        if (! empty($hsn) && $hsn !== 'none') {
            $b->join('hsn_codes as hsc', 'hsc.id = sld.hsn_code_id', 'left');
            $b->where('hsc.hsn_code', $hsn);
        }
        $b->where('sld.template_id', fy()->template_id);
        $b->where("COALESCE(sld.status,'') NOT IN ('Inactive','Delete')", null, false);
        if (! empty($from)) {
            $b->where('STR_TO_DATE(sld.date, "%Y-%m-%d") >=', $from);
        }
        if (! empty($to)) {
            $b->where('STR_TO_DATE(sld.date, "%Y-%m-%d") <=', $to);
        }
        $b->groupBy('sld.hsn_code_id');

        $out = [];
        foreach ($b->get()->getResult() as $r) {
            $out[$r->hsn_code_id] = [
                'purchase'   => (float) $r->purchase,
                'production' => (float) $r->production,
                'sale'       => (float) $r->sale,
            ];
        }
        return $out;
    }

    /** Per-month movement totals for every product (whole FY). */
    public function position_monthly($hsn = null)
    {
        $b = $this->db()->table('stock_log_details as sld')
            ->select("sld.hsn_code_id, sld.year, sld.month,
                SUM(CASE WHEN sld.type_of_invoice='purchase'   THEN IFNULL(sld.purchase_stock,0)   ELSE 0 END) as purchase,
                SUM(CASE WHEN sld.type_of_invoice='production' THEN IFNULL(sld.production_stock,0) ELSE 0 END) as production,
                SUM(CASE WHEN sld.type_of_invoice='sale'       THEN IFNULL(sld.sales_stock,0)      ELSE 0 END) as sale", false);
        if (! empty($hsn) && $hsn !== 'none') {
            $b->join('hsn_codes as hsc', 'hsc.id = sld.hsn_code_id', 'left');
            $b->where('hsc.hsn_code', $hsn);
        }
        $b->where('sld.template_id', fy()->template_id);
        $b->where("COALESCE(sld.status,'') NOT IN ('Inactive','Delete')", null, false);
        $b->groupBy(['sld.hsn_code_id', 'sld.year', 'sld.month']);
        $b->orderBy('sld.year', 'asc');
        $b->orderBy('sld.month', 'asc');

        $out = [];
        foreach ($b->get()->getResult() as $r) {
            $out[$r->hsn_code_id][] = [
                'year'       => (int) $r->year,
                'month'      => (int) $r->month,
                'purchase'   => (float) $r->purchase,
                'production' => (float) $r->production,
                'sale'       => (float) $r->sale,
            ];
        }
        return $out;
    }

    /** Purchase rate analysis (from purchase_bills) for the charts tab. */
    public function position_purchase_analysis($from = null, $to = null, $hsn = null)
    {
        $db = $this->db();
        $applyFilters = function ($b) use ($from, $to, $hsn) {
            $b->join('hsn_codes as hsc', 'hsc.id = pb.hsn_code_id', 'left');
            $b->where('pb.template_id', fy()->template_id);
            $b->where('pb.status !=', 'Delete');
            if (! empty($hsn) && $hsn !== 'none') {
                $b->where('hsc.hsn_code', $hsn);
            }
            if (! empty($from)) {
                $b->where('STR_TO_DATE(pb.invoice_date, "%Y-%m-%d") >=', $from);
            }
            if (! empty($to)) {
                $b->where('STR_TO_DATE(pb.invoice_date, "%Y-%m-%d") <=', $to);
            }
            return $b;
        };

        // Per product.
        $b = $db->table('purchase_bills as pb')
            ->select("hsc.product_name, hsc.hsn_code,
                SUM(CAST(pb.weight AS DECIMAL(20,6)))  as qty,
                SUM(CAST(pb.amount AS DECIMAL(20,6)))  as amount,
                MIN(CAST(pb.rate   AS DECIMAL(20,6)))  as min_rate,
                MAX(CAST(pb.rate   AS DECIMAL(20,6)))  as max_rate", false);
        $applyFilters($b);
        $b->groupBy('pb.hsn_code_id');
        $b->orderBy('hsc.product_name', 'asc');
        $by_product = [];
        foreach ($b->get()->getResult() as $r) {
            $qty    = (float) $r->qty;
            $amount = (float) $r->amount;
            $by_product[] = [
                'product_name' => (string) $r->product_name,
                'hsn_code'     => (string) $r->hsn_code,
                'qty'          => $qty,
                'amount'       => $amount,
                'avg_rate'     => $qty > 0 ? round($amount / $qty, 2) : 0,
                'min_rate'     => (float) $r->min_rate,
                'max_rate'     => (float) $r->max_rate,
            ];
        }

        // Per month (rate trend).
        $b = $db->table('purchase_bills as pb')
            ->select("YEAR(STR_TO_DATE(pb.invoice_date, '%Y-%m-%d'))  as y,
                       MONTH(STR_TO_DATE(pb.invoice_date, '%Y-%m-%d')) as m,
                SUM(CAST(pb.weight AS DECIMAL(20,6))) as qty,
                SUM(CAST(pb.amount AS DECIMAL(20,6))) as amount", false);
        $applyFilters($b);
        $b->groupBy(['y', 'm']);
        $b->orderBy('y', 'asc');
        $b->orderBy('m', 'asc');
        $months = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];
        $by_month = [];
        foreach ($b->get()->getResult() as $r) {
            $qty    = (float) $r->qty;
            $amount = (float) $r->amount;
            $m      = (int) $r->m;
            $by_month[] = [
                'label'    => (isset($months[$m]) ? $months[$m] : $m) . ' ' . (int) $r->y,
                'qty'      => $qty,
                'avg_rate' => $qty > 0 ? round($amount / $qty, 2) : 0,
            ];
        }

        // Individual purchases (rate vs quantity scatter).
        $b = $db->table('purchase_bills as pb')
            ->select("hsc.product_name,
                CAST(pb.weight AS DECIMAL(20,6)) as qty,
                CAST(pb.rate   AS DECIMAL(20,6)) as rate", false);
        $applyFilters($b);
        $b->where('CAST(pb.weight AS DECIMAL(20,6)) >', 0);
        $b->orderBy('pb.id', 'desc');
        $b->limit(1000);
        $points = [];
        foreach ($b->get()->getResult() as $r) {
            $points[] = [
                'product' => (string) $r->product_name,
                'qty'     => (float) $r->qty,
                'rate'    => (float) $r->rate,
            ];
        }

        return [
            'by_product' => $by_product,
            'by_month'   => $by_month,
            'points'     => $points,
        ];
    }

    /** Full ledger movement detail for one product (drill-down popup). */
    public function position_movement_details($hsn_code_id, $from = null, $to = null)
    {
        helper(['url', 'app']);
        $db = $this->db();
        $hsn_code_id = (int) $hsn_code_id;
        if ($hsn_code_id <= 0) {
            return [];
        }

        $col = function ($alias, $table, $field, $as) use ($db) {
            return $db->fieldExists($field, $table)
                ? $alias . '.' . $field . ' as ' . $as
                : 'NULL as ' . $as;
        };
        $select = [
            'sld.id', 'sld.date', 'sld.invoice_no', 'sld.type_of_invoice', 'sld.invoice_type',
            'sld.product_name', 'sld.hsn_code', 'sld.purchase_stock', 'sld.production_stock',
            'sld.sales_stock', 'sld.status', 'sld.rokad_jama_id', 'sld.rokad_nama_id',
            'hsc.product_name as master_product_name',
            'hsc.hsn_code as master_hsn_code',
            'arj.rokad_id as jama_rokad_id',
            'arj.account_name as jama_account_name',
            'arj.account_no as jama_account_no',
            'arj.remark as jama_remark',
            'arn.rokad_id as nama_rokad_id',
            'arn.account_name as nama_account_name',
            'arn.account_no as nama_account_no',
            'arn.remark as nama_remark',
        ];

        $b = $db->table('stock_log_details as sld');
        $b->join('hsn_codes as hsc', 'hsc.id = sld.hsn_code_id', 'left');
        $b->join('aa_rokad as arj', 'arj.rokad_id = sld.rokad_jama_id', 'left');
        $b->join('aa_rokad as arn', 'arn.rokad_id = sld.rokad_nama_id', 'left');
        if ($db->tableExists('purchase_bills')) {
            $select[] = $col('pb', 'purchase_bills', 'id', 'purchase_id');
            $select[] = $col('pb', 'purchase_bills', 'invoice_no', 'purchase_invoice_no');
            $select[] = $col('pb', 'purchase_bills', 'account_id', 'purchase_account_id');
            $select[] = $col('pb', 'purchase_bills', 'naam_id', 'purchase_naam_id');
            $select[] = $col('pb', 'purchase_bills', 'remark', 'purchase_remark');
            $select[] = 'pba.name as purchase_party_name';
            $b->join('purchase_bills as pb', 'pb.rokadh_jama_id = sld.rokad_jama_id AND pb.rokadh_nama_id = sld.rokad_nama_id', 'left');
            $b->join('aa_account_name as pba', 'pba.account_id = pb.account_id', 'left');
        } else {
            $select[] = 'NULL as purchase_id';
            $select[] = 'NULL as purchase_invoice_no';
            $select[] = 'NULL as purchase_account_id';
            $select[] = 'NULL as purchase_naam_id';
            $select[] = 'NULL as purchase_remark';
            $select[] = 'NULL as purchase_party_name';
        }
        if ($db->tableExists('invoice_system')) {
            $select[] = $col('inv', 'invoice_system', 'bos_id', 'bos_id');
            $select[] = $col('inv', 'invoice_system', 'invoice_id', 'bos_invoice_id');
            $select[] = $col('inv', 'invoice_system', 'account_id', 'bos_account_id');
            $select[] = $col('inv', 'invoice_system', 'remark', 'bos_remark');
            $select[] = 'iba.name as bos_party_name';
            $b->join('invoice_system as inv', 'inv.rokadh_jama_id = sld.rokad_jama_id AND inv.rokadh_nama_id = sld.rokad_nama_id', 'left');
            $b->join('aa_account_name as iba', 'iba.account_id = inv.account_id', 'left');
        } else {
            $select[] = 'NULL as bos_id';
            $select[] = 'NULL as bos_invoice_id';
            $select[] = 'NULL as bos_account_id';
            $select[] = 'NULL as bos_remark';
            $select[] = 'NULL as bos_party_name';
        }
        if ($db->tableExists('uninvoice_system')) {
            $select[] = $col('ub', 'uninvoice_system', 'invoice_id', 'ubos_invoice_id');
            $select[] = $col('ub', 'uninvoice_system', 'bos_id', 'ubos_bos_id');
            $select[] = $col('ub', 'uninvoice_system', 'account_id', 'ubos_account_id');
            $select[] = $col('ub', 'uninvoice_system', 'naam', 'ubos_naam_id');
            $select[] = $col('ub', 'uninvoice_system', 'remark', 'ubos_remark');
            $select[] = 'uba.name as ubos_party_name';
            $b->join('uninvoice_system as ub', 'ub.rokadh_jama_id = sld.rokad_jama_id AND ub.rokadh_nama_id = sld.rokad_nama_id', 'left');
            $b->join('aa_account_name as uba', 'uba.account_id = ub.account_id', 'left');
        } else {
            $select[] = 'NULL as ubos_invoice_id';
            $select[] = 'NULL as ubos_bos_id';
            $select[] = 'NULL as ubos_account_id';
            $select[] = 'NULL as ubos_naam_id';
            $select[] = 'NULL as ubos_remark';
            $select[] = 'NULL as ubos_party_name';
        }
        if ($db->tableExists('tax_invoice_system')) {
            $select[] = $col('tax', 'tax_invoice_system', 'tax_invoice_id', 'tax_invoice_id');
            $select[] = $col('tax', 'tax_invoice_system', 'tax_invoice_fy_id', 'tax_invoice_fy_id');
            $select[] = $col('tax', 'tax_invoice_system', 'einvoice_no', 'tax_einvoice_no');
            $select[] = $col('tax', 'tax_invoice_system', 'account_id', 'tax_account_id');
            $select[] = $col('tax', 'tax_invoice_system', 'remark', 'tax_remark');
            $select[] = 'taxa.name as tax_party_name';
            $b->join('tax_invoice_system as tax', 'tax.rokadh_jama_id = sld.rokad_jama_id AND tax.rokadh_nama_id = sld.rokad_nama_id', 'left');
            $b->join('aa_account_name as taxa', 'taxa.account_id = tax.account_id', 'left');
        } else {
            $select[] = 'NULL as tax_invoice_id';
            $select[] = 'NULL as tax_invoice_fy_id';
            $select[] = 'NULL as tax_einvoice_no';
            $select[] = 'NULL as tax_account_id';
            $select[] = 'NULL as tax_remark';
            $select[] = 'NULL as tax_party_name';
        }
        $b->select(implode(",\n", $select), false);
        $b->where('sld.template_id', fy()->template_id);
        $b->where('sld.hsn_code_id', $hsn_code_id);
        $b->where("COALESCE(sld.status,'') NOT IN ('Inactive','Delete')", null, false);
        if (! empty($from)) {
            $b->where('STR_TO_DATE(sld.date, "%Y-%m-%d") >=', $from);
        }
        if (! empty($to)) {
            $b->where('STR_TO_DATE(sld.date, "%Y-%m-%d") <=', $to);
        }
        $b->orderBy('STR_TO_DATE(sld.date, "%Y-%m-%d")', 'asc', false);
        $b->orderBy('sld.type_of_invoice', 'asc');
        $b->orderBy('sld.id', 'asc');
        $b->limit(500);

        $query = $b->get();
        if (! $query) {
            log_message('error', 'Stock movement details query failed for hsn_code_id=' . $hsn_code_id);
            return [];
        }
        $rows = [];
        foreach ($query->getResultArray() as $r) {
            $source     = $this->position_source_from_movement($r);
            $purchase   = (float) $r['purchase_stock'];
            $production = (float) $r['production_stock'];
            $sale       = (float) $r['sales_stock'];
            $net        = $purchase + $production - $sale;

            $rows[] = [
                'date'          => $r['date'],
                'invoice_no'    => $r['invoice_no'],
                'type'          => $r['type_of_invoice'],
                'invoice_type'  => $r['invoice_type'],
                'product_name'  => ! empty($r['master_product_name']) ? $r['master_product_name'] : $r['product_name'],
                'hsn_code'      => ! empty($r['master_hsn_code']) ? $r['master_hsn_code'] : $r['hsn_code'],
                'purchase'      => $purchase,
                'production'    => $production,
                'sale'          => $sale,
                'net'           => $net,
                'status'        => $r['status'],
                'rokad_jama_id' => $r['rokad_jama_id'],
                'rokad_nama_id' => $r['rokad_nama_id'],
                'jama_account'  => $r['jama_account_name'],
                'nama_account'  => $r['nama_account_name'],
                'jama_remark'   => $r['jama_remark'],
                'nama_remark'   => $r['nama_remark'],
                'source_label'  => $source['label'],
                'source_id'     => $source['id'],
                'source_no'     => $source['no'],
                'source_party'  => $source['party'],
                'source_remark' => $source['remark'],
                'source_url'    => $source['url'],
            ];
        }
        return $rows;
    }

    private function position_source_from_movement($r)
    {
        $base = ['label' => ucwords((string) $r['invoice_type']), 'id' => '', 'no' => $r['invoice_no'], 'party' => '', 'remark' => '', 'url' => ''];

        if (! empty($r['purchase_id'])) {
            return [
                'label'  => 'Purchase Bill',
                'id'     => $r['purchase_id'],
                'no'     => ! empty($r['purchase_invoice_no']) ? $r['purchase_invoice_no'] : $r['invoice_no'],
                'party'  => ! empty($r['purchase_party_name']) ? $r['purchase_party_name'] : $r['jama_account_name'],
                'remark' => $r['purchase_remark'],
                'url'    => base_url('admin/purchase_module/view/' . ID_encode($r['purchase_id'])),
            ];
        }
        if (! empty($r['bos_id'])) {
            return [
                'label'  => 'Bill of Supply',
                'id'     => $r['bos_id'],
                'no'     => ! empty($r['bos_invoice_id']) ? $r['bos_invoice_id'] : $r['invoice_no'],
                'party'  => ! empty($r['bos_party_name']) ? $r['bos_party_name'] : $r['jama_account_name'],
                'remark' => $r['bos_remark'],
                'url'    => base_url('admin/invoice/view/' . ID_encode($r['bos_id'])),
            ];
        }
        if (! empty($r['ubos_invoice_id'])) {
            return [
                'label'  => 'Unregistered BOS',
                'id'     => $r['ubos_invoice_id'],
                'no'     => ! empty($r['ubos_bos_id']) ? $r['ubos_bos_id'] : $r['invoice_no'],
                'party'  => ! empty($r['ubos_party_name']) ? $r['ubos_party_name'] : $r['jama_account_name'],
                'remark' => $r['ubos_remark'],
                'url'    => base_url('admin/uninvoice/view/' . ID_encode($r['ubos_invoice_id'])),
            ];
        }
        if (! empty($r['tax_invoice_id'])) {
            return [
                'label'  => 'E-Tax Invoice',
                'id'     => $r['tax_invoice_id'],
                'no'     => ! empty($r['tax_invoice_fy_id']) ? $r['tax_invoice_fy_id'] : (! empty($r['tax_einvoice_no']) ? $r['tax_einvoice_no'] : $r['invoice_no']),
                'party'  => ! empty($r['tax_party_name']) ? $r['tax_party_name'] : $r['jama_account_name'],
                'remark' => $r['tax_remark'],
                'url'    => base_url('admin/taxinvoice/einvoice_view/' . ID_encode($r['tax_invoice_id'])),
            ];
        }

        $base['party']  = ! empty($r['jama_account_name']) ? $r['jama_account_name'] : $r['nama_account_name'];
        $base['remark'] = ! empty($r['jama_remark']) ? $r['jama_remark'] : $r['nama_remark'];
        return $base;
    }

    /* ============= Firm stock valuation (CI3 Stock_mod parity) — Trading Profit ============= */

    /**
     * Stock value/qty/items for the current firm as of a date. Master opening_stock
     * is the FY opening, so movements are counted only from $fy_start up to $asof
     * (else pre-FY movements double-count). Both dates optional.
     */
    public function firm_stock_value_asof($fy_start = '', $asof = ''): array
    {
        $tid  = (int) fy()->template_id;
        $FY   = fy()->FY;
        $bind = [$tid];

        $dateClause = '';
        if ($fy_start !== '') { $dateClause .= ' AND date >= ?'; $bind[] = $fy_start; }
        if ($asof !== '')     { $dateClause .= ' AND date <= ?'; $bind[] = $asof; }
        $bind[] = $tid;
        $bind[] = $FY;

        $sql = "SELECT COALESCE(SUM((sd.opening_stock + IFNULL(mv.net,0)) * sd.rate),0) AS value,
                       COALESCE(SUM(sd.opening_stock + IFNULL(mv.net,0)),0) AS qty,
                       COUNT(*) AS items
            FROM stock_detail sd
            LEFT JOIN (
                SELECT hsn_code_id, SUM(CASE
                        WHEN type_of_invoice='purchase'   THEN IFNULL(purchase_stock,0)
                        WHEN type_of_invoice='production' THEN IFNULL(production_stock,0)
                        WHEN type_of_invoice='sale'       THEN -IFNULL(sales_stock,0)
                        ELSE 0 END) AS net
                FROM stock_log_details
                WHERE template_id = ? AND COALESCE(status,'') NOT IN ('Inactive','Delete')" . $dateClause . "
                GROUP BY hsn_code_id
            ) mv ON mv.hsn_code_id = sd.hsn_code_id
            WHERE sd.template_id = ? AND sd.FY = ? AND COALESCE(sd.status,'') <> 'Delete'";
        $row = $this->db()->query($sql, $bind)->getRow();
        return [
            'value' => $row ? (float) $row->value : 0,
            'qty'   => $row ? (float) $row->qty : 0,
            'items' => $row ? (int) $row->items : 0,
        ];
    }

    /**
     * Opening & closing stock valuation for a period: opening = value the day
     * BEFORE $from; closing = value as of $to. Feeds the Trading Profit report
     * (gross profit = (Sales − Purchase) + (Closing − Opening stock)).
     */
    public function firm_stock_valuation($from = '', $to = '', $fy_start = ''): array
    {
        $open_before = '';
        if ($from !== '') {
            $ts = strtotime($from . ' -1 day');
            $open_before = $ts ? date('Y-m-d', $ts) : '';
        }
        $open  = $this->firm_stock_value_asof($fy_start, $open_before);
        $close = $this->firm_stock_value_asof($fy_start, $to);
        return [
            'items'         => $close['items'],
            'opening_value' => $open['value'],
            'closing_value' => $close['value'],
            'closing_qty'   => $close['qty'],
        ];
    }
}
