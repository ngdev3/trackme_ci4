<?php

namespace Modules\Api\Controllers;

use App\Models\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\ProductModel;
use App\Models\StockMovementModel;
use App\Models\TransactionModel;

/**
 * Sales / Purchase invoices (bills) with full integration:
 *   - a SALE bill issues stock (out) and posts a Jama (money-in) cash entry,
 *   - a PURCHASE bill receives stock (in) and posts a Naam (money-out) entry.
 * Each invoice stores the id of the transaction it created so the cash book and
 * the bill stay linked. Company-scoped; the active company comes from the token.
 *
 *   GET  api/v1/invoices           (Bearer) [?type=sale|purchase]
 *   GET  api/v1/invoices/(:num)    (Bearer)  full bill + line items
 *   POST api/v1/invoices           (Bearer)  create a bill (adjusts stock + cash book)
 */
class InvoiceApiController extends BaseApiController
{
    /** Max quantity for a bill line / stock figure — fits the DECIMAL(12,3) columns. */
    public const MAX_QTY = 99999999.999;

    private function scope(): array
    {
        $user = $this->currentApiUser();
        return $user ? [$user, $this->resolveCompanyId($user)] : [null, null];
    }

    /** List invoices (headers only), newest first. */
    public function index()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $type  = $this->request->getGet('type');
        $model = (new InvoiceModel())->scoped($cid)->orderBy('id', 'DESC');
        if ($type === 'sale' || $type === 'purchase') {
            $model->where('type', $type);
        }
        $rows = $model->findAll(200);
        return $this->respond([
            'status'   => 'ok',
            'invoices' => array_map([$this, 'shapeHeader'], $rows),
        ]);
    }

    /** One invoice with its line items (for re-print). */
    public function show($id = null)
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $inv = (new InvoiceModel())->scoped($cid)->find((int) $id);
        if (! $inv) {
            return $this->failNotFound('Invoice not found.');
        }
        $items = (new InvoiceItemModel())->forInvoice((int) $inv['id']);
        return $this->respond([
            'status'  => 'ok',
            'invoice' => $this->shapeHeader($inv) + ['items' => array_map([$this, 'shapeItem'], $items)],
        ]);
    }

    /** Create a sale / purchase bill. Adjusts stock and posts a cash-book entry. */
    public function create()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $type      = $this->input('type') === 'purchase' ? 'purchase' : 'sale';
        $partyName = trim((string) ($this->input('party_name') ?? ''));
        $partyType = trim((string) ($this->input('party_type') ?? ''));
        $mode      = (string) ($this->input('payment_mode') ?? 'cash');
        $notes     = trim((string) ($this->input('notes') ?? '')) ?: null;
        $discount  = round((float) ($this->input('discount') ?? 0), 2);
        $dateIn    = (string) ($this->input('invoice_date') ?? '');
        $ts        = strtotime($dateIn);
        $invDate   = $ts !== false ? date('Y-m-d', $ts) : date('Y-m-d');

        if (! in_array($mode, TransactionModel::MODES, true)) {
            $mode = 'cash';
        }

        $parsed = $this->parseLines($this->input('items'), $cid);
        if (isset($parsed['error'])) {
            return $this->failValidationErrors($parsed['error']);
        }
        $lines    = $parsed['lines'];
        $subtotal = $parsed['subtotal'];
        $taxTotal = $parsed['taxTotal'];

        // Block overselling up front with a clear, per-product message.
        if ($type === 'sale' && ($short = $this->stockShortage($lines)) !== null) {
            return $this->failValidationErrors(['items' => $short]);
        }

        $total = round($subtotal + $taxTotal - $discount, 2);
        if ($total < 0) {
            $total = 0.0;
        }

        // "Received now": the amount actually collected/paid at billing time.
        // 0 = fully on credit (party owes the whole bill); >= total = fully paid.
        // When the field is omitted we default to the full total (a paid bill) so
        // older clients keep their previous behaviour.
        $receivedRaw = $this->input('received');
        $received    = ($receivedRaw === null || $receivedRaw === '')
            ? $total
            : round((float) $receivedRaw, 2);

        // ---- Post everything through the central engine (one DB transaction,
        //      idempotent, splits ledger vs cash vs stock) -----------------------
        try {
            $result = (new \App\Services\LedgerPostingService())->postInvoice([
                'company_id'   => $cid,
                'user_id'      => (int) $user['id'],
                'type'         => $type,
                'party_name'   => $partyName,
                'party_type'   => $partyType,
                'payment_mode' => $mode,
                'invoice_date' => $invDate,
                'notes'        => $notes,
                'discount'     => $discount,
                'subtotal'     => $subtotal,
                'tax_total'    => $taxTotal,
                'total'        => $total,
                'received'     => $received,
                'client_uuid'  => trim((string) ($this->input('client_uuid') ?? '')) ?: null,
                'lines'        => $lines,
            ]);
        } catch (\Throwable $e) {
            if (($msg = $this->stockErrorMessage($e)) !== null) {
                return $this->failValidationErrors(['items' => $msg]);
            }
            log_message('error', '[Invoice] post failed: ' . $e->getMessage());
            return $this->fail('Could not save the bill. Please try again.', 500);
        }

        $saved = $result['invoice'];
        $invNo = $saved['invoice_no'];
        if (! $result['duplicate'] && function_exists('activity_log')) {
            activity_log('Invoices', 'Add', ucfirst($type) . " bill {$invNo} created (mobile)");
        }
        // A bill posts a Jama/Naam cash entry + stock — bust the cached
        // Dashboard/Report aggregates so it shows immediately.
        if (! $result['duplicate'] && function_exists('dash_bust')) {
            dash_bust($cid);
        }

        return $this->respondCreated([
            'status'    => 'ok',
            'duplicate' => $result['duplicate'],
            'message'   => $result['duplicate']
                ? ('Bill ' . $invNo . ' was already saved.')
                : (($type === 'sale' ? 'Sale' : 'Purchase') . ' bill ' . $invNo . ' saved.'),
            'invoice'   => $this->shapeHeader($saved) + ['items' => array_map([$this, 'shapeItem'], $result['items'])],
        ]);
    }

    /**
     * POST invoices/return — a sale/purchase return. Reverses stock + party
     * ledger; an optional `received` is the refund paid/collected now.
     * {type: sale_return|purchase_return, ref_invoice_id?, party_name, items[], received?}
     */
    public function returnBill()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $type = $this->input('type') === 'purchase_return' ? 'purchase_return' : 'sale_return';
        $mode = (string) ($this->input('payment_mode') ?? 'cash');
        if (! in_array($mode, TransactionModel::MODES, true)) {
            $mode = 'cash';
        }
        $ts      = strtotime((string) ($this->input('invoice_date') ?? ''));
        $invDate = $ts !== false ? date('Y-m-d', $ts) : date('Y-m-d');

        $parsed = $this->parseLines($this->input('items'), $cid);
        if (isset($parsed['error'])) {
            return $this->failValidationErrors($parsed['error']);
        }
        $total   = round($parsed['subtotal'] + $parsed['taxTotal'] - round((float) ($this->input('discount') ?? 0), 2), 2);
        $total   = max(0.0, $total);
        $refRaw  = $this->input('received');
        $refund  = ($refRaw === null || $refRaw === '') ? 0.0 : round((float) $refRaw, 2);

        try {
            $result = (new \App\Services\LedgerPostingService())->postReturn([
                'company_id'   => $cid,
                'user_id'      => (int) $user['id'],
                'type'         => $type,
                'ref_invoice_id' => (int) ($this->input('ref_invoice_id') ?? 0) ?: null,
                'party_name'   => trim((string) ($this->input('party_name') ?? '')),
                'party_type'   => trim((string) ($this->input('party_type') ?? '')),
                'payment_mode' => $mode,
                'invoice_date' => $invDate,
                'notes'        => trim((string) ($this->input('notes') ?? '')) ?: null,
                'discount'     => round((float) ($this->input('discount') ?? 0), 2),
                'subtotal'     => $parsed['subtotal'],
                'tax_total'    => $parsed['taxTotal'],
                'total'        => $total,
                'received'     => $refund,
                'client_uuid'  => trim((string) ($this->input('client_uuid') ?? '')) ?: null,
                'lines'        => $parsed['lines'],
            ]);
        } catch (\Throwable $e) {
            if (($msg = $this->stockErrorMessage($e)) !== null) {
                return $this->failValidationErrors(['items' => $msg]);
            }
            log_message('error', '[Invoice] return failed: ' . $e->getMessage());
            return $this->fail('Could not save the return. Please try again.', 500);
        }

        $saved = $result['invoice'];
        if (! $result['duplicate'] && function_exists('activity_log')) {
            activity_log('Invoices', 'Add', str_replace('_', ' ', $type) . " {$saved['invoice_no']} created (mobile)");
        }
        // A return reverses stock + party ledger — bust the cached aggregates.
        if (! $result['duplicate'] && function_exists('dash_bust')) {
            dash_bust($cid);
        }
        return $this->respondCreated([
            'status'    => 'ok',
            'duplicate' => $result['duplicate'],
            'message'   => ($type === 'sale_return' ? 'Sale return ' : 'Purchase return ') . $saved['invoice_no'] . ($result['duplicate'] ? ' was already saved.' : ' saved.'),
            'invoice'   => $this->shapeHeader($saved) + ['items' => array_map([$this, 'shapeItem'], $result['items'])],
        ]);
    }

    /** POST invoices/(:num)/void — reverse a bill/return's stock + ledger + cash. */
    public function void($id = null)
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $reason = trim((string) ($this->input('reason') ?? ''));
        try {
            $res = (new \App\Services\LedgerPostingService())->voidInvoice((int) $id, (int) $cid, (int) $user['id'], $reason);
        } catch (\Throwable $e) {
            return $this->fail($e->getMessage(), 400);
        }
        if (function_exists('activity_log')) {
            activity_log('Invoices', 'Delete', "Bill {$res['invoice_no']} voided (mobile)" . ($reason ? " — {$reason}" : ''));
        }
        // Voiding reverses the cash entry + stock — bust the cached aggregates.
        if (function_exists('dash_bust')) {
            dash_bust($cid);
        }
        return $this->respond(['status' => 'ok', 'message' => 'Bill ' . $res['invoice_no'] . ' voided.']);
    }

    /**
     * Validate + normalise raw line items against the catalogue. Returns
     * ['lines'=>[], 'subtotal'=>float, 'taxTotal'=>float] or ['error'=>...].
     */
    private function parseLines($rawItems, int $cid): array
    {
        if (! is_array($rawItems) || count($rawItems) === 0) {
            return ['error' => ['items' => 'Add at least one product line.']];
        }
        $products = new ProductModel();
        $lines = [];
        $subtotal = 0.0;
        $taxTotal = 0.0;
        foreach ($rawItems as $it) {
            $pid  = (int) ($it['product_id'] ?? 0) ?: null;
            $qty  = round((float) ($it['qty'] ?? 0), 3);
            $rate = round((float) ($it['rate'] ?? 0), 2);
            $tax  = round((float) ($it['tax_rate'] ?? 0), 2);
            $name = trim((string) ($it['name'] ?? ''));
            if ($qty <= 0) {
                continue;
            }
            // Bounds — reject nonsense values (fat-finger / abuse). Qty fits the
            // DECIMAL(12,3) stock columns; rate/amount share the ₹ ceiling; tax is
            // a percentage.
            if ($qty > self::MAX_QTY) {
                return ['error' => ['items' => 'A quantity is too large (max ' . number_format(self::MAX_QTY) . ').']];
            }
            if ($rate < 0 || $rate > TransactionModel::MAX_AMOUNT) {
                return ['error' => ['items' => 'A rate is out of range (₹0 – ₹9,99,99,99,999.99).']];
            }
            if ($tax < 0 || $tax > 100) {
                return ['error' => ['items' => 'Tax % must be between 0 and 100.']];
            }
            $product = null;
            if ($pid) {
                $product = $products->scoped($cid)->find($pid);
                if (! $product) {
                    return ['error' => ['items' => 'A product on this document was not found.']];
                }
                if ($name === '') {
                    $name = $product['name'];
                }
            }
            if ($name === '') {
                $name = 'Item';
            }
            $lineAmt   = round($qty * $rate, 2);
            $subtotal += $lineAmt;
            $taxTotal += round($lineAmt * $tax / 100, 2);
            $lines[] = [
                'product_id' => $pid, 'product' => $product, 'name' => mb_substr($name, 0, 191),
                'qty' => $qty, 'rate' => $rate, 'tax_rate' => $tax, 'amount' => $lineAmt,
            ];
        }
        if (count($lines) === 0) {
            return ['error' => ['items' => 'Every line needs a quantity greater than zero.']];
        }
        return ['lines' => $lines, 'subtotal' => round($subtotal, 2), 'taxTotal' => round($taxTotal, 2)];
    }

    /**
     * Aggregate qty per product and flag any that exceed available stock.
     * Returns a friendly message, or null when everything is in stock.
     */
    private function stockShortage(array $lines): ?string
    {
        $need = [];   // product_id => [name, qty, available]
        foreach ($lines as $ln) {
            if (($ln['product'] ?? null) === null) {
                continue;
            }
            $pid = (int) $ln['product_id'];
            if (! isset($need[$pid])) {
                $need[$pid] = ['name' => $ln['name'], 'qty' => 0.0, 'have' => (float) $ln['product']['current_stock']];
            }
            $need[$pid]['qty'] += (float) $ln['qty'];
        }
        $short = [];
        foreach ($need as $n) {
            if ($n['qty'] > $n['have'] + 0.0001) {
                $short[] = $n['name'] . ' (need ' . rtrim(rtrim(number_format($n['qty'], 3, '.', ''), '0'), '.')
                    . ', have ' . rtrim(rtrim(number_format($n['have'], 3, '.', ''), '0'), '.') . ')';
            }
        }
        return $short === [] ? null : 'Not enough stock: ' . implode('; ', $short) . '.';
    }

    /** Extract a user-friendly message from the service's INSUFFICIENT_STOCK sentinel. */
    private function stockErrorMessage(\Throwable $e): ?string
    {
        if (! str_contains($e->getMessage(), 'INSUFFICIENT_STOCK:')) {
            return null;
        }
        $part = explode('INSUFFICIENT_STOCK:', $e->getMessage(), 2)[1] ?? '';
        [$name, $have] = array_pad(explode(':', $part, 2), 2, '0');
        return 'Not enough stock for ' . trim($name) . ' (available ' . rtrim(rtrim(number_format((float) $have, 3, '.', ''), '0'), '.') . ').';
    }

    // ---- Shapers -----------------------------------------------------------

    private function shapeHeader(array $r): array
    {
        return [
            'id'           => (int) $r['id'],
            'type'         => $r['type'],
            'ref_invoice_id' => isset($r['ref_invoice_id']) && $r['ref_invoice_id'] !== null ? (int) $r['ref_invoice_id'] : null,
            'invoice_no'   => $r['invoice_no'],
            'party_name'   => $r['party_name'],
            'party_type'   => $r['party_type'],
            'invoice_date' => $r['invoice_date'],
            'subtotal'     => (float) $r['subtotal'],
            'tax_total'    => (float) $r['tax_total'],
            'discount'     => (float) $r['discount'],
            'total'        => (float) $r['total'],
            'received'     => isset($r['received']) ? (float) $r['received'] : (float) $r['total'],
            'balance_due'  => round((float) $r['total'] - (float) ($r['received'] ?? $r['total']), 2),
            'payment_mode' => $r['payment_mode'],
            'status'       => $r['status'],
            'txn_id'       => $r['txn_id'] !== null ? (int) $r['txn_id'] : null,
            'pay_txn_id'   => isset($r['pay_txn_id']) && $r['pay_txn_id'] !== null ? (int) $r['pay_txn_id'] : null,
            'notes'        => $r['notes'],
            'created_at'   => $r['created_at'] ?? null,
        ];
    }

    private function shapeItem(array $r): array
    {
        return [
            'id'         => (int) $r['id'],
            'product_id' => $r['product_id'] !== null ? (int) $r['product_id'] : null,
            'name'       => $r['name'],
            'qty'        => (float) $r['qty'],
            'rate'       => (float) $r['rate'],
            'tax_rate'   => (float) $r['tax_rate'],
            'amount'     => (float) $r['amount'],
        ];
    }
}
