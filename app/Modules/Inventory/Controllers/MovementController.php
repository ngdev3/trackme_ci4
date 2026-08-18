<?php

namespace Modules\Inventory\Controllers;

use App\Controllers\BaseController;
use App\Libraries\InventoryService;
use App\Models\InvMovementModel;
use App\Models\InvPartyModel;
use App\Models\InvProductModel;
use App\Models\InvWarehouseModel;

/**
 * Unified Stock Movement — one compact, dense screen for every stock change:
 * IN | OUT | TRANSFER | PRODUCTION | ADJUSTMENT. A type selector shows only the
 * fields each type needs; every save goes through InventoryService, so the ledger
 * and running balance stay in lock-step (Current Stock == Stock Ledger).
 */
class MovementController extends BaseController
{
    protected $helpers = ['url', 'form', 'auth', 'menu', 'ui', 'text', 'settings', 'company', 'format'];

    protected string $moduleCode = 'inventory';
    protected string $baseRoute  = 'inventory';

    private function cid(): int
    {
        return (int) company_id();
    }

    private function uid(): int
    {
        return (int) user_id();
    }

    /** Render the unified movement screen + a compact recent-movements ledger. */
    public function index()
    {
        $cid        = $this->cid();
        $products   = (new InvProductModel())->forCompany($cid);
        $warehouses = (new InvWarehouseModel())->forCompany($cid);

        if (empty($products) || empty($warehouses)) {
            return $this->render('setup_needed', [
                'title'         => 'Set up Inventory',
                'breadcrumb'    => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Setup']],
                'needProduct'   => empty($products),
                'needWarehouse' => empty($warehouses),
                'moduleCode'    => $this->moduleCode,
                'css'           => [base_url('assets/css/inventory.css')],
            ]);
        }

        $recent = (new InvMovementModel())->scopedList($cid)
            ->orderBy('inv_movements.id', 'DESC')->limit(40)->get()->getResultArray();

        // One-time token to defuse double-click / refresh resubmits.
        $token = bin2hex(random_bytes(8));
        session()->set('inv_mv_token', $token);

        return $this->render('movement', [
            'title'      => 'Stock Movement',
            'breadcrumb' => [['label' => 'Inventory', 'url' => site_url('inventory')], ['label' => 'Stock Movement']],
            'products'   => $products,
            'warehouses' => $warehouses,
            'suppliers'  => (new InvPartyModel())->forCompany($cid, 'supplier'),
            'customers'  => (new InvPartyModel())->forCompany($cid, 'customer'),
            'recent'     => $recent,
            'formToken'  => $token,
            'moduleCode' => $this->moduleCode,
            'baseRoute'  => $this->baseRoute,
            'css'        => [base_url('assets/css/inventory.css')],
        ]);
    }

    /** JSON — live available bags for a product+godown (shown beside the qty field). */
    public function available()
    {
        $cid   = $this->cid();
        $p     = (int) $this->request->getGet('product_id');
        $w     = (int) $this->request->getGet('warehouse_id');
        $bags  = ($p && $w) ? (new InventoryService())->availableBags($cid, $p, $w) : 0.0;
        return $this->response->setJSON(['available' => $bags]);
    }

    /** Save any movement type through the single atomic InventoryService seam. */
    public function save()
    {
        $cid  = $this->cid();
        $uid  = $this->uid();
        $type = (string) $this->request->getPost('type');
        $svc  = new InventoryService();

        // Idempotency: reject a resubmit of the same rendered form (double-click /
        // back-forward / refresh). A fresh token is minted on every index() render.
        $token = (string) $this->request->getPost('_mvtoken');
        if ($token === '' || $token !== (string) session('inv_mv_token')) {
            return redirect()->to(site_url('inventory/movement'))->with('warning', 'That entry was already submitted.');
        }
        session()->remove('inv_mv_token');

        try {
            switch ($type) {
                case 'in':
                    $partyId = $this->partyId($cid, (string) $this->request->getPost('party'), 'supplier');
                    $r = $svc->recordInward($this->common($cid, $uid) + [
                        'party_id' => $partyId,
                        'rate'     => (float) $this->request->getPost('rate'),
                        'notes'    => $this->str('reference'),
                    ]);
                    $msg = "Inward {$r['entry_no']} saved.";
                    break;

                case 'out':
                    $partyId = $this->partyId($cid, (string) $this->request->getPost('party'), 'customer');
                    $r = $svc->recordOutward($this->common($cid, $uid) + [
                        'party_id'       => $partyId,
                        'rate'           => (float) $this->request->getPost('rate'),
                        'notes'          => $this->str('reference'),
                        'allow_negative' => false,
                    ]);
                    $msg = "Outward {$r['entry_no']} saved.";
                    break;

                case 'transfer':
                    $r = $svc->recordTransfer([
                        'company_id'        => $cid,
                        'product_id'        => (int) $this->request->getPost('product_id'),
                        'from_warehouse_id' => (int) $this->request->getPost('warehouse_id'),
                        'to_warehouse_id'   => (int) $this->request->getPost('to_warehouse_id'),
                        'bags'              => (float) $this->request->getPost('bags'),
                        'notes'             => $this->str('reference'),
                        'source'            => 'web',
                        'created_by'        => $uid,
                    ]);
                    $msg = "Transfer {$r['out_entry_no']} → {$r['in_entry_no']} saved.";
                    break;

                case 'production':
                    $r = $svc->recordProduction([
                        'company_id'   => $cid,
                        'input'        => [
                            'product_id'   => (int) $this->request->getPost('product_id'),
                            'warehouse_id' => (int) $this->request->getPost('warehouse_id'),
                            'bags'         => (float) $this->request->getPost('bags'),
                        ],
                        'outputs'      => $this->outputs(),
                        'wastage_bags' => (float) $this->request->getPost('wastage_bags'),
                        'notes'        => $this->str('reference'),
                        'source'       => 'web',
                        'created_by'   => $uid,
                    ]);
                    $msg = "Production {$r['input_entry_no']} saved (" . count($r['outputs']) . ' outputs).';
                    break;

                case 'adjustment':
                    $reason = $this->str('reason');
                    if ($reason === null) {
                        return redirect()->back()->withInput()->with('error', 'A reason is required for every adjustment.');
                    }
                    $qty  = abs((float) $this->request->getPost('bags'));
                    $sign = $this->request->getPost('sign') === '-' ? -1 : 1;
                    $r = $svc->recordAdjustment([
                        'company_id'   => $cid,
                        'product_id'   => (int) $this->request->getPost('product_id'),
                        'warehouse_id' => (int) $this->request->getPost('warehouse_id'),
                        'delta_bags'   => $sign * $qty,
                        'reason'       => $reason,
                        'notes'        => $this->str('reference'),
                        'source'       => 'web',
                        'created_by'   => $uid,
                    ]);
                    $msg = "Adjustment {$r['entry_no']} saved.";
                    break;

                default:
                    return redirect()->back()->withInput()->with('error', 'Choose a movement type.');
            }
        } catch (\InvalidArgumentException $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        } catch (\RuntimeException $e) {
            return redirect()->back()->withInput()->with('error', 'Not enough stock. Available: ' . $e->getMessage() . ' bags.');
        }

        return redirect()->to(site_url('inventory/movement'))->with('success', $msg);
    }

    // ---- helpers ----------------------------------------------------------

    /** Fields common to inward/outward. */
    private function common(int $cid, int $uid): array
    {
        return [
            'company_id'   => $cid,
            'product_id'   => (int) $this->request->getPost('product_id'),
            'warehouse_id' => (int) $this->request->getPost('warehouse_id'),
            'bags'         => (float) $this->request->getPost('bags'),
            'source'       => 'web',
            'created_by'   => $uid,
        ];
    }

    /** Trimmed POST string or null if empty. */
    private function str(string $key): ?string
    {
        $v = trim((string) $this->request->getPost($key));
        return $v !== '' ? $v : null;
    }

    /** Resolve/create a party by name for the given side (or null). */
    private function partyId(int $cid, string $name, string $type): ?int
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        return (new InvPartyModel())->findOrCreate($cid, $name, $type) ?: null;
    }

    /** Parse the production output grid rows: out_product[] + out_bags[]. */
    private function outputs(): array
    {
        $ids  = (array) $this->request->getPost('out_product');
        $bags = (array) $this->request->getPost('out_bags');
        $out  = [];
        foreach ($ids as $i => $pid) {
            $pid = (int) $pid;
            $qty = (float) ($bags[$i] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $out[] = ['product_id' => $pid, 'bags' => $qty];
            }
        }
        return $out;
    }
}
