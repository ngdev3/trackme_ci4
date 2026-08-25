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
        $rawItems  = $this->input('items');

        if (! is_array($rawItems) || count($rawItems) === 0) {
            return $this->failValidationErrors(['items' => 'Add at least one product line.']);
        }
        if (! in_array($mode, TransactionModel::MODES, true)) {
            $mode = 'cash';
        }

        // ---- Validate + normalise the line items against the catalogue -------
        $products = new ProductModel();
        $lines    = [];
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
            $product = null;
            if ($pid) {
                $product = $products->scoped($cid)->find($pid);
                if (! $product) {
                    return $this->failNotFound('A product on this bill was not found.');
                }
                if ($name === '') {
                    $name = $product['name'];
                }
            }
            if ($name === '') {
                $name = 'Item';
            }
            $lineAmt   = round($qty * $rate, 2);
            $lineTax   = round($lineAmt * $tax / 100, 2);
            $subtotal += $lineAmt;
            $taxTotal += $lineTax;
            $lines[] = [
                'product_id' => $pid,
                'product'    => $product,
                'name'       => mb_substr($name, 0, 191),
                'qty'        => $qty,
                'rate'       => $rate,
                'tax_rate'   => $tax,
                'amount'     => $lineAmt,
            ];
        }
        if (count($lines) === 0) {
            return $this->failValidationErrors(['items' => 'Every line needs a quantity greater than zero.']);
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxTotal, 2);
        $total    = round($subtotal + $taxTotal - $discount, 2);
        if ($total < 0) {
            $total = 0.0;
        }

        // ---- Post the linked cash-book transaction ---------------------------
        // Sale  -> Jama  (money received).  Purchase -> Naam (money paid).
        $txnModel = new TransactionModel();
        $txnType  = $type === 'sale' ? 'jama' : 'naam';
        $txnName  = $partyName !== '' ? $partyName : ($type === 'sale' ? 'Cash Sale' : 'Cash Purchase');
        $txnId    = null;
        if ($total > 0) {
            $txnId = $txnModel->insert([
                'user_id'      => (int) $user['id'],
                'company_id'   => $cid,
                'txn_no'       => $txnModel->nextTxnNo($cid),
                'txn_date'     => $invDate,
                'name'         => mb_substr($txnName, 0, 191),
                'party_type'   => $partyType !== '' ? mb_substr($partyType, 0, 32) : null,
                'type'         => $txnType,
                'amount'       => $total,
                'payment_mode' => $mode,
                'status'       => $type === 'sale' ? 'received' : 'paid',
                'notes'        => 'Bill ' . $type,
                'source'       => 'invoice',
            ]);
            $txnId = $txnId ? (int) $txnId : null;
        }

        // ---- Save the invoice header + items --------------------------------
        $invoices = new InvoiceModel();
        $invNo    = $invoices->nextInvoiceNo($cid, $type);
        $invId    = (int) $invoices->insert([
            'company_id'   => $cid,
            'created_by'   => (int) $user['id'],
            'type'         => $type,
            'invoice_no'   => $invNo,
            'party_name'   => $partyName !== '' ? mb_substr($partyName, 0, 191) : null,
            'party_type'   => $partyType !== '' ? mb_substr($partyType, 0, 32) : null,
            'invoice_date' => $invDate,
            'subtotal'     => $subtotal,
            'tax_total'    => $taxTotal,
            'discount'     => $discount,
            'total'        => $total,
            'payment_mode' => $mode,
            'status'       => 'paid',
            'txn_id'       => $txnId,
            'notes'        => $notes,
        ]);

        $itemModel = new InvoiceItemModel();
        $moves     = new StockMovementModel();
        foreach ($lines as $ln) {
            $itemModel->insert([
                'invoice_id' => $invId,
                'product_id' => $ln['product_id'],
                'name'       => $ln['name'],
                'qty'        => $ln['qty'],
                'rate'       => $ln['rate'],
                'tax_rate'   => $ln['tax_rate'],
                'amount'     => $ln['amount'],
            ]);
            // ---- Adjust stock for catalogued lines --------------------------
            if ($ln['product'] !== null) {
                $moveType = $type === 'sale' ? 'out' : 'in';
                $current  = (float) $ln['product']['current_stock'];
                $newStock = $moveType === 'in' ? $current + $ln['qty'] : $current - $ln['qty'];
                $moves->insert([
                    'company_id' => $cid,
                    'product_id' => $ln['product_id'],
                    'type'       => $moveType,
                    'qty'        => $ln['qty'],
                    'rate'       => $ln['rate'],
                    'note'       => $invNo,
                    'created_by' => (int) $user['id'],
                ]);
                $products->update($ln['product_id'], ['current_stock' => round($newStock, 3)]);
            }
        }

        if (function_exists('activity_log')) {
            activity_log('Invoices', 'Add', ucfirst($type) . " bill {$invNo} created (mobile)");
        }

        $saved = $invoices->find($invId);
        $items = $itemModel->forInvoice($invId);
        return $this->respondCreated([
            'status'  => 'ok',
            'message' => ($type === 'sale' ? 'Sale' : 'Purchase') . ' bill ' . $invNo . ' saved.',
            'invoice' => $this->shapeHeader($saved) + ['items' => array_map([$this, 'shapeItem'], $items)],
        ]);
    }

    // ---- Shapers -----------------------------------------------------------

    private function shapeHeader(array $r): array
    {
        return [
            'id'           => (int) $r['id'],
            'type'         => $r['type'],
            'invoice_no'   => $r['invoice_no'],
            'party_name'   => $r['party_name'],
            'party_type'   => $r['party_type'],
            'invoice_date' => $r['invoice_date'],
            'subtotal'     => (float) $r['subtotal'],
            'tax_total'    => (float) $r['tax_total'],
            'discount'     => (float) $r['discount'],
            'total'        => (float) $r['total'],
            'payment_mode' => $r['payment_mode'],
            'status'       => $r['status'],
            'txn_id'       => $r['txn_id'] !== null ? (int) $r['txn_id'] : null,
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
