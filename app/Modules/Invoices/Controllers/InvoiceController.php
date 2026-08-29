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
            $lines[] = compact('pid', 'product', 'name', 'qty', 'rate', 'tax') + ['amount' => $lineAmt];
        }
        if (count($lines) === 0) {
            return redirect()->back()->withInput()->with('error', 'Every line needs a quantity greater than zero.');
        }

        // Block overselling before we touch the books — same rule the manual
        // Stock Out and the mobile bill already enforce, so a sale can't drive a
        // tracked product's stock negative. Quantities are summed per product so
        // two lines of the same item are checked together.
        if ($type === 'sale') {
            $wanted = [];
            foreach ($lines as $ln) {
                if ($ln['product'] !== null) {
                    $wanted[$ln['pid']] = ($wanted[$ln['pid']] ?? 0) + $ln['qty'];
                }
            }
            foreach ($lines as $ln) {
                if ($ln['product'] !== null && ($wanted[$ln['pid']] ?? 0) > (float) $ln['product']['current_stock']) {
                    $have = rtrim(rtrim(number_format((float) $ln['product']['current_stock'], 3, '.', ''), '0'), '.');
                    return redirect()->back()->withInput()->with(
                        'error',
                        'Not enough stock for "' . $ln['product']['name'] . '". Available: ' . $have . '.'
                    );
                }
            }
        }

        $subtotal = round($subtotal, 2);
        $taxTotal = round($taxTotal, 2);
        $total    = max(0, round($subtotal + $taxTotal - $discount, 2));

        // A bill spans several writes — a cash-book entry, the header, its item
        // rows, and a stock movement + level update per product. Wrap them in one
        // DB transaction so a mid-way failure rolls the whole bill back instead of
        // leaving half of it saved (an orphan header, a cash entry with no bill,
        // or stock decremented for only some lines). All models share the default
        // connection, so this transaction spans every insert/update below.
        $invoices = new InvoiceModel();
        $invNo    = $invoices->nextInvoiceNo($cid, $type);
        $invId    = 0;

        $db = \Config\Database::connect();
        $db->transBegin();
        try {
            // ---- Linked cash-book entry (sale=Jama, purchase=Naam) ----------
            $txnModel = new TransactionModel();
            $txnId    = null;
            if ($total > 0) {
                $txnName = $partyName !== '' ? $partyName : ($type === 'sale' ? 'Cash Sale' : 'Cash Purchase');
                $txnId   = $txnModel->insert([
                    'user_id'      => (int) user_id(),
                    'company_id'   => $cid,
                    'txn_no'       => $txnModel->nextTxnNo($cid),
                    'txn_date'     => $invDate,
                    'name'         => mb_substr($txnName, 0, 191),
                    'party_type'   => $partyType !== '' ? mb_substr($partyType, 0, 32) : null,
                    'type'         => $type === 'sale' ? 'jama' : 'naam',
                    'amount'       => $total,
                    'payment_mode' => $mode,
                    'status'       => $type === 'sale' ? 'received' : 'paid',
                    'notes'        => 'Bill ' . $type,
                    'source'       => 'invoice',
                ]);
                $txnId = $txnId ? (int) $txnId : null;
            }

            // ---- Header + items + stock -------------------------------------
            $invId = (int) $invoices->insert([
                'company_id'   => $cid,
                'created_by'   => (int) user_id(),
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
                    'product_id' => $ln['pid'],
                    'name'       => $ln['name'],
                    'qty'        => $ln['qty'],
                    'rate'       => $ln['rate'],
                    'tax_rate'   => $ln['tax'],
                    'amount'     => $ln['amount'],
                ]);
                if ($ln['product'] !== null) {
                    $moveType = $type === 'sale' ? 'out' : 'in';
                    $current  = (float) $ln['product']['current_stock'];
                    $newStock = $moveType === 'in' ? $current + $ln['qty'] : $current - $ln['qty'];
                    $moves->insert([
                        'company_id' => $cid,
                        'product_id' => $ln['pid'],
                        'type'       => $moveType,
                        'qty'        => $ln['qty'],
                        'rate'       => $ln['rate'],
                        'note'       => $invNo,
                        'created_by' => (int) user_id(),
                    ]);
                    $products->update($ln['pid'], ['current_stock' => round($newStock, 3)]);
                }
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                return redirect()->back()->withInput()
                    ->with('error', 'Could not save the bill — please try again.');
            }
            $db->transCommit();
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'Invoice save failed: ' . $e->getMessage());
            return redirect()->back()->withInput()
                ->with('error', 'Could not save the bill — please try again.');
        }

        if (function_exists('activity_log')) {
            activity_log('Invoices', 'Add', ucfirst($type) . " bill {$invNo} created");
        }

        return redirect()->to(site_url('invoices/view/' . $invId))
            ->with('success', ($type === 'sale' ? 'Sale' : 'Purchase') . ' bill ' . $invNo . ' saved.');
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
