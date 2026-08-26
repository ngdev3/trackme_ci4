<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Sales / Purchase invoices (bills). Company-scoped; always query through
 * {@see scoped()}. Each invoice links to its cash-book transaction (txn_id) and
 * has rows in invoice_items.
 */
class InvoiceModel extends Model
{
    protected $table          = 'invoices';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'company_id', 'client_uuid', 'created_by', 'type', 'ref_invoice_id', 'invoice_no', 'party_name', 'party_type', 'party_id',
        'invoice_date', 'subtotal', 'tax_total', 'discount', 'total', 'received',
        'payment_mode', 'status', 'txn_id', 'pay_txn_id', 'notes',
    ];

    /** Company-scoped builder (never query invoices without it). */
    public function scoped(?int $companyId)
    {
        return $this->where('company_id', (int) $companyId);
    }

    /**
     * Billed totals per document type within a date range (voided/soft-deleted
     * excluded). Powers the accrual dashboard: net sales = sale − sale_return,
     * net purchases = purchase − purchase_return.
     *
     * @return array{sale:float, purchase:float, sale_return:float, purchase_return:float,
     *               net_sales:float, net_purchases:float, count:int}
     */
    public function periodTotals(?int $companyId, string $from, string $to): array
    {
        $rows = $this->builder()
            ->select("type, COALESCE(SUM(total),0) AS t, COUNT(*) AS c", false)
            ->where('company_id', (int) $companyId)
            ->where('deleted_at', null)
            ->where('invoice_date >=', $from)
            ->where('invoice_date <=', $to)
            ->groupBy('type')
            ->get()->getResultArray();

        $out = ['sale' => 0.0, 'purchase' => 0.0, 'sale_return' => 0.0, 'purchase_return' => 0.0, 'count' => 0];
        foreach ($rows as $r) {
            $t = (string) $r['type'];
            if (array_key_exists($t, $out)) {
                $out[$t] = (float) $r['t'];
            }
            $out['count'] += (int) $r['c'];
        }
        $out['net_sales']     = round($out['sale'] - $out['sale_return'], 2);
        $out['net_purchases'] = round($out['purchase'] - $out['purchase_return'], 2);
        return $out;
    }

    /**
     * TRUE gross profit for a period = Σ line qty × (sale rate − product cost),
     * over Sale bills (Sale Returns subtract back out). This is revenue minus the
     * COST OF GOODS SOLD — NOT sales − total purchases (buying stock is an asset,
     * not an expense, so it must not drive profit negative). Uses the product's
     * current cost; 0 when the billing tables/products aren't present.
     */
    public function salesProfit(?int $companyId, string $from, string $to): float
    {
        if (! $this->db->tableExists('invoice_items') || ! $this->db->tableExists('products')) {
            return 0.0;
        }
        $row = $this->db->table('invoices inv')
            ->select("COALESCE(SUM("
                . "(CASE inv.type WHEN 'sale' THEN 1 WHEN 'sale_return' THEN -1 ELSE 0 END)"
                . " * ii.qty * (ii.rate - COALESCE(p.purchase_price, 0))"
                . "), 0) AS profit", false)
            ->join('invoice_items ii', 'ii.invoice_id = inv.id')
            ->join('products p', 'p.id = ii.product_id', 'left')
            ->where('inv.company_id', (int) $companyId)
            ->where('inv.deleted_at', null)
            ->whereIn('inv.type', ['sale', 'sale_return'])
            ->where('inv.invoice_date >=', $from)
            ->where('inv.invoice_date <=', $to)
            ->get()->getRowArray();

        return round((float) ($row['profit'] ?? 0), 2);
    }

    /**
     * Next invoice number for a company + type, e.g. INV-000123 / PUR-000045.
     * Sequence is per company and per type.
     */
    public function nextInvoiceNo(?int $companyId, string $type): string
    {
        $prefix = ['sale' => 'INV', 'purchase' => 'PUR', 'sale_return' => 'SRT', 'purchase_return' => 'PRT'][$type] ?? 'INV';
        $n = $this->withDeleted()
            ->where('company_id', (int) $companyId)
            ->where('type', $type)
            ->countAllResults();
        return $prefix . '-' . str_pad((string) ($n + 1), 6, '0', STR_PAD_LEFT);
    }
}
