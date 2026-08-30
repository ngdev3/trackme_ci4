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
}
