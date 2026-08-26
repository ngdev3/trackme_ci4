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
     * TRUE gross profit for a period = revenue − COST OF GOODS SOLD, over Sale
     * bills (Sale Returns subtract back out). Revenue is the sale-line total;
     * COGS is the cost captured AT SALE TIME, read from stock_movements.rate
     * (stamped when the bill posted) — NOT the product's current cost, so editing
     * a product's cost later can't retroactively rewrite past profit (audit F-04).
     *
     * Buying stock is an asset swap, so purchases never appear here. Pass $party
     * to scope the profit to one account (used by the Account Statement); 0 when
     * the billing tables aren't present.
     */
    public function salesProfit(?int $companyId, ?string $from, ?string $to, ?string $party = null): float
    {
        $db = $this->db;
        if (! $db->tableExists('invoices') || ! $db->tableExists('invoice_items') || ! $db->tableExists('stock_movements')) {
            return 0.0;
        }
        $sale   = $this->typeRevenueCogs($db, 'sale', $companyId, $from, $to, $party);
        $return = $this->typeRevenueCogs($db, 'sale_return', $companyId, $from, $to, $party);
        // (sale revenue − sale COGS) − (return revenue − return COGS)
        return round(($sale['rev'] - $sale['cogs']) - ($return['rev'] - $return['cogs']), 2);
    }

    /**
     * Revenue (Σ line amount) and COGS (Σ movement qty × cost-at-sale) for one
     * invoice type over a range, optionally scoped to a party. COGS joins the
     * stock movements by note = invoice_no (how the posting engine links them);
     * a free-text line has no movement, so it contributes revenue but no cost.
     *
     * @return array{rev:float, cogs:float}
     */
    private function typeRevenueCogs($db, string $type, ?int $companyId, ?string $from, ?string $to, ?string $party): array
    {
        // A sale line took goods OUT (its cost basis lives on the OUT movement); a
        // sale-return brought them back IN. Literal, never user input.
        $dir = $type === 'sale' ? 'out' : 'in';

        // Per-LINE cost lookup: the unit cost stamped on THIS invoice's own movement
        // for THIS product. Scoping by product_id keeps a movement that merely shares
        // an invoice_no (e.g. a reused number) from leaking in, and a free-text line
        // with no movement contributes revenue but NULL → 0 cost.
        $cogsExpr = "SUM(ii.qty * (SELECT sm.rate FROM stock_movements sm"
            . " WHERE sm.note = inv.invoice_no AND sm.company_id = inv.company_id"
            . "   AND sm.product_id = ii.product_id AND sm.type = '{$dir}'"
            . " ORDER BY sm.id LIMIT 1))";

        $b = $db->table('invoices inv')
            ->select("COALESCE(SUM(ii.amount), 0) AS rev, COALESCE({$cogsExpr}, 0) AS cogs", false)
            ->join('invoice_items ii', 'ii.invoice_id = inv.id')
            ->where('inv.company_id', (int) $companyId)
            ->where('inv.type', $type)
            ->where('inv.deleted_at', null);
        if ($from !== null && $from !== '') { $b->where('inv.invoice_date >=', $from); }
        if ($to !== null && $to !== '')     { $b->where('inv.invoice_date <=', $to); }
        if ($party !== null && $party !== '') { $b->where('inv.party_name', $party); }

        $row = $b->get()->getRowArray();
        return ['rev' => (float) ($row['rev'] ?? 0), 'cogs' => (float) ($row['cogs'] ?? 0)];
    }

    /**
     * GST captured on the bills for a period, from the tax ALREADY stored on each
     * invoice (invoices.tax_total) — no separate tax ledger/schema (audit F-07,
     * no-schema variant). Output GST = tax charged on Sales (Sale Returns reverse);
     * Input GST = tax paid on Purchases (Purchase Returns reverse); Net = output −
     * input = GST payable (negative = input-tax credit in hand). This SURFACES the
     * liability the bills collected; it does NOT post GST as its own ledger entry
     * (that would need a schema change).
     *
     * @return array{output:float, input:float, net:float}
     */
    public function gstSummary(?int $companyId, string $from, string $to): array
    {
        $out = ['output' => 0.0, 'input' => 0.0, 'net' => 0.0];
        if (! $this->db->tableExists('invoices')) {
            return $out;
        }
        $rows = $this->builder()
            ->select('type, COALESCE(SUM(tax_total), 0) AS t', false)
            ->where('company_id', (int) $companyId)
            ->where('deleted_at', null)
            ->where('invoice_date >=', $from)
            ->where('invoice_date <=', $to)
            ->groupBy('type')
            ->get()->getResultArray();

        $by = [];
        foreach ($rows as $r) {
            $by[(string) $r['type']] = (float) $r['t'];
        }
        $out['output'] = round(($by['sale'] ?? 0) - ($by['sale_return'] ?? 0), 2);
        $out['input']  = round(($by['purchase'] ?? 0) - ($by['purchase_return'] ?? 0), 2);
        $out['net']    = round($out['output'] - $out['input'], 2);
        return $out;
    }

    /**
     * GST register for a period — one row per TAXED bill (output for sales, input
     * for purchases), newest first, for a filing-ready listing. Derived straight
     * from `invoices` (the model's soft-delete scope drops voided bills), so it
     * covers every bill with no separate ledger to keep in sync (audit F-07).
     *
     * @return array<int, array<string,mixed>>
     */
    public function gstRegister(?int $companyId, string $from, string $to, int $limit = 200): array
    {
        if (! $this->db->tableExists('invoices')) {
            return [];
        }
        $rows = $this->select('invoice_no, type, party_name, invoice_date, subtotal, tax_total')
            ->where('company_id', (int) $companyId)
            ->where('tax_total >', 0)
            ->where('invoice_date >=', $from)
            ->where('invoice_date <=', $to)
            ->orderBy('invoice_date', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);

        return array_map(static fn (array $r): array => [
            'invoice_no'   => $r['invoice_no'],
            'type'         => $r['type'],
            'direction'    => in_array($r['type'], ['sale', 'sale_return'], true) ? 'output' : 'input',
            'party_name'   => $r['party_name'],
            'invoice_date' => $r['invoice_date'],
            'taxable'      => round((float) $r['subtotal'], 2),
            'tax'          => round((float) $r['tax_total'], 2),
        ], $rows);
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
