<?php

namespace Modules\Inventory\Controllers;

use App\Controllers\BaseController;
use App\Libraries\InventoryService;
use App\Models\InvAttachmentModel;
use App\Models\InvLotModel;
use App\Models\InvMovementModel;
use App\Models\InvPartyModel;
use App\Models\InvProductModel;
use App\Models\InvWarehouseModel;

/**
 * Mandi Inventory — worker-facing screens. Deliberately minimal: big buttons,
 * few fields, dropdowns and search so labourers/gate operators need almost no
 * training. All stock changes flow through InventoryService so the ledger, the
 * stock balance and the audit log always stay in step.
 */
class InventoryController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company'];

    protected string $moduleCode = 'inventory';
    protected string $baseRoute  = 'inventory';

    protected InvProductModel $products;
    protected InvWarehouseModel $warehouses;
    protected InvPartyModel $parties;

    public function __construct()
    {
        $this->products   = new InvProductModel();
        $this->warehouses = new InvWarehouseModel();
        $this->parties    = new InvPartyModel();
    }

    private function uid(): int
    {
        return (int) user_id();
    }

    private function cid(): ?int
    {
        return company_id();
    }

    /** How the entry was captured — 'voice' when it came from the voice screen, else 'web'. */
    private function entrySource(): string
    {
        return $this->request->getPost('entry_source') === 'voice' ? 'voice' : 'web';
    }

    /** True once today's inventory is closed (locked) — no new entries until reopened. */
    private function dayLocked(): bool
    {
        return (new \App\Models\InvDailyClosingModel())->isLocked($this->cid(), date('Y-m-d'));
    }

    /** Master lists for the forms (dropdowns). */
    private function masterLists(): array
    {
        $cid = $this->cid();
        return [
            'products'   => $this->products->forCompany($cid),
            'warehouses' => $this->warehouses->forCompany($cid),
            'suppliers'  => $this->parties->forCompany($cid, 'supplier'),
            'customers'  => $this->parties->forCompany($cid, 'customer'),
        ];
    }

    // ===============================================================
    // Worker hub
    // ===============================================================
    public function index()
    {
        $cid   = $this->cid();
        $mv    = new InvMovementModel();
        $today = date('Y-m-d');

        $sumBags = static function (string $type) use ($mv, $cid, $today): float {
            $b = $mv->builder()->selectSum('bags')->where('deleted_at', null)
                ->where('movement_type', $type)->where('DATE(created_at)', $today);
            if ($cid !== null) {
                $b->where('company_id', $cid);
            }
            return (float) ($b->get()->getRowArray()['bags'] ?? 0);
        };

        // Live current stock (per product + godown) — the heart of the workspace.
        $stock = (new \App\Models\InvStockModel())->scopedList($cid)
            ->orderBy('p.name', 'ASC')->orderBy('w.name', 'ASC')->get()->getResultArray();

        $totalBags = 0.0;
        $totalWt   = 0.0;
        $lowCount  = 0;
        foreach ($stock as $s) {
            $totalBags += (float) $s['bags'];
            $totalWt   += (float) $s['weight'];
            if ((float) $s['bags'] > 0 && (int) $s['low_stock'] > 0 && (float) $s['bags'] <= (int) $s['low_stock']) {
                $lowCount++;
            }
        }

        // Recent movements (the activity feed).
        $recent = $mv->scopedList($cid)->orderBy('inv_movements.id', 'DESC')->limit(8)->get()->getResultArray();

        return $this->render('hub', [
            'title'      => 'Inventory',
            'breadcrumb' => [['label' => 'Inventory']],
            'todayIn'    => $sumBags('inward'),
            'todayOut'   => $sumBags('outward'),
            'stock'      => $stock,
            'recent'     => $recent,
            'totalBags'  => $totalBags,
            'totalWt'    => $totalWt,
            'lowCount'   => $lowCount,
            'productCount'   => count($this->products->forCompany($cid)),
            'warehouseCount' => count($this->warehouses->forCompany($cid)),
            'hasMasters' => ! empty($this->products->forCompany($cid)) && ! empty($this->warehouses->forCompany($cid)),
            'canAdd'     => can('inventory', 'add'),
            'canEdit'    => can('inventory', 'edit'),
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    // ===============================================================
    // Task 1 — Stock Inward
    // ===============================================================
    public function inward()
    {
        $m = $this->masterLists();
        if (empty($m['products']) || empty($m['warehouses'])) {
            return $this->render('setup_needed', [
                'title'      => 'Set up Inventory',
                'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Setup']],
                'needProduct'   => empty($m['products']),
                'needWarehouse' => empty($m['warehouses']),
                'moduleCode' => $this->moduleCode,
                'css'        => [base_url('assets/css/inventory.css')],
            ]);
        }

        return $this->render('inward', $m + [
            'title'      => 'Stock Inward',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Stock Inward']],
            'errors'     => session()->getFlashdata('errors') ?? [],
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    public function storeInward()
    {
        $cid = (int) $this->cid();

        if ($this->dayLocked()) {
            return redirect()->to(site_url('inventory/closing'))->with('error', "Today's inventory is closed. Ask an owner/admin to reopen it before adding entries.");
        }

        $productId   = (int) $this->request->getPost('product_id');
        $warehouseId = (int) $this->request->getPost('warehouse_id');
        $bags        = (float) $this->request->getPost('bags');
        $weight      = $this->request->getPost('weight') !== '' ? (float) $this->request->getPost('weight') : null;
        $supplier    = trim((string) $this->request->getPost('supplier_name'));

        // Minimal, worker-friendly validation.
        $errors = [];
        if (! $this->products->findForCompany($productId, $cid)) {
            $errors['product_id'] = 'Please choose a product.';
        }
        if (! $this->warehouses->findForCompany($warehouseId, $cid)) {
            $errors['warehouse_id'] = 'Please choose a godown.';
        }
        if ($bags <= 0) {
            $errors['bags'] = 'Enter the number of bags.';
        }
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        // Supplier is optional; create it on the fly if a new name was typed.
        $partyId = null;
        if ($supplier !== '') {
            $partyId = $this->parties->findOrCreate($cid, $supplier, 'supplier') ?: null;
        }

        $result = (new InventoryService())->recordInward([
            'company_id'   => $cid,
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'party_id'     => $partyId,
            'bags'         => $bags,
            'weight'       => $weight,
            'rack'         => trim((string) $this->request->getPost('rack')) ?: null,
            'notes'        => trim((string) $this->request->getPost('notes')) ?: null,
            'source'       => $this->entrySource(),
            'created_by'   => $this->uid(),
        ]);

        // Optional proof files (photo / bill / challan / video / voice note).
        $this->saveAttachments((int) $result['movement_id']);

        return redirect()->to(site_url('inventory/receipt/' . $result['movement_id']))
            ->with('success', "Inward saved. Lot {$result['lot_no']}, Entry {$result['entry_no']}.");
    }

    // ===============================================================
    // Task 3 — Stock Search (cards; voice + QR fill the search box)
    // ===============================================================
    public function search()
    {
        $cid = $this->cid();
        $q   = trim((string) $this->request->getGet('q'));
        $results = ($q !== '' || $this->request->getGet('all') !== null)
            ? (new \App\Models\InvStockModel())->search($cid, $q)
            : [];

        return $this->render('search', [
            'title'      => 'Stock Search',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Search']],
            'q'          => $q,
            'results'    => $results,
            'searched'   => $q !== '' || $this->request->getGet('all') !== null,
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    // ===============================================================
    // Task 5 — Voice-Based Entry
    // ===============================================================

    /** Voice entry screen: speak a sentence, review the parsed entry, confirm. */
    public function voice()
    {
        $m = $this->masterLists();
        if (empty($m['products']) || empty($m['warehouses'])) {
            return $this->render('setup_needed', [
                'title'         => 'Set up Inventory',
                'breadcrumb'    => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Setup']],
                'needProduct'   => empty($m['products']),
                'needWarehouse' => empty($m['warehouses']),
                'moduleCode'    => $this->moduleCode,
                'css'           => [base_url('assets/css/inventory.css')],
            ]);
        }

        return $this->render('voice', $m + [
            'title'      => 'Voice Entry',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Voice Entry']],
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    /** Parse a spoken sentence into a structured entry (AJAX/JSON). */
    public function voiceParse()
    {
        $cid        = $this->cid();
        $transcript = trim((string) $this->request->getPost('transcript'));
        if ($transcript === '') {
            return $this->response->setJSON(['status' => 'empty', 'message' => 'Nothing was heard. Please try again.']);
        }

        $parsed = (new \App\Libraries\VoiceEntryParser())->parse(
            $transcript,
            $this->products->forCompany($cid),
            $this->warehouses->forCompany($cid),
            $this->parties->forCompany($cid)
        );

        // If a product + godown resolved, show the live available stock (handy
        // for outward) so the worker sees it while confirming.
        $parsed['available'] = null;
        if (! empty($parsed['product_id']) && ! empty($parsed['warehouse_id'])) {
            $parsed['available'] = (new InventoryService())->availableBags($cid, (int) $parsed['product_id'], (int) $parsed['warehouse_id']);
        }

        return $this->response->setJSON(['status' => 'ok', 'parsed' => $parsed]);
    }

    // ===============================================================
    // Task 4 — Physical Stock Verification + correction requests
    // ===============================================================

    /** Owner/admin (or super admin) may approve corrections; workers may not. */
    private function canApprove(): bool
    {
        return is_super_admin_account() || in_array(company_role(), ['owner', 'admin'], true);
    }

    public function verify()
    {
        $m = $this->masterLists();
        if (empty($m['products']) || empty($m['warehouses'])) {
            return redirect()->to(site_url('inventory/masters'))->with('error', 'Add products and godowns first.');
        }
        // System stock per product+warehouse so the form can show it live.
        $stock = (new \App\Models\InvStockModel())->scopedList($this->cid())->get()->getResultArray();
        $sys   = [];
        foreach ($stock as $s) {
            $sys[(int) $s['product_id'] . '_' . (int) $s['warehouse_id']] = (float) $s['bags'];
        }
        return $this->render('verify', $m + [
            'title'      => 'Verify Stock',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Verify Stock']],
            'sys'        => $sys,
            'reasons'    => \App\Models\InvCorrectionModel::REASONS,
            'errors'     => session()->getFlashdata('errors') ?? [],
            'canApprove' => $this->canApprove(),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    public function storeVerification()
    {
        $cid = (int) $this->cid();

        if ($this->dayLocked()) {
            return redirect()->to(site_url('inventory/closing'))->with('error', "Today's inventory is closed. Ask an owner/admin to reopen it before submitting corrections.");
        }

        $productId   = (int) $this->request->getPost('product_id');
        $warehouseId = (int) $this->request->getPost('warehouse_id');
        $physical    = (float) $this->request->getPost('physical_bags');
        $reason      = (string) $this->request->getPost('reason');

        $errors = [];
        if (! $this->products->findForCompany($productId, $cid)) {
            $errors['product_id'] = 'Please choose a product.';
        }
        if (! $this->warehouses->findForCompany($warehouseId, $cid)) {
            $errors['warehouse_id'] = 'Please choose a godown.';
        }
        if ($this->request->getPost('physical_bags') === '' || $physical < 0) {
            $errors['physical_bags'] = 'Enter the counted physical bags.';
        }
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $system = (new InventoryService())->availableBags($cid, $productId, $warehouseId);
        $diff   = round($physical - $system, 2);

        if ($diff == 0.0) {
            return redirect()->to(site_url('inventory/verify'))->with('success', 'Physical count matches the system. No correction needed.');
        }
        if (! array_key_exists($reason, \App\Models\InvCorrectionModel::REASONS)) {
            return redirect()->back()->withInput()->with('errors', ['reason' => 'Please select a reason for the difference.']);
        }

        // Workers can only REQUEST — never adjust. Record a pending correction.
        (new \App\Models\InvCorrectionModel())->insert([
            'company_id'    => $cid,
            'product_id'    => $productId,
            'warehouse_id'  => $warehouseId,
            'system_bags'   => $system,
            'physical_bags' => $physical,
            'difference'    => $diff,
            'reason'        => $reason,
            'note'          => trim((string) $this->request->getPost('note')) ?: null,
            'status'        => 'pending',
            'requested_by'  => $this->uid(),
        ]);
        activity_log('Inventory', 'Add', "Correction requested: {$diff} bags ({$reason})");

        return redirect()->to(site_url('inventory/corrections'))
            ->with('success', 'Correction request submitted for owner/admin approval. Stock is unchanged until approved.');
    }

    /** List correction requests (approvers see approve/reject controls). */
    public function corrections()
    {
        $cid  = $this->cid();
        $rows = (new \App\Models\InvCorrectionModel())->scopedList($cid)
            ->orderBy("FIELD(inv_corrections.status,'pending','approved','rejected')", '', false)
            ->orderBy('inv_corrections.id', 'DESC')->findAll(100);

        return $this->render('corrections', [
            'title'      => 'Stock Corrections',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Corrections']],
            'rows'       => $rows,
            'reasons'    => \App\Models\InvCorrectionModel::REASONS,
            'canApprove' => $this->canApprove(),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    public function approveCorrection($id = null)
    {
        if (! $this->canApprove()) {
            return redirect()->to(site_url('inventory/corrections'))->with('error', 'Only the owner or an admin can approve corrections.');
        }
        $cid = (int) $this->cid();
        $c   = (new \App\Models\InvCorrectionModel())->findForCompany((int) $id, $cid);
        if (! $c || $c['status'] !== 'pending') {
            return redirect()->to(site_url('inventory/corrections'))->with('error', 'Correction not found or already reviewed.');
        }

        $result = (new InventoryService())->recordAdjustment([
            'company_id'   => $cid,
            'product_id'   => (int) $c['product_id'],
            'warehouse_id' => (int) $c['warehouse_id'],
            'delta_bags'   => (float) $c['difference'],
            'reason'       => $c['reason'],
            'notes'        => 'Correction #' . $c['id'] . ($c['note'] ? ' — ' . $c['note'] : ''),
            'source'       => 'web',
            'created_by'   => $this->uid(),
        ]);

        (new \App\Models\InvCorrectionModel())->update((int) $c['id'], [
            'status'      => 'approved',
            'movement_id' => $result['movement_id'],
            'reviewed_by' => $this->uid(),
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        activity_log('Inventory', 'Edit', "Correction #{$c['id']} approved → adjustment {$result['entry_no']}");

        return redirect()->to(site_url('inventory/corrections'))->with('success', "Approved. Adjustment {$result['entry_no']} created and stock reconciled.");
    }

    public function rejectCorrection($id = null)
    {
        if (! $this->canApprove()) {
            return redirect()->to(site_url('inventory/corrections'))->with('error', 'Only the owner or an admin can reject corrections.');
        }
        $cid = (int) $this->cid();
        $c   = (new \App\Models\InvCorrectionModel())->findForCompany((int) $id, $cid);
        if (! $c || $c['status'] !== 'pending') {
            return redirect()->to(site_url('inventory/corrections'))->with('error', 'Correction not found or already reviewed.');
        }
        (new \App\Models\InvCorrectionModel())->update((int) $c['id'], [
            'status'      => 'rejected',
            'reviewed_by' => $this->uid(),
            'review_note' => trim((string) $this->request->getPost('review_note')) ?: null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        activity_log('Inventory', 'Edit', "Correction #{$c['id']} rejected");
        return redirect()->to(site_url('inventory/corrections'))->with('success', 'Correction rejected. Stock unchanged.');
    }

    // ===============================================================
    // Task 2 — Stock Outward
    // ===============================================================
    public function outward()
    {
        $m = $this->masterLists();
        if (empty($m['products']) || empty($m['warehouses'])) {
            return $this->render('setup_needed', [
                'title'      => 'Set up Inventory',
                'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Setup']],
                'needProduct'   => empty($m['products']),
                'needWarehouse' => empty($m['warehouses']),
                'moduleCode' => $this->moduleCode,
                'css'        => [base_url('assets/css/inventory.css')],
            ]);
        }

        // Current stock per product+warehouse so the form can show availability live.
        $stock = (new \App\Models\InvStockModel())->scopedList($this->cid())->get()->getResultArray();
        $avail = [];
        foreach ($stock as $s) {
            $avail[(int) $s['product_id'] . '_' . (int) $s['warehouse_id']] = (float) $s['bags'];
        }

        return $this->render('outward', $m + [
            'title'      => 'Stock Outward',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Stock Outward']],
            'avail'      => $avail,
            'errors'     => session()->getFlashdata('errors') ?? [],
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    public function storeOutward()
    {
        $cid = (int) $this->cid();

        if ($this->dayLocked()) {
            return redirect()->to(site_url('inventory/closing'))->with('error', "Today's inventory is closed. Ask an owner/admin to reopen it before adding entries.");
        }

        $productId   = (int) $this->request->getPost('product_id');
        $warehouseId = (int) $this->request->getPost('warehouse_id');
        $bags        = (float) $this->request->getPost('bags');
        $weight      = $this->request->getPost('weight') !== '' ? (float) $this->request->getPost('weight') : null;
        $customer    = trim((string) $this->request->getPost('customer_name'));
        $allowNeg    = (int) $this->request->getPost('allow_negative') === 1;

        $errors = [];
        if (! $this->products->findForCompany($productId, $cid)) {
            $errors['product_id'] = 'Please choose a product.';
        }
        if (! $this->warehouses->findForCompany($warehouseId, $cid)) {
            $errors['warehouse_id'] = 'Please choose a godown.';
        }
        if ($bags <= 0) {
            $errors['bags'] = 'Enter the number of bags.';
        }
        if ($errors !== []) {
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        $partyId = null;
        if ($customer !== '') {
            $partyId = $this->parties->findOrCreate($cid, $customer, 'customer') ?: null;
        }

        try {
            $result = (new InventoryService())->recordOutward([
                'company_id'     => $cid,
                'product_id'     => $productId,
                'warehouse_id'   => $warehouseId,
                'party_id'       => $partyId,
                'bags'           => $bags,
                'weight'         => $weight,
                'vehicle_no'     => trim((string) $this->request->getPost('vehicle_no')) ?: null,
                'notes'          => trim((string) $this->request->getPost('notes')) ?: null,
                'allow_negative' => $allowNeg,
                'source'         => $this->entrySource(),
                'created_by'     => $this->uid(),
            ]);
        } catch (\RuntimeException $e) {
            // Not enough stock — send back with the available count so the worker
            // can correct it (or confirm dispatching more if allowed).
            $available = (float) $e->getMessage();
            return redirect()->back()->withInput()
                ->with('errors', ['bags' => 'Only ' . number_format($available, 0) . ' bags available in this godown.'])
                ->with('short_available', $available);
        }

        $this->saveAttachments((int) $result['movement_id']);

        return redirect()->to(site_url('inventory/receipt/' . $result['movement_id']))
            ->with('success', "Outward saved. Entry {$result['entry_no']}. {$result['available']} bags left.");
    }

    /** Success / receipt screen after an entry. */
    public function receipt($id = null)
    {
        $mv  = new InvMovementModel();
        $row = $mv->scopedList($this->cid())->where('inv_movements.id', (int) $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to(site_url('inventory'))->with('error', 'Entry not found.');
        }
        return $this->render('receipt', [
            'title'      => 'Entry Saved',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Receipt']],
            'row'        => $row,
            'attachments'=> (new InvAttachmentModel())->forMovement((int) $row['id']),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    // ===============================================================
    // Masters (owner/admin) — products, godowns, parties
    // ===============================================================
    public function masters()
    {
        $cid = $this->cid();
        return $this->render('masters', [
            'title'      => 'Inventory Setup',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Setup']],
            'products'   => $this->products->forCompany($cid),
            'warehouses' => $this->warehouses->forCompany($cid),
            'parties'    => $this->parties->forCompany($cid),
            'setup'      => (new \App\Libraries\InventoryReport())->setupScore($cid),
            'errors'     => session()->getFlashdata('errors') ?? [],
            'errForm'    => session()->getFlashdata('errForm'),
            'canDelete'  => can('inventory', 'delete'),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    /**
     * A master name already used by this company (case-insensitive, ignoring
     * soft-deleted rows). Guards against duplicate products/godowns/parties that
     * would confuse the dropdowns and split a product's stock in two.
     */
    private function masterNameExists(string $table, int $cid, string $name, ?string $extraCol = null, ?string $extraVal = null): bool
    {
        $b = \Config\Database::connect()->table($table)
            ->where('company_id', $cid)->where('deleted_at', null)
            ->where('LOWER(name)', mb_strtolower($name));
        if ($extraCol !== null) {
            $b->where($extraCol, $extraVal);
        }
        return $b->countAllResults() > 0;
    }

    /** Validate a non-negative number field; returns [ok, value, error]. */
    private function validNumber($raw, string $label, float $max = 100000000): array
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return [true, 0.0, null];
        }
        if (! is_numeric($raw)) {
            return [false, 0.0, "{$label} must be a number."];
        }
        $v = (float) $raw;
        if ($v < 0) {
            return [false, 0.0, "{$label} cannot be negative."];
        }
        if ($v > $max) {
            return [false, 0.0, "{$label} looks too large."];
        }
        return [true, $v, null];
    }

    /** Send the user back to the setup wizard with field errors on the right step. */
    private function backToWizard(string $form, array $errors)
    {
        return redirect()->to(site_url('inventory/masters'))->withInput()
            ->with('errors', $errors)->with('errForm', $form);
    }

    public function storeProduct()
    {
        $cid  = (int) $this->cid();
        $name = trim((string) $this->request->getPost('name'));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Product name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Product name is too long (max 150 characters).';
        } elseif ($this->masterNameExists('inv_products', $cid, $name)) {
            $errors['name'] = 'A product with this name already exists.';
        }

        [$wOk, $avgWeight, $wErr] = $this->validNumber($this->request->getPost('avg_weight'), 'Average weight');
        if (! $wOk) { $errors['avg_weight'] = $wErr; }
        [$rOk, $rate, $rErr] = $this->validNumber($this->request->getPost('rate'), 'Rate');
        if (! $rOk) { $errors['rate'] = $rErr; }
        [$lOk, $lowStock, $lErr] = $this->validNumber($this->request->getPost('low_stock'), 'Low-stock alert', 1000000);
        if (! $lOk) { $errors['low_stock'] = $lErr; }

        if ($errors !== []) {
            return $this->backToWizard('product', $errors);
        }

        $this->products->insert([
            'company_id' => $cid,
            'name'       => $name,
            'unit'       => trim((string) $this->request->getPost('unit')) ?: 'bag',
            'avg_weight' => round($avgWeight, 2),
            'rate'       => round($rate, 2),
            'low_stock'  => (int) $lowStock,
            'status'     => 1,
        ]);
        activity_log('Inventory', 'Add', "Product \"{$name}\" added");
        return redirect()->to(site_url('inventory/masters'))->with('success', "Product \"{$name}\" added.");
    }

    public function storeWarehouse()
    {
        $cid  = (int) $this->cid();
        $name = trim((string) $this->request->getPost('name'));

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Godown name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Godown name is too long (max 150 characters).';
        } elseif ($this->masterNameExists('inv_warehouses', $cid, $name)) {
            $errors['name'] = 'A godown with this name already exists.';
        }

        [$cOk, $capacity, $cErr] = $this->validNumber($this->request->getPost('capacity'), 'Capacity');
        if (! $cOk) { $errors['capacity'] = $cErr; }

        $location = trim((string) $this->request->getPost('location'));
        if (mb_strlen($location) > 191) {
            $errors['location'] = 'Location is too long.';
        }

        if ($errors !== []) {
            return $this->backToWizard('warehouse', $errors);
        }

        $this->warehouses->insert([
            'company_id' => $cid,
            'name'       => $name,
            'location'   => $location ?: null,
            'capacity'   => (int) $capacity,
            'status'     => 1,
        ]);
        activity_log('Inventory', 'Add', "Godown \"{$name}\" added");
        return redirect()->to(site_url('inventory/masters'))->with('success', "Godown \"{$name}\" added.");
    }

    public function storeParty()
    {
        $cid  = (int) $this->cid();
        $name = trim((string) $this->request->getPost('name'));
        $type = (string) $this->request->getPost('type');
        $type = in_array($type, ['supplier', 'customer', 'both'], true) ? $type : 'both';

        $errors = [];
        if ($name === '') {
            $errors['name'] = 'Party name is required.';
        } elseif (mb_strlen($name) > 150) {
            $errors['name'] = 'Party name is too long (max 150 characters).';
        } elseif ($this->masterNameExists('inv_parties', $cid, $name, 'type', $type)) {
            $errors['name'] = 'A party with this name and type already exists.';
        }

        // Phone optional; when given, must look like a real number.
        $phone = trim((string) $this->request->getPost('phone'));
        if ($phone !== '') {
            $digits = preg_replace('/\D+/', '', $phone);
            if (strlen($digits) < 7 || strlen($digits) > 15) {
                $errors['phone'] = 'Enter a valid phone number.';
            }
        }

        if ($errors !== []) {
            return $this->backToWizard('party', $errors);
        }

        $this->parties->insert([
            'company_id' => $cid,
            'name'       => $name,
            'type'       => $type,
            'phone'      => $phone ?: null,
            'status'     => 1,
        ]);
        activity_log('Inventory', 'Add', "Party \"{$name}\" added");
        return redirect()->to(site_url('inventory/masters'))->with('success', "Party \"{$name}\" added.");
    }

    public function deleteMaster($type = null, $id = null)
    {
        $cid = (int) $this->cid();
        $id  = (int) $id;
        $map = ['product' => $this->products, 'warehouse' => $this->warehouses, 'party' => $this->parties];
        if (! isset($map[$type])) {
            return redirect()->back()->with('error', 'Unknown item.');
        }
        $model = $map[$type];
        $row   = $model->find($id);
        if ($row && (int) $row['company_id'] === $cid) {
            $model->delete($id);
            activity_log('Inventory', 'Delete', ucfirst($type) . " #{$id} removed");
        }
        return redirect()->to(site_url('inventory/masters'))->with('success', ucfirst($type) . ' removed.');
    }

    // ===============================================================
    // Task 6 — Entry detail + proof attachments (multiple, any type)
    // ===============================================================

    /** Full detail of one movement with its proof files (view + manage). */
    public function entry($id = null)
    {
        $row = (new InvMovementModel())->scopedList($this->cid())
            ->where('inv_movements.id', (int) $id)->get()->getRowArray();
        if (! $row) {
            return redirect()->to(site_url('inventory'))->with('error', 'Entry not found.');
        }
        // Lot number (inward entries carry a lot).
        if (! empty($row['lot_id'])) {
            $lot = (new InvLotModel())->find((int) $row['lot_id']);
            $row['lot_no'] = $lot['lot_no'] ?? null;
        }
        $creator = ! empty($row['created_by']) ? (new \App\Models\UserModel())->find((int) $row['created_by']) : null;

        return $this->render('entry', [
            'title'      => 'Entry ' . $row['entry_no'],
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => $row['entry_no']]],
            'row'        => $row,
            'creatorName'=> $creator['name'] ?? null,
            'attachments'=> (new InvAttachmentModel())->forMovement((int) $row['id']),
            'canAdd'     => can('inventory', 'add'),
            'canDelete'  => $this->canApprove(),
            'maxMb'      => self::MAX_ATTACH_MB,
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    /** Add one or more proof files to an existing entry. */
    public function attachToEntry($id = null)
    {
        $cid = (int) $this->cid();
        $mv  = (new InvMovementModel())->findForCompany((int) $id, $cid);
        if (! $mv) {
            return redirect()->to(site_url('inventory'))->with('error', 'Entry not found.');
        }
        $n = $this->saveAttachments((int) $id);
        if ($n > 0) {
            activity_log('Inventory', 'Edit', "Attached {$n} proof file(s) to {$mv['entry_no']}");
        }
        return redirect()->to(site_url('inventory/entry/' . $id))
            ->with($n > 0 ? 'success' : 'error', $n > 0 ? "{$n} file(s) attached." : 'No valid files were uploaded (check type / size).');
    }

    /** Delete a proof file. Owner/admin only — workers can never delete. */
    public function deleteAttachment($id = null)
    {
        if (! $this->canApprove()) {
            return redirect()->back()->with('error', 'Only the owner or an admin can delete proof files.');
        }
        $cid = (int) $this->cid();
        $att = (new InvAttachmentModel())->find((int) $id);
        if (! $att || (int) $att['company_id'] !== $cid) {
            return redirect()->to(site_url('inventory'))->with('error', 'Attachment not found.');
        }
        $path = $this->uploadDir($cid) . $att['stored_name'];
        if (is_file($path)) {
            @unlink($path);
        }
        (new InvAttachmentModel())->delete((int) $att['id']);
        activity_log('Inventory', 'Delete', "Removed proof file \"{$att['original_name']}\"");
        return redirect()->to(site_url('inventory/entry/' . (int) $att['movement_id']))->with('success', 'Attachment removed.');
    }

    // ===============================================================
    // Serve an attachment file (scoped to the active company)
    // ===============================================================
    public function attachment($id = null)
    {
        $att = (new InvAttachmentModel())->find((int) $id);
        if (! $att || (int) $att['company_id'] !== (int) $this->cid()) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        $path = $this->uploadDir((int) $att['company_id']) . $att['stored_name'];
        if (! is_file($path)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        // Attachments (bills/challans) download; media plays inline.
        $inline = in_array($att['kind'], ['image', 'video', 'audio', 'pdf'], true);
        return $this->response
            ->setHeader('Content-Type', $att['mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', ($inline ? 'inline' : 'attachment') . '; filename="' . rawurlencode($att['original_name']) . '"')
            ->setBody(file_get_contents($path));
    }

    // ===============================================================
    // Shared: proof-file uploads → attachments (auto-linked)
    // ===============================================================

    /** Max size accepted per proof file (MB). */
    private const MAX_ATTACH_MB = 50;

    private function uploadDir(int $companyId): string
    {
        $dir = WRITEPATH . 'uploads/inventory/' . $companyId . '/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    /**
     * Store every uploaded proof file against a movement, auto-linking each to
     * the entry, product, lot, party, user and timestamp. Accepts the single
     * `photo` field (camera capture) and a multi `attachments[]` field carrying
     * images, videos, voice notes, PDFs, bills and challans.
     *
     * @return int number of files stored
     */
    private function saveAttachments(int $movementId): int
    {
        $mv = (new InvMovementModel())->find($movementId);
        if (! $mv) {
            return 0;
        }
        $cid = (int) $mv['company_id'];

        // Collect files from both the single and the multi field.
        $files = [];
        if ($single = $this->request->getFile('photo')) {
            $files[] = $single;
        }
        foreach ((array) $this->request->getFileMultiple('attachments') as $f) {
            if ($f !== null) {
                $files[] = $f;
            }
        }
        if ($files === []) {
            return 0;
        }

        $dir        = $this->uploadDir($cid);
        $attModel   = new InvAttachmentModel();
        $stored     = 0;
        $firstImage = null;

        foreach ($files as $file) {
            if (! $file->isValid() || $file->hasMoved()) {
                continue;
            }
            if ($file->getSizeByUnit('mb') > self::MAX_ATTACH_MB) {
                continue;
            }
            $ext  = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
            $kind = InvAttachmentModel::kindFor((string) $file->getClientMimeType(), (string) $ext);
            $name = $file->getRandomName();
            try {
                $file->move($dir, $name);
            } catch (\Throwable $e) {
                log_message('error', 'Inventory attachment move failed: ' . $e->getMessage());
                continue;
            }
            $attModel->insert([
                'company_id'    => $cid,
                'movement_id'   => $movementId,
                'product_id'    => $mv['product_id'] ?? null,
                'lot_id'        => $mv['lot_id'] ?? null,
                'party_id'      => $mv['party_id'] ?? null,
                'kind'          => $kind,
                'original_name' => $file->getClientName(),
                'stored_name'   => $name,
                'mime'          => $file->getClientMimeType(),
                'size'          => (int) filesize($dir . $name),
                'created_by'    => $this->uid(),
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
            $stored++;
            if ($firstImage === null && $kind === 'image') {
                $firstImage = $name;
            }
        }

        // Keep the movement's quick thumbnail pointing at the first image.
        if ($firstImage !== null && empty($mv['photo'])) {
            (new InvMovementModel())->update($movementId, ['photo' => $firstImage]);
        }

        return $stored;
    }
}
