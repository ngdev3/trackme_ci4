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
}
