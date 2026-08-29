<?php

namespace Modules\Invoices\Controllers;

use App\Controllers\BaseController;
use App\Models\InvoiceModel;
use App\Models\InvoiceItemModel;
use App\Models\ProductModel;
use App\Models\StockMovementModel;
use App\Models\TransactionModel;

/**
 * Sales / Purchase invoices (firm portal). Same integrated logic as the mobile
 * InvoiceApiController: a SALE issues stock (out) + posts a Jama (money-in) cash
 * entry; a PURCHASE receives stock (in) + posts a Naam (money-out) entry — all
 * in one save. Company-scoped via company_id().
 */
class InvoiceController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'company'];

    /** Bill list (both types, newest first). */
    public function index()
    {
        $cid  = (int) company_id();
        $rows = $cid > 0
            ? (new InvoiceModel())->scoped($cid)->orderBy('id', 'DESC')->findAll(300)
            : [];

        return $this->render('index', [
            'title'      => 'Sales & Purchase',
            'breadcrumb' => [['label' => 'Home', 'url' => site_url('dashboard')], ['label' => 'Sales & Purchase']],
            'rows'       => $rows,
        ]);
    }

    /** New sale / purchase bill form. */
    public function create($type = 'sale')
    {
        $type = $type === 'purchase' ? 'purchase' : 'sale';
        $cid  = (int) company_id();
        $products = $cid > 0
            ? (new ProductModel())->scoped($cid)->orderBy('name', 'ASC')->findAll()
            : [];

        return $this->render('form', [
            'title'      => $type === 'sale' ? 'New Sale Bill' : 'New Purchase Bill',
            'breadcrumb' => [
                ['label' => 'Home', 'url' => site_url('dashboard')],
                ['label' => 'Sales & Purchase', 'url' => site_url('invoices')],
                ['label' => $type === 'sale' ? 'New Sale' : 'New Purchase'],
            ],
            'type'     => $type,
            'products' => $products,
        ]);
    }

    /** Persist a bill: adjust stock + post the linked cash entry. */
    public function store()
    {
        $cid = (int) company_id();
        if ($cid <= 0) {
            return redirect()->to(site_url('invoices'))->with('error', 'Select a company first.');
        }
        $req       = $this->request;
        $type      = $req->getPost('type') === 'purchase' ? 'purchase' : 'sale';
        $partyName = trim((string) $req->getPost('party_name'));
        $partyType = trim((string) $req->getPost('party_type'));
        $mode      = (string) ($req->getPost('payment_mode') ?: 'cash');
        $notes     = trim((string) $req->getPost('notes')) ?: null;
        $discount  = round((float) $req->getPost('discount'), 2);
        $dateIn    = (string) $req->getPost('invoice_date');
        $ts        = strtotime($dateIn);
        $invDate   = $ts !== false ? date('Y-m-d', $ts) : date('Y-m-d');
        $rawItems  = $req->getPost('items');

        if (! in_array($mode, TransactionModel::MODES, true)) {
            $mode = 'cash';
        }
        if (! is_array($rawItems) || count($rawItems) === 0) {
            return redirect()->back()->withInput()->with('error', 'Add at least one product line.');
        }

        // ---- Validate + price the line items --------------------------------
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
                    return redirect()->back()->withInput()->with('error', 'A product on this bill was not found.');
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
            // Shape expected by LedgerPostingService (product_id / tax_rate keys).
            $lines[] = [
                'product_id' => $pid,
                'product'    => $product,
                'name'       => $name,
                'qty'        => $qty,
                'rate'       => $rate,
                'tax_rate'   => $tax,
                'amount'     => $lineAmt,
            ];
        }
        if (count($lines) === 0) {
            return redirect()->back()->withInput()->with('error', 'Every line needs a quantity greater than zero.');
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxTotal, 2);
        $total    = max(0, round($subtotal + $taxTotal - $discount, 2));

        // Block overselling BEFORE calling the engine (as the mobile API does):
        // the engine also guards, but it creates/updates the party master before
        // its transaction, so a failure there would leave an orphan party. Summed
        // per product so two lines of the same item are checked together.
        if ($type === 'sale') {
            $wanted = [];
            foreach ($lines as $ln) {
                if ($ln['product'] !== null) {
                    $wanted[$ln['product_id']] = ($wanted[$ln['product_id']] ?? 0) + $ln['qty'];
                }
            }
            foreach ($lines as $ln) {
                if ($ln['product'] !== null && ($wanted[$ln['product_id']] ?? 0) > (float) $ln['product']['current_stock']) {
                    $have = rtrim(rtrim(number_format((float) $ln['product']['current_stock'], 3, '.', ''), '0'), '.');
                    return redirect()->back()->withInput()->with(
                        'error',
                        'Not enough stock for "' . $ln['product']['name'] . '". Available: ' . $have . '.'
                    );
                }
            }
        }

        // Post through the SAME central engine the mobile app uses, so web and
        // mobile bills can never drift: one DB transaction, idempotent on
        // client_uuid, blocks overselling, splits the party-ledger receivable from
        // the cash entry, and stamps each stock movement with the COST basis (so
        // COGS/profit stay correct — the old inline path stamped the sale price).
        // The web form has no "received" field, so a bill is treated as fully paid.
        try {
            $result = (new \App\Services\LedgerPostingService())->postInvoice([
                'company_id'   => $cid,
                'user_id'      => (int) user_id(),
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
                'received'     => $total,
                'client_uuid'  => trim((string) $req->getPost('client_uuid')) ?: null,
                'lines'        => $lines,
            ]);
        } catch (\Throwable $e) {
            if (($msg = $this->stockErrorMessage($e)) !== null) {
                return redirect()->back()->withInput()->with('error', $msg);
            }
            log_message('error', 'Invoice save failed: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Could not save the bill — please try again.');
        }

        $invoice = $result['invoice'];
        $invNo   = (string) $invoice['invoice_no'];
        if (! $result['duplicate'] && function_exists('activity_log')) {
            activity_log('Invoices', 'Add', ucfirst($type) . " bill {$invNo} created");
        }

        return redirect()->to(site_url('invoices/view/' . $invoice['id']))
            ->with('success', ($type === 'sale' ? 'Sale' : 'Purchase') . ' bill ' . $invNo . ' saved.');
    }

    /** Map the posting engine's INSUFFICIENT_STOCK sentinel to a friendly message. */
    private function stockErrorMessage(\Throwable $e): ?string
    {
        if (! str_contains($e->getMessage(), 'INSUFFICIENT_STOCK:')) {
            return null;
        }
        $part = explode('INSUFFICIENT_STOCK:', $e->getMessage(), 2)[1] ?? '';
        [$name, $have] = array_pad(explode(':', $part, 2), 2, '0');
        return 'Not enough stock for "' . trim($name) . '". Available: '
            . rtrim(rtrim(number_format((float) $have, 3, '.', ''), '0'), '.') . '.';
    }

    /** Printable bill. */
    public function show($id = null)
    {
        $cid = (int) company_id();
        $inv = (new InvoiceModel())->scoped($cid)->find((int) $id);
        if (! $inv) {
            return redirect()->to(site_url('invoices'))->with('error', 'Invoice not found.');
        }
        $inv['items'] = (new InvoiceItemModel())->forInvoice((int) $inv['id']);

        return $this->render('print', [
            'title'      => $inv['invoice_no'],
            'breadcrumb' => [
                ['label' => 'Home', 'url' => site_url('dashboard')],
                ['label' => 'Sales & Purchase', 'url' => site_url('invoices')],
                ['label' => $inv['invoice_no']],
            ],
            'inv'     => $inv,
            'company' => current_company(),
        ]);
    }
}
