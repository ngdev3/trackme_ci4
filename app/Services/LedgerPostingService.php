<?php

namespace App\Services;

use App\Models\InvoiceItemModel;
use App\Models\InvoiceModel;
use App\Models\PartyModel;
use App\Models\ProductModel;
use App\Models\StockMovementModel;
use App\Models\TransactionModel;
use Config\Database;

/**
 * Central posting engine — the ONE place that turns a business document into its
 * ledger + cash + stock effects, so web and mobile can never drift (audit §5).
 *
 * Accounting model (bahi-khata; receivable = Naam − Jama):
 *
 *   SALE of total T, of which R is received now
 *     • party ledger : NAAM  T   (ledger_only) → receivable +T   (no cash)
 *     • cash book     : JAMA  R                 → cash +R, receivable −R
 *     • stock         : OUT qty per line
 *     ⇒ party owes (T − R); cash rises by R only. A credit sale (R=0) posts no
 *       cash at all, so it can never inflate cash-in-hand.
 *
 *   PURCHASE of total T, of which P is paid now  (mirror image)
 *     • party ledger : JAMA  T   (ledger_only) → payable +T
 *     • cash book     : NAAM  P                 → cash −P, payable −P
 *     • stock         : IN qty per line
 *
 * Everything runs inside ONE database transaction (audit C3) and is idempotent
 * on (company_id, client_uuid) (audit C4), so a double-tap / retry can't double
 * post the bill, the stock move, or the cash entry.
 */
class LedgerPostingService
{
    /**
     * Post a sale/purchase invoice with all its side effects.
     *
     * @param array $ctx {
     *   company_id:int, user_id:int, type:'sale'|'purchase', party_name:string,
     *   party_type:?string, payment_mode:string, invoice_date:string(Y-m-d),
     *   notes:?string, discount:float, subtotal:float, tax_total:float,
     *   total:float, received:float, client_uuid:?string,
     *   lines: array<int,{product_id:?int, product:?array, name:string, qty:float,
     *                      rate:float, tax_rate:float, amount:float}>
     * }
     * @return array{invoice:array, items:array, duplicate:bool}
     *
     * @throws \RuntimeException on an accounting/stock failure (transaction rolled back)
     */
    public function postInvoice(array $ctx): array
    {
        $cid   = (int) $ctx['company_id'];
        $uid   = (int) $ctx['user_id'];
        $type  = $ctx['type'] === 'purchase' ? 'purchase' : 'sale';
        $uuid  = trim((string) ($ctx['client_uuid'] ?? '')) ?: null;

        $invoices = new InvoiceModel();
        $items    = new InvoiceItemModel();

        // --- C4: idempotency — a retried/double-tapped bill returns the first one.
        if ($uuid !== null) {
            $existing = $invoices->where('company_id', $cid)->where('client_uuid', $uuid)->first();
            if ($existing) {
                return [
                    'invoice'   => $existing,
                    'items'     => $items->forInvoice((int) $existing['id']),
                    'duplicate' => true,
                ];
            }
        }

        $total    = round((float) $ctx['total'], 2);
        $received = max(0.0, min(round((float) ($ctx['received'] ?? 0), 2), $total)); // clamp 0..total
        $party    = trim((string) ($ctx['party_name'] ?? ''));
        $partyTyp = trim((string) ($ctx['party_type'] ?? '')) ?: null;
        $mode     = in_array($ctx['payment_mode'] ?? 'cash', TransactionModel::MODES, true) ? $ctx['payment_mode'] : 'cash';
        $date     = $ctx['invoice_date'];
        $partyId  = $this->resolvePartyId($cid, $party);

        $db  = Database::connect();
        $txn = new TransactionModel();
        $inv = null;

        $db->transStart();
        try {
            // 1) Receivable / payable ledger entry (never touches cash).
            $ledgerType = $type === 'sale' ? 'naam' : 'jama'; // sale → receivable(+), purchase → payable(+)
            $ledgerTxnId = null;
            if ($total > 0 && $party !== '') {
                $ledgerTxnId = (int) $txn->insert([
                    'user_id'      => $uid,
                    'company_id'   => $cid,
                    'txn_no'       => $txn->nextTxnNo($cid),
                    'txn_date'     => $date,
                    'name'         => mb_substr($party, 0, 191),
                    'party_id'     => $partyId,
                    'party_type'   => $partyTyp,
                    'type'         => $ledgerType,
                    'amount'       => $total,
                    'payment_mode' => $mode,
                    'status'       => 'pending', // an unsettled receivable/payable
                    'ledger_only'  => 1,
                    'notes'        => ucfirst($type) . ' bill',
                    'source'       => 'invoice',
                ]);
            }

            // 2) Cash book entry for money actually moving now.
            $payTxnId = null;
            if ($received > 0) {
                $cashType = $type === 'sale' ? 'jama' : 'naam'; // sale receipt → cash in; purchase payment → cash out
                $payTxnId = (int) $txn->insert([
                    'user_id'      => $uid,
                    'company_id'   => $cid,
                    'txn_no'       => $txn->nextTxnNo($cid),
                    'txn_date'     => $date,
                    'name'         => $party !== '' ? mb_substr($party, 0, 191) : ($type === 'sale' ? 'Cash Sale' : 'Cash Purchase'),
                    'party_id'     => $partyId,
                    'party_type'   => $partyTyp,
                    'type'         => $cashType,
                    'amount'       => $received,
                    'payment_mode' => $mode,
                    'status'       => $type === 'sale' ? 'received' : 'paid',
                    'ledger_only'  => 0,
                    'notes'        => ($type === 'sale' ? 'Sale receipt' : 'Purchase payment'),
                    'source'       => 'invoice',
                ]);
            }

            // 3) Invoice header.
            $status = $received >= $total ? 'paid' : ($received > 0 ? 'partial' : 'unpaid');
            $invId  = (int) $invoices->insert([
                'company_id'   => $cid,
                'client_uuid'  => $uuid,
                'created_by'   => $uid,
                'type'         => $type,
                'invoice_no'   => $invoices->nextInvoiceNo($cid, $type),
                'party_name'   => $party !== '' ? mb_substr($party, 0, 191) : null,
                'party_type'   => $partyTyp,
                'party_id'     => $partyId,
                'invoice_date' => $date,
                'subtotal'     => round((float) $ctx['subtotal'], 2),
                'tax_total'    => round((float) $ctx['tax_total'], 2),
                'discount'     => round((float) $ctx['discount'], 2),
                'total'        => $total,
                'received'     => $received,
                'payment_mode' => $mode,
                'status'       => $status,
                'txn_id'       => $ledgerTxnId,
                'pay_txn_id'   => $payTxnId,
                'notes'        => $ctx['notes'] ?? null,
            ]);

            // 4) Items + stock movements (movement ledger is the source of truth).
            $products = new ProductModel();
            $moves    = new StockMovementModel();
            $invNo    = $invoices->find($invId)['invoice_no'] ?? '';
            foreach ($ctx['lines'] as $ln) {
                $items->insert([
                    'invoice_id' => $invId,
                    'product_id' => $ln['product_id'],
                    'name'       => $ln['name'],
                    'qty'        => $ln['qty'],
                    'rate'       => $ln['rate'],
                    'tax_rate'   => $ln['tax_rate'],
                    'amount'     => $ln['amount'],
                ]);
                if (($ln['product'] ?? null) === null) {
                    continue; // free-text line: billed but not stock-tracked
                }
                $moveType = $type === 'sale' ? 'out' : 'in';
                $current  = (float) $ln['product']['current_stock'];
                $newStock = $moveType === 'in' ? $current + $ln['qty'] : $current - $ln['qty'];
                $moves->insert([
                    'company_id' => $cid,
                    'product_id' => $ln['product_id'],
                    'type'       => $moveType,
                    'qty'        => $ln['qty'],
                    // Cost basis: a purchase records its purchase rate; a sale records
                    // the item's current purchase_price so COGS is recoverable later.
                    'rate'       => $type === 'purchase' ? $ln['rate'] : (float) $ln['product']['purchase_price'],
                    'note'       => $invNo,
                    'created_by' => $uid,
                ]);
                // Only the stock counter changes here — skip the full-row
                // (name-required) validation so the update isn't rejected.
                $products->skipValidation(true)->update($ln['product_id'], ['current_stock' => round($newStock, 3)]);
            }

            $inv = $invoices->find($invId);
        } catch (\Throwable $e) {
            $db->transRollback();
            // C4 race: a concurrent identical request won the unique (company,uuid)
            // index a moment ago — return that committed bill instead of erroring.
            if ($uuid !== null) {
                $won = $invoices->where('company_id', $cid)->where('client_uuid', $uuid)->first();
                if ($won) {
                    return ['invoice' => $won, 'items' => $items->forInvoice((int) $won['id']), 'duplicate' => true];
                }
            }
            throw new \RuntimeException('Posting failed: ' . $e->getMessage(), 0, $e);
        }

        $db->transComplete();
        if ($db->transStatus() === false || $inv === null) {
            $err = $db->error();
            log_message('error', '[LedgerPosting] invoice post failed: ' . ($err['message'] ?? 'unknown'));
            throw new \RuntimeException('Could not save the bill. Please try again.');
        }

        if (function_exists('dash_bust')) {
            dash_bust($cid);
        }

        return [
            'invoice'   => $inv,
            'items'     => $items->forInvoice((int) $inv['id']),
            'duplicate' => false,
        ];
    }

    /**
     * Post a sale/purchase RETURN. Mirror of a bill:
     *   SALE_RETURN total T, refunded R now
     *     • party ledger : JAMA  T (ledger_only)  → receivable −T
     *     • cash book     : NAAM  R                → cash −R (money refunded)
     *     • stock         : IN qty  (goods come back)
     *   PURCHASE_RETURN — opposite (ledger NAAM = payable−, cash JAMA in, stock OUT).
     *
     * Never posts a "payment" for the return itself; only an explicit refund
     * moves cash. One DB transaction, idempotent on (company, client_uuid).
     *
     * @param array $ctx same shape as postInvoice, with type 'sale_return'|
     *                   'purchase_return', an optional 'ref_invoice_id', and
     *                   'received' reused as the refund amount.
     * @return array{invoice:array, items:array, duplicate:bool}
     */
    public function postReturn(array $ctx): array
    {
        $cid    = (int) $ctx['company_id'];
        $uid    = (int) $ctx['user_id'];
        $isSale = ($ctx['type'] ?? 'sale_return') !== 'purchase_return';
        $type   = $isSale ? 'sale_return' : 'purchase_return';
        $uuid   = trim((string) ($ctx['client_uuid'] ?? '')) ?: null;

        $invoices = new InvoiceModel();
        $items    = new InvoiceItemModel();

        if ($uuid !== null) {
            $existing = $invoices->where('company_id', $cid)->where('client_uuid', $uuid)->first();
            if ($existing) {
                return ['invoice' => $existing, 'items' => $items->forInvoice((int) $existing['id']), 'duplicate' => true];
            }
        }

        $total  = round((float) $ctx['total'], 2);
        $refund = max(0.0, min(round((float) ($ctx['received'] ?? 0), 2), $total));
        $party  = trim((string) ($ctx['party_name'] ?? ''));
        $partyTyp = trim((string) ($ctx['party_type'] ?? '')) ?: null;
        $mode   = in_array($ctx['payment_mode'] ?? 'cash', TransactionModel::MODES, true) ? $ctx['payment_mode'] : 'cash';
        $date   = $ctx['invoice_date'];
        $partyId = $this->resolvePartyId($cid, $party);
        $refId  = isset($ctx['ref_invoice_id']) ? ((int) $ctx['ref_invoice_id'] ?: null) : null;

        $db  = Database::connect();
        $txn = new TransactionModel();
        $inv = null;

        $db->transStart();
        try {
            // Ledger reversal of the party balance.
            $ledgerType = $isSale ? 'jama' : 'naam'; // sale_return → receivable−, purchase_return → payable−
            $ledgerTxnId = null;
            if ($total > 0 && $party !== '') {
                $ledgerTxnId = (int) $txn->insert([
                    'user_id' => $uid, 'company_id' => $cid, 'txn_no' => $txn->nextTxnNo($cid),
                    'txn_date' => $date, 'name' => mb_substr($party, 0, 191), 'party_id' => $partyId,
                    'party_type' => $partyTyp, 'type' => $ledgerType, 'amount' => $total, 'payment_mode' => $mode,
                    'status' => 'pending', 'ledger_only' => 1, 'notes' => ucfirst(str_replace('_', ' ', $type)), 'source' => 'invoice',
                ]);
            }
            // Optional cash refund.
            $payTxnId = null;
            if ($refund > 0) {
                $cashType = $isSale ? 'naam' : 'jama'; // refund to customer = cash out; from supplier = cash in
                $payTxnId = (int) $txn->insert([
                    'user_id' => $uid, 'company_id' => $cid, 'txn_no' => $txn->nextTxnNo($cid),
                    'txn_date' => $date, 'name' => $party !== '' ? mb_substr($party, 0, 191) : 'Return',
                    'party_id' => $partyId, 'party_type' => $partyTyp, 'type' => $cashType, 'amount' => $refund,
                    'payment_mode' => $mode, 'status' => $isSale ? 'paid' : 'received', 'ledger_only' => 0,
                    'notes' => 'Return refund', 'source' => 'invoice',
                ]);
            }

            $status = $refund >= $total ? 'refunded' : ($refund > 0 ? 'partial' : 'credited');
            $invId  = (int) $invoices->insert([
                'company_id' => $cid, 'client_uuid' => $uuid, 'created_by' => $uid, 'type' => $type,
                'ref_invoice_id' => $refId, 'invoice_no' => $invoices->nextInvoiceNo($cid, $type),
                'party_name' => $party !== '' ? mb_substr($party, 0, 191) : null, 'party_type' => $partyTyp, 'party_id' => $partyId,
                'invoice_date' => $date, 'subtotal' => round((float) $ctx['subtotal'], 2), 'tax_total' => round((float) $ctx['tax_total'], 2),
                'discount' => round((float) $ctx['discount'], 2), 'total' => $total, 'received' => $refund,
                'payment_mode' => $mode, 'status' => $status, 'txn_id' => $ledgerTxnId, 'pay_txn_id' => $payTxnId, 'notes' => $ctx['notes'] ?? null,
            ]);

            $products = new ProductModel();
            $moves    = new StockMovementModel();
            $invNo    = $invoices->find($invId)['invoice_no'] ?? '';
            foreach ($ctx['lines'] as $ln) {
                $items->insert([
                    'invoice_id' => $invId, 'product_id' => $ln['product_id'], 'name' => $ln['name'],
                    'qty' => $ln['qty'], 'rate' => $ln['rate'], 'tax_rate' => $ln['tax_rate'], 'amount' => $ln['amount'],
                ]);
                if (($ln['product'] ?? null) === null) {
                    continue;
                }
                $moveType = $isSale ? 'in' : 'out'; // sale_return brings goods back in; purchase_return sends them out
                $current  = (float) $ln['product']['current_stock'];
                $newStock = $moveType === 'in' ? $current + $ln['qty'] : $current - $ln['qty'];
                $moves->insert([
                    'company_id' => $cid, 'product_id' => $ln['product_id'], 'type' => $moveType, 'qty' => $ln['qty'],
                    'rate' => (float) $ln['product']['purchase_price'], 'note' => $invNo, 'created_by' => $uid,
                ]);
                $products->skipValidation(true)->update($ln['product_id'], ['current_stock' => round($newStock, 3)]);
            }

            $inv = $invoices->find($invId);
        } catch (\Throwable $e) {
            $db->transRollback();
            if ($uuid !== null) {
                $won = $invoices->where('company_id', $cid)->where('client_uuid', $uuid)->first();
                if ($won) {
                    return ['invoice' => $won, 'items' => $items->forInvoice((int) $won['id']), 'duplicate' => true];
                }
            }
            throw new \RuntimeException('Return posting failed: ' . $e->getMessage(), 0, $e);
        }

        $db->transComplete();
        if ($db->transStatus() === false || $inv === null) {
            log_message('error', '[LedgerPosting] return post failed: ' . ($db->error()['message'] ?? 'unknown'));
            throw new \RuntimeException('Could not save the return. Please try again.');
        }
        if (function_exists('dash_bust')) {
            dash_bust($cid);
        }
        return ['invoice' => $inv, 'items' => $items->forInvoice((int) $inv['id']), 'duplicate' => false];
    }

    /**
     * Void a bill/return: reverse its stock, and soft-delete its ledger + cash
     * entries and the document itself — all in one DB transaction, so the books
     * return exactly to their pre-bill state (audit C5/§27).
     *
     * @throws \RuntimeException if not found, already void, or referenced by a return
     */
    public function voidInvoice(int $invoiceId, int $cid, int $uid, string $reason = ''): array
    {
        $invoices = new InvoiceModel();
        $inv = $invoices->scoped($cid)->find($invoiceId);
        if (! $inv) {
            throw new \RuntimeException('Bill not found.');
        }
        // Block voiding an original that still has (non-deleted) returns against it.
        $openReturns = $invoices->where('company_id', $cid)->where('ref_invoice_id', $invoiceId)->countAllResults();
        if ($openReturns > 0) {
            throw new \RuntimeException('Cancel the linked return(s) first.');
        }

        // Original stock direction per document type → we reverse it.
        $origOut = in_array($inv['type'], ['sale', 'purchase_return'], true); // these reduced stock
        $items   = (new InvoiceItemModel())->forInvoice($invoiceId);

        $db  = Database::connect();
        $db->transStart();
        try {
            $products = new ProductModel();
            $moves    = new StockMovementModel();
            foreach ($items as $it) {
                if ($it['product_id'] === null) {
                    continue;
                }
                $p = $products->find((int) $it['product_id']);
                if (! $p) {
                    continue;
                }
                $revType  = $origOut ? 'in' : 'out';               // reverse of the original move
                $current  = (float) $p['current_stock'];
                $newStock = $revType === 'in' ? $current + (float) $it['qty'] : $current - (float) $it['qty'];
                $moves->insert([
                    'company_id' => $cid, 'product_id' => (int) $it['product_id'], 'type' => $revType,
                    'qty' => (float) $it['qty'], 'rate' => (float) $p['purchase_price'],
                    'note' => 'VOID ' . $inv['invoice_no'], 'created_by' => $uid,
                ]);
                $products->skipValidation(true)->update((int) $it['product_id'], ['current_stock' => round($newStock, 3)]);
            }

            // Soft-delete the linked ledger + cash entries (removes them from every
            // aggregation) with an audit reason.
            $txn = new TransactionModel();
            foreach (['txn_id', 'pay_txn_id'] as $k) {
                if (! empty($inv[$k])) {
                    $txn->update((int) $inv[$k], ['delete_reason' => 'VOID ' . $inv['invoice_no'] . ($reason ? ' — ' . $reason : '')]);
                    $txn->delete((int) $inv[$k]);
                }
            }

            $invoices->update($invoiceId, ['status' => 'void', 'notes' => trim(($inv['notes'] ?? '') . ' [VOID ' . $reason . ']')]);
            $invoices->delete($invoiceId); // soft delete
        } catch (\Throwable $e) {
            $db->transRollback();
            throw new \RuntimeException('Void failed: ' . $e->getMessage(), 0, $e);
        }
        $db->transComplete();
        if ($db->transStatus() === false) {
            log_message('error', '[LedgerPosting] void failed: ' . ($db->error()['message'] ?? 'unknown'));
            throw new \RuntimeException('Could not void the bill. Please try again.');
        }
        if (function_exists('dash_bust')) {
            dash_bust($cid);
        }
        return ['ok' => true, 'invoice_no' => $inv['invoice_no']];
    }

    /** Best-effort party-master id for the name (nullable; name stays the join key). */
    private function resolvePartyId(int $cid, string $name): ?int
    {
        if ($name === '') {
            return null;
        }
        $p = (new PartyModel())->forName($cid, $name);
        return $p ? (int) $p['id'] : null;
    }
}
