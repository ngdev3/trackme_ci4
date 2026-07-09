<?php

namespace Modules\Inventory\Controllers;

use App\Controllers\BaseController;
use App\Libraries\InventoryReport;
use App\Libraries\InventoryService;
use App\Models\InvMovementModel;
use App\Models\InvPartyModel;
use App\Models\InvProductModel;
use App\Models\InvWarehouseModel;
use App\Models\TransactionModel;

/**
 * Simple Inventory — the whole module in three easy screens:
 *   1. Products  — add products in seconds (name only).
 *   2. Daily     — record stock IN (Purchase) / OUT (Sale) with the party in one
 *                  field. Godowns are hidden behind a single default store.
 *   3. Position  — stock position by day / month / year.
 * All entries still flow through InventoryService, so the ledger and running
 * balance stay correct.
 */
class StockController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company', 'format'];

    protected string $moduleCode = 'inventory';

    protected InvProductModel $products;
    protected InvPartyModel $parties;

    public function __construct()
    {
        $this->products = new InvProductModel();
        $this->parties  = new InvPartyModel();
    }

    private function cid(): int
    {
        return (int) company_id();
    }

    private function uid(): int
    {
        return (int) user_id();
    }

    /** The single hidden store every entry uses. Created on first need. */
    private function storeId(int $cid): int
    {
        $w     = new InvWarehouseModel();
        $first = $w->where('company_id', $cid)->where('status', 1)->where('deleted_at', null)->orderBy('id', 'ASC')->first();
        if ($first) {
            return (int) $first['id'];
        }
        return (int) $w->insert(['company_id' => $cid, 'name' => 'Main Store', 'status' => 1]);
    }

    private function cleanDate(string $d): ?string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) ? $d : null;
    }

    /**
     * Suggested account names — the Inventory parties and the Jama/Naam ledger
     * accounts merged and de-duplicated, so a purchase/sale reuses the same
     * account the money side already knows. The two stay in step.
     *
     * @return list<string>
     */
    private function accountNames(int $cid): array
    {
        $names = [];
        foreach ($this->parties->forCompany($cid) as $p) {
            $names[mb_strtolower(trim($p['name']))] = $p['name'];
        }
        foreach ((new TransactionModel())->partyDirectory($cid) as $a) {
            $key = mb_strtolower(trim($a['name']));
            if ($key !== '' && ! isset($names[$key])) {
                $names[$key] = $a['name'];
            }
        }
        $out = array_values($names);
        sort($out, SORT_NATURAL | SORT_FLAG_CASE);
        return $out;
    }

    /** Map the form's payment choice to a Jama/Naam payment_mode + status. */
    private function paymentMeta(string $choice): array
    {
        return [
            'cash'   => ['cash', 'paid'],
            'upi'    => ['upi', 'paid'],
            'bank'   => ['bank', 'paid'],
            'cheque' => ['cheque', 'paid'],
            'credit' => ['other', 'pending'],
        ][$choice] ?? ['cash', 'paid'];
    }

    // =================================================================
    // 2. Daily IN / OUT — the home screen
    // =================================================================
    public function index()
    {
        $cid  = $this->cid();
        $date = $this->cleanDate((string) $this->request->getGet('date')) ?: date('Y-m-d');

        // The day's entries (both directions) with product + party names.
        $mv  = new InvMovementModel();
        $rows = $mv->select('inv_movements.*, p.name AS product_name, pt.name AS party_name')
            ->join('inv_products p', 'p.id = inv_movements.product_id', 'left')
            ->join('inv_parties pt', 'pt.id = inv_movements.party_id', 'left')
            ->where('inv_movements.company_id', $cid)
            ->where('inv_movements.deleted_at', null)
            ->where('DATE(inv_movements.created_at)', $date)
            ->whereIn('inv_movements.movement_type', ['inward', 'outward'])
            ->orderBy('inv_movements.id', 'DESC')->findAll();

        $tot = ['in_qty' => 0.0, 'in_amt' => 0.0, 'out_qty' => 0.0, 'out_amt' => 0.0];
        foreach ($rows as $r) {
            if ($r['movement_type'] === 'inward') {
                $tot['in_qty'] += (float) $r['bags'];
                $tot['in_amt'] += (float) $r['amount'];
            } else {
                $tot['out_qty'] += (float) $r['bags'];
                $tot['out_amt'] += (float) $r['amount'];
            }
        }

        return $this->render('stock_home', [
            'title'      => 'Daily Stock',
            'breadcrumb' => [['label' => 'Inventory']],
            'date'       => $date,
            'isToday'    => $date === date('Y-m-d'),
            'products'   => $this->products->forCompany($cid),
            'stockMap'   => (new \App\Models\InvStockModel())->bagsByProduct($cid),
            'accounts'   => $this->accountNames($cid),
            'rows'       => $rows,
            'tot'        => $tot,
            'errors'     => session()->getFlashdata('errors') ?? [],
            'old'        => session()->getFlashdata('old') ?? [],
            'hasProducts'=> ! empty($this->products->forCompany($cid)),
            'canAdd'     => can('inventory', 'add'),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    /** Record one IN (Purchase) or OUT (Sale) entry. */
    public function save()
    {
        $cid       = $this->cid();
        $date      = $this->cleanDate((string) $this->request->getPost('date')) ?: date('Y-m-d');
        $type      = $this->request->getPost('type') === 'out' ? 'out' : 'in';
        $productId = (int) $this->request->getPost('product_id');
        $qty       = (float) $this->request->getPost('qty');
        $rate      = $this->request->getPost('rate') !== '' ? (float) $this->request->getPost('rate') : 0.0;
        $party     = trim((string) $this->request->getPost('party'));
        $note      = trim((string) $this->request->getPost('note'));

        $back = static fn (array $errors, array $old) => redirect()->to(site_url('inventory?date=' . $date))
            ->with('errors', $errors)->with('old', $old + ['type' => $type]);

        $errors = [];
        if (! $this->products->findForCompany($productId, $cid)) {
            $errors['product_id'] = 'Please choose a product.';
        }
        if ($qty <= 0) {
            $errors['qty'] = 'Enter a quantity greater than zero.';
        }
        if ($rate < 0) {
            $errors['rate'] = 'Rate cannot be negative.';
        }
        if ($date > date('Y-m-d')) {
            $errors['date'] = 'You cannot record a future date.';
        }
        if ($errors !== []) {
            return $back($errors, $this->request->getPost());
        }

        $amount    = round($rate * $qty, 2);
        $partyId   = $party !== '' ? ($this->parties->findOrCreate($cid, $party, 'both') ?: null) : null;
        $createdAt = $date === date('Y-m-d') ? date('Y-m-d H:i:s') : $date . ' ' . date('H:i:s');
        $store     = $this->storeId($cid);
        $pname     = ($this->products->find($productId)['name'] ?? 'stock');

        $svc = new InventoryService();
        try {
            if ($type === 'in') {
                $svc->recordInward([
                    'company_id' => $cid, 'product_id' => $productId, 'warehouse_id' => $store,
                    'party_id' => $partyId, 'bags' => $qty, 'rate' => $rate, 'amount' => $amount,
                    'notes' => $note ?: null, 'source' => 'web', 'created_by' => $this->uid(), 'created_at' => $createdAt,
                ]);
            } else {
                $svc->recordOutward([
                    'company_id' => $cid, 'product_id' => $productId, 'warehouse_id' => $store,
                    'party_id' => $partyId, 'bags' => $qty, 'rate' => $rate, 'amount' => $amount,
                    'notes' => $note ?: null, 'source' => 'web', 'created_by' => $this->uid(), 'created_at' => $createdAt,
                ]);
            }
        } catch (\RuntimeException $e) {
            $avail = (float) $e->getMessage();
            return $back(['qty' => 'Only ' . number_format($avail, 0) . ' in stock. Sale would go negative.'], $this->request->getPost());
        }

        // Post the money side to the Jama/Naam ledger — Purchase = Naam (paid),
        // Sale = Jama (received) — using the same account name. Needs a party + amount.
        $ledger = false;
        if ($amount > 0 && $party !== '') {
            [$mode, $status] = $this->paymentMeta((string) $this->request->getPost('payment'));
            $tm  = new TransactionModel();
            $tm->insert([
                'user_id'      => $this->uid(),
                'company_id'   => $cid,
                'txn_no'       => $tm->nextTxnNo($cid),
                'txn_date'     => $date,
                'name'         => $party,
                'type'         => $type === 'in' ? 'naam' : 'jama',
                'amount'       => $amount,
                'payment_mode' => $mode,
                'status'       => $status,
                'source'       => 'inventory',
                'notes'        => ($type === 'in' ? 'Purchase' : 'Sale') . ": {$qty} {$pname}" . ($note !== '' ? " — {$note}" : ''),
            ]);
            $ledger = true;
        }

        $label = $type === 'in' ? 'Purchase (IN)' : 'Sale (OUT)';
        $msg   = "{$label} saved." . ($ledger ? ' Jama/Naam ledger updated for ' . esc($party) . '.' : '');
        return redirect()->to(site_url('inventory?date=' . $date))->with('success', $msg);
    }

    // =================================================================
    // 1. Products master
    // =================================================================
    public function products()
    {
        $cid = $this->cid();
        return $this->render('stock_products', [
            'title'      => 'Products',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Products']],
            'products'   => $this->products->forCompany($cid),
            'errors'     => session()->getFlashdata('errors') ?? [],
            'old'        => session()->getFlashdata('old') ?? [],
            'canDelete'  => can('inventory', 'delete'),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    public function storeProduct()
    {
        $cid  = $this->cid();
        $name = trim((string) $this->request->getPost('name'));
        $unit = trim((string) $this->request->getPost('unit')) ?: 'quintal';
        $rate = $this->request->getPost('rate') !== '' ? round((float) $this->request->getPost('rate'), 2) : 0.0;

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Product name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Name is too long.';
        } else {
            $dup = $this->products->where('company_id', $cid)->where('deleted_at', null)
                ->where('LOWER(name)', mb_strtolower($name))->countAllResults() > 0;
            if ($dup) {
                $errors['name'] = 'This product already exists.';
            }
        }
        if ($rate < 0) {
            $errors['rate'] = 'Rate cannot be negative.';
        }
        if ($errors !== []) {
            return redirect()->to(site_url('inventory/products'))->with('errors', $errors)->with('old', $this->request->getPost());
        }

        $this->products->insert([
            'company_id' => $cid, 'name' => $name, 'unit' => $unit, 'rate' => $rate, 'status' => 1,
        ]);
        activity_log('Inventory', 'Add', "Product \"{$name}\" added");
        return redirect()->to(site_url('inventory/products'))->with('success', "Product \"{$name}\" added.");
    }

    public function deleteProduct($id = null)
    {
        $cid = $this->cid();
        $row = $this->products->find((int) $id);
        if ($row && (int) $row['company_id'] === $cid) {
            $this->products->delete((int) $id);
            activity_log('Inventory', 'Delete', "Product #{$id} removed");
        }
        return redirect()->to(site_url('inventory/products'))->with('success', 'Product removed.');
    }

    // =================================================================
    // 3. Stock Position — day / month / year
    // =================================================================
    public function position()
    {
        $cid    = $this->cid();
        $period = in_array($this->request->getGet('period'), ['day', 'month', 'year'], true)
            ? $this->request->getGet('period') : 'month';

        // Resolve the range + a human label from the period + its picker value.
        [$from, $to, $label, $pickerValue] = $this->resolveRange($period);

        $data = (new InventoryReport())->stockPosition($cid, $from, $to);

        return $this->render('stock_position', [
            'title'       => 'Stock Position',
            'breadcrumb'  => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Stock Position']],
            'period'      => $period,
            'from'        => $from,
            'to'          => $to,
            'rangeLabel'  => $label,
            'pickerValue' => $pickerValue,
            'rows'        => $data['rows'],
            'totals'      => $data['totals'],
            'moduleCode'  => $this->moduleCode,
            'css'         => [base_url('assets/css/inventory.css')],
        ]);
    }

    /** @return array{0:string,1:string,2:string,3:string} [from, to, label, pickerValue] */
    private function resolveRange(string $period): array
    {
        if ($period === 'day') {
            $d = $this->cleanDate((string) $this->request->getGet('date')) ?: date('Y-m-d');
            return [$d, $d, date('d M Y', strtotime($d)), $d];
        }
        if ($period === 'year') {
            $y = preg_match('/^\d{4}$/', (string) $this->request->getGet('year')) ? (string) $this->request->getGet('year') : date('Y');
            return ["{$y}-01-01", "{$y}-12-31", $y, $y];
        }
        // month
        $m = preg_match('/^\d{4}-\d{2}$/', (string) $this->request->getGet('month')) ? (string) $this->request->getGet('month') : date('Y-m');
        $from = $m . '-01';
        $to   = date('Y-m-t', strtotime($from));
        return [$from, $to, date('M Y', strtotime($from)), $m];
    }
}
