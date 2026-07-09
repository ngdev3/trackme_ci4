<?php

namespace Modules\Api\Controllers;

use App\Libraries\InventoryService;
use App\Models\CompanyUserModel;
use App\Models\InvPartyModel;
use App\Models\InvProductModel;
use App\Models\InvStockModel;
use App\Models\InvWarehouseModel;

/**
 * Mandi Inventory REST API for the mobile app. Bearer-token authenticated (see
 * BaseApiController). The company is resolved from the caller's membership: a
 * `company_id` may be supplied but is always validated against what the user
 * actually belongs to, so one firm's stock can never be touched by another.
 */
class InventoryApiController extends BaseApiController
{
    /** Resolve + authorise the active company for the API caller. */
    private function companyId(array $user): ?int
    {
        $members = new CompanyUserModel();
        $requested = (int) ($this->input('company_id') ?? 0);
        if ($requested > 0 && $members->isMember($requested, (int) $user['id'])) {
            return $requested;
        }
        $companies = (new \App\Models\CompanyModel())->forUser((int) $user['id']);
        return $companies !== [] ? (int) $companies[0]['id'] : null;
    }

    /** GET masters: products, godowns, parties — to populate the app's dropdowns. */
    public function masters()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        return $this->respond([
            'status'     => 'ok',
            'company_id' => $cid,
            'products'   => (new InvProductModel())->forCompany($cid),
            'warehouses' => (new InvWarehouseModel())->forCompany($cid),
            'suppliers'  => (new InvPartyModel())->forCompany($cid, 'supplier'),
            'customers'  => (new InvPartyModel())->forCompany($cid, 'customer'),
        ]);
    }

    /** GET current stock (per product+warehouse) for the app. */
    public function stock()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $rows = (new InvStockModel())->scopedList($cid)->orderBy('p.name', 'ASC')->get()->getResultArray();
        return $this->respond(['status' => 'ok', 'stock' => $rows]);
    }

    /** GET stock search (product / party / godown / SKU / lot) — card data for the app. */
    public function search()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $q = trim((string) ($this->input('q') ?? $this->request->getGet('q') ?? ''));
        return $this->respond([
            'status'  => 'ok',
            'q'       => $q,
            'results' => (new InvStockModel())->search($cid, $q),
        ]);
    }

    /** POST record a stock inward from the app. */
    public function inward()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $productId   = (int) $this->input('product_id');
        $warehouseId = (int) $this->input('warehouse_id');
        $bags        = (float) $this->input('bags');

        if (! (new InvProductModel())->findForCompany($productId, $cid)) {
            return $this->failValidationErrors('Invalid product.');
        }
        if (! (new InvWarehouseModel())->findForCompany($warehouseId, $cid)) {
            return $this->failValidationErrors('Invalid godown.');
        }
        if ($bags <= 0) {
            return $this->failValidationErrors('Bags must be greater than zero.');
        }

        $partyId = null;
        $supplier = trim((string) ($this->input('supplier_name') ?? ''));
        if ($supplier !== '') {
            $partyId = (new InvPartyModel())->findOrCreate($cid, $supplier, 'supplier') ?: null;
        }

        $result = (new InventoryService())->recordInward([
            'company_id'   => $cid,
            'product_id'   => $productId,
            'warehouse_id' => $warehouseId,
            'party_id'     => $partyId,
            'bags'         => $bags,
            'weight'       => $this->input('weight') !== null ? (float) $this->input('weight') : null,
            'rack'         => trim((string) ($this->input('rack') ?? '')) ?: null,
            'notes'        => trim((string) ($this->input('notes') ?? '')) ?: null,
            'source'       => 'mobile',
            'created_by'   => (int) $user['id'],
        ]);

        return $this->respondCreated([
            'status'    => 'ok',
            'message'   => 'Inward saved.',
            'entry_no'  => $result['entry_no'],
            'lot_no'    => $result['lot_no'],
            'weight'    => $result['weight'],
            'available' => (new InventoryService())->availableBags($cid, $productId, $warehouseId),
        ]);
    }

    /** POST record a stock outward from the app. */
    public function outward()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $productId   = (int) $this->input('product_id');
        $warehouseId = (int) $this->input('warehouse_id');
        $bags        = (float) $this->input('bags');

        if (! (new InvProductModel())->findForCompany($productId, $cid)) {
            return $this->failValidationErrors('Invalid product.');
        }
        if (! (new InvWarehouseModel())->findForCompany($warehouseId, $cid)) {
            return $this->failValidationErrors('Invalid godown.');
        }
        if ($bags <= 0) {
            return $this->failValidationErrors('Bags must be greater than zero.');
        }

        $partyId  = null;
        $customer = trim((string) ($this->input('customer_name') ?? ''));
        if ($customer !== '') {
            $partyId = (new InvPartyModel())->findOrCreate($cid, $customer, 'customer') ?: null;
        }

        try {
            $result = (new InventoryService())->recordOutward([
                'company_id'     => $cid,
                'product_id'     => $productId,
                'warehouse_id'   => $warehouseId,
                'party_id'       => $partyId,
                'bags'           => $bags,
                'weight'         => $this->input('weight') !== null ? (float) $this->input('weight') : null,
                'vehicle_no'     => trim((string) ($this->input('vehicle_no') ?? '')) ?: null,
                'notes'          => trim((string) ($this->input('notes') ?? '')) ?: null,
                'allow_negative' => (bool) $this->input('allow_negative'),
                'source'         => 'mobile',
                'created_by'     => (int) $user['id'],
            ]);
        } catch (\RuntimeException $e) {
            return $this->respond([
                'status'    => 'insufficient_stock',
                'message'   => 'Not enough stock in this godown.',
                'available' => (float) $e->getMessage(),
            ], 409);
        }

        return $this->respondCreated([
            'status'    => 'ok',
            'message'   => 'Outward saved.',
            'entry_no'  => $result['entry_no'],
            'weight'    => $result['weight'],
            'available' => $result['available'],
        ]);
    }

    /** Whether the API caller may approve corrections for this company (owner/admin). */
    private function canApprove(int $companyId, array $user): bool
    {
        $m = (new CompanyUserModel())->membership($companyId, (int) $user['id']);
        return $m && in_array($m['role'] ?? '', ['owner', 'admin'], true);
    }

    /** POST submit a physical-verification correction request. */
    public function verify()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $productId   = (int) $this->input('product_id');
        $warehouseId = (int) $this->input('warehouse_id');
        $physical    = (float) $this->input('physical_bags');
        $reason      = (string) ($this->input('reason') ?? '');

        if (! (new InvProductModel())->findForCompany($productId, $cid)) {
            return $this->failValidationErrors('Invalid product.');
        }
        if (! (new InvWarehouseModel())->findForCompany($warehouseId, $cid)) {
            return $this->failValidationErrors('Invalid godown.');
        }

        $system = (new InventoryService())->availableBags($cid, $productId, $warehouseId);
        $diff   = round($physical - $system, 2);
        if ($diff == 0.0) {
            return $this->respond(['status' => 'match', 'message' => 'Physical matches system. No correction needed.', 'system' => $system]);
        }
        if (! array_key_exists($reason, \App\Models\InvCorrectionModel::REASONS)) {
            return $this->failValidationErrors('Select a valid reason: ' . implode(', ', array_keys(\App\Models\InvCorrectionModel::REASONS)));
        }

        $id = (new \App\Models\InvCorrectionModel())->insert([
            'company_id' => $cid, 'product_id' => $productId, 'warehouse_id' => $warehouseId,
            'system_bags' => $system, 'physical_bags' => $physical, 'difference' => $diff,
            'reason' => $reason, 'note' => trim((string) ($this->input('note') ?? '')) ?: null,
            'status' => 'pending', 'requested_by' => (int) $user['id'],
        ]);

        return $this->respondCreated([
            'status' => 'pending_approval', 'message' => 'Correction request submitted. Stock unchanged until approved.',
            'correction_id' => (int) $id, 'system' => $system, 'physical' => $physical, 'difference' => $diff,
        ]);
    }

    /** GET list correction requests for the company. */
    public function corrections()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        $rows = (new \App\Models\InvCorrectionModel())->scopedList($cid)
            ->orderBy('inv_corrections.id', 'DESC')->findAll(100);
        return $this->respond(['status' => 'ok', 'can_approve' => $this->canApprove($cid, $user), 'corrections' => $rows]);
    }

    /** POST approve a correction (owner/admin only) → auto adjustment entry. */
    public function approveCorrection($id = null)
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->companyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }
        if (! $this->canApprove($cid, $user)) {
            return $this->failForbidden('Only the owner or an admin can approve corrections.');
        }
        $model = new \App\Models\InvCorrectionModel();
        $c     = $model->findForCompany((int) $id, $cid);
        if (! $c || $c['status'] !== 'pending') {
            return $this->failNotFound('Correction not found or already reviewed.');
        }
        $result = (new InventoryService())->recordAdjustment([
            'company_id' => $cid, 'product_id' => (int) $c['product_id'], 'warehouse_id' => (int) $c['warehouse_id'],
            'delta_bags' => (float) $c['difference'], 'reason' => $c['reason'],
            'notes' => 'Correction #' . $c['id'], 'source' => 'mobile', 'created_by' => (int) $user['id'],
        ]);
        $model->update((int) $c['id'], [
            'status' => 'approved', 'movement_id' => $result['movement_id'],
            'reviewed_by' => (int) $user['id'], 'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        return $this->respond([
            'status' => 'ok', 'message' => 'Approved. Adjustment created and stock reconciled.',
            'entry_no' => $result['entry_no'],
            'available' => (new InventoryService())->availableBags($cid, (int) $c['product_id'], (int) $c['warehouse_id']),
        ]);
    }
}
