<?php

namespace Modules\Inventory\Controllers;

use App\Controllers\BaseController;
use App\Libraries\InventoryService;
use App\Models\InvAttachmentModel;
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
            'source'       => 'web',
            'created_by'   => $this->uid(),
        ]);

        // Optional photo proof.
        $this->savePhoto((int) $result['movement_id'], $productId, (int) $result['movement_id'], $partyId);

        return redirect()->to(site_url('inventory/receipt/' . $result['movement_id']))
            ->with('success', "Inward saved. Lot {$result['lot_no']}, Entry {$result['entry_no']}.");
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
                'source'         => 'web',
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

        $this->savePhoto((int) $result['movement_id'], $productId, 0, $partyId);

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
            'canDelete'  => can('inventory', 'delete'),
            'moduleCode' => $this->moduleCode,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    public function storeProduct()
    {
        $cid  = (int) $this->cid();
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Product name is required.');
        }
        $this->products->insert([
            'company_id' => $cid,
            'name'       => $name,
            'unit'       => trim((string) $this->request->getPost('unit')) ?: 'bag',
            'avg_weight' => round((float) $this->request->getPost('avg_weight'), 2),
            'low_stock'  => (int) $this->request->getPost('low_stock'),
            'status'     => 1,
        ]);
        activity_log('Inventory', 'Add', "Product \"{$name}\" added");
        return redirect()->to(site_url('inventory/masters'))->with('success', 'Product added.');
    }

    public function storeWarehouse()
    {
        $cid  = (int) $this->cid();
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Godown name is required.');
        }
        $this->warehouses->insert([
            'company_id' => $cid,
            'name'       => $name,
            'location'   => trim((string) $this->request->getPost('location')) ?: null,
            'capacity'   => (int) $this->request->getPost('capacity'),
            'status'     => 1,
        ]);
        activity_log('Inventory', 'Add', "Godown \"{$name}\" added");
        return redirect()->to(site_url('inventory/masters'))->with('success', 'Godown added.');
    }

    public function storeParty()
    {
        $cid  = (int) $this->cid();
        $name = trim((string) $this->request->getPost('name'));
        if ($name === '') {
            return redirect()->back()->with('error', 'Party name is required.');
        }
        $type = (string) $this->request->getPost('type');
        $this->parties->insert([
            'company_id' => $cid,
            'name'       => $name,
            'type'       => in_array($type, ['supplier', 'customer', 'both'], true) ? $type : 'both',
            'phone'      => trim((string) $this->request->getPost('phone')) ?: null,
            'status'     => 1,
        ]);
        activity_log('Inventory', 'Add', "Party \"{$name}\" added");
        return redirect()->to(site_url('inventory/masters'))->with('success', 'Party added.');
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
        return $this->response
            ->setHeader('Content-Type', $att['mime'] ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="' . rawurlencode($att['original_name']) . '"')
            ->setBody(file_get_contents($path));
    }

    // ===============================================================
    // Shared: photo upload → attachment
    // ===============================================================
    private function uploadDir(int $companyId): string
    {
        $dir = WRITEPATH . 'uploads/inventory/' . $companyId . '/';
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        return $dir;
    }

    private function savePhoto(int $movementId, int $productId, int $lotId, ?int $partyId): void
    {
        $file = $this->request->getFile('photo');
        if (! $file || ! $file->isValid() || $file->hasMoved()) {
            return;
        }
        if ($file->getSizeByUnit('mb') > 25) {
            return;
        }
        $cid    = (int) $this->cid();
        $dir    = $this->uploadDir($cid);
        $ext    = strtolower($file->getExtension() ?: pathinfo($file->getName(), PATHINFO_EXTENSION));
        $stored = $file->getRandomName();
        try {
            $file->move($dir, $stored);
        } catch (\Throwable $e) {
            log_message('error', 'Inventory photo move failed: ' . $e->getMessage());
            return;
        }
        (new InvMovementModel())->update($movementId, ['photo' => $stored]);
        (new InvAttachmentModel())->insert([
            'company_id'    => $cid,
            'movement_id'   => $movementId,
            'product_id'    => $productId,
            'lot_id'        => $lotId,
            'party_id'      => $partyId,
            'kind'          => InvAttachmentModel::kindFor((string) $file->getClientMimeType(), (string) $ext),
            'original_name' => $file->getClientName(),
            'stored_name'   => $stored,
            'mime'          => $file->getClientMimeType(),
            'size'          => (int) filesize($dir . $stored),
            'created_by'    => $this->uid(),
            'created_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
