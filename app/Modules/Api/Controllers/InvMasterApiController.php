<?php

namespace Modules\Api\Controllers;

use App\Models\InvPartyModel;
use App\Models\InvProductModel;
use App\Models\InvStockModel;
use App\Models\InvWarehouseModel;

/**
 * CRUD for inventory masters (products, godowns, parties) from the mobile app.
 * Company-scoped and bearer-authenticated. Complements InventoryApiController
 * (which handles stock movements, closing, corrections and reports).
 *
 *   POST   inventory/products            {name, unit?, avg_weight?, low_stock?, sku?}
 *   PUT    inventory/products/{id}
 *   DELETE inventory/products/{id}
 *   ... same for warehouses and parties.
 */
class InvMasterApiController extends BaseApiController
{
    protected $helpers = ['settings'];

    /** [user, companyId] or an error response. */
    private function ctx()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return [null, null, $this->failUnauthorized('Invalid or missing token.')];
        }
        $cid = $this->resolveCompanyId($user);
        if (! $cid) {
            return [null, null, $this->failValidationErrors('No company for this user.')];
        }
        return [$user, $cid, null];
    }

    // ---------------- Products ----------------

    public function createProduct()
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $name = trim((string) $this->input('name', ''));
        if ($name === '') {
            return $this->failValidationErrors('Product name is required.');
        }
        $model = new InvProductModel();
        $id = $model->insert([
            'company_id' => $cid,
            'name'       => $name,
            'unit'       => trim((string) ($this->input('unit') ?? 'bag')) ?: 'bag',
            'avg_weight' => (float) ($this->input('avg_weight') ?? 0),
            'rate'       => (float) ($this->input('rate') ?? 0),
            'sku'        => trim((string) ($this->input('sku') ?? '')) ?: null,
            'low_stock'  => (int) ($this->input('low_stock') ?? 0),
            'status'     => 1,
        ], true);
        return $this->respondCreated(['status' => 'ok', 'id' => (int) $id, 'item' => $model->find((int) $id)]);
    }

    public function updateProduct($id = null)
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $model = new InvProductModel();
        if (! $model->findForCompany((int) $id, $cid)) {
            return $this->failNotFound('Product not found.');
        }
        $data = $this->onlyProvided(['name', 'unit', 'avg_weight', 'rate', 'sku', 'low_stock', 'status']);
        if ($data === []) {
            return $this->failValidationErrors('Nothing to update.');
        }
        $model->update((int) $id, $data);
        return $this->respond(['status' => 'ok', 'item' => $model->find((int) $id)]);
    }

    public function deleteProduct($id = null)
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $model = new InvProductModel();
        if (! $model->findForCompany((int) $id, $cid)) {
            return $this->failNotFound('Product not found.');
        }
        $model->delete((int) $id); // soft delete
        return $this->respondDeleted(['status' => 'ok', 'message' => 'Product deleted.']);
    }

    // ---------------- Warehouses ----------------

    public function createWarehouse()
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $name = trim((string) $this->input('name', ''));
        if ($name === '') {
            return $this->failValidationErrors('Godown name is required.');
        }
        $model = new InvWarehouseModel();
        $id = $model->insert([
            'company_id' => $cid,
            'name'       => $name,
            'location'   => trim((string) ($this->input('location') ?? '')) ?: null,
            'capacity'   => (int) ($this->input('capacity') ?? 0),
            'status'     => 1,
        ], true);
        return $this->respondCreated(['status' => 'ok', 'id' => (int) $id, 'item' => $model->find((int) $id)]);
    }

    public function updateWarehouse($id = null)
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $model = new InvWarehouseModel();
        if (! $model->findForCompany((int) $id, $cid)) {
            return $this->failNotFound('Godown not found.');
        }
        $data = $this->onlyProvided(['name', 'location', 'capacity', 'status']);
        if ($data === []) {
            return $this->failValidationErrors('Nothing to update.');
        }
        $model->update((int) $id, $data);
        return $this->respond(['status' => 'ok', 'item' => $model->find((int) $id)]);
    }

    public function deleteWarehouse($id = null)
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $model = new InvWarehouseModel();
        if (! $model->findForCompany((int) $id, $cid)) {
            return $this->failNotFound('Godown not found.');
        }
        $model->delete((int) $id);
        return $this->respondDeleted(['status' => 'ok', 'message' => 'Godown deleted.']);
    }

    // ---------------- Parties ----------------

    public function createParty()
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $name = trim((string) $this->input('name', ''));
        if ($name === '') {
            return $this->failValidationErrors('Party name is required.');
        }
        $type = trim((string) ($this->input('type') ?? 'both'));
        if (! in_array($type, ['supplier', 'customer', 'both'], true)) {
            $type = 'both';
        }
        $model = new InvPartyModel();
        $id = $model->insert([
            'company_id' => $cid,
            'name'       => $name,
            'type'       => $type,
            'phone'      => trim((string) ($this->input('phone') ?? '')) ?: null,
            'status'     => 1,
        ], true);
        return $this->respondCreated(['status' => 'ok', 'id' => (int) $id, 'item' => $model->find((int) $id)]);
    }

    public function updateParty($id = null)
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $model = new InvPartyModel();
        if (! $model->findForCompany((int) $id, $cid)) {
            return $this->failNotFound('Party not found.');
        }
        $data = $this->onlyProvided(['name', 'type', 'phone', 'status']);
        if ($data === []) {
            return $this->failValidationErrors('Nothing to update.');
        }
        $model->update((int) $id, $data);
        return $this->respond(['status' => 'ok', 'item' => $model->find((int) $id)]);
    }

    public function deleteParty($id = null)
    {
        [$user, $cid, $err] = $this->ctx();
        if ($err) {
            return $err;
        }
        $model = new InvPartyModel();
        if (! $model->findForCompany((int) $id, $cid)) {
            return $this->failNotFound('Party not found.');
        }
        $model->delete((int) $id);
        return $this->respondDeleted(['status' => 'ok', 'message' => 'Party deleted.']);
    }

    /**
     * Pick only the fields present in the request body (partial update), casting
     * numerics. Keeps PUT semantics simple and avoids clobbering unset columns.
     *
     * @param list<string> $fields
     * @return array<string,mixed>
     */
    private function onlyProvided(array $fields): array
    {
        $json = $this->request->getJSON(true);
        $out  = [];
        foreach ($fields as $f) {
            $val = null;
            $has = false;
            if (is_array($json) && array_key_exists($f, $json)) {
                $val = $json[$f];
                $has = true;
            } elseif ($this->request->getPost($f) !== null) {
                $val = $this->request->getPost($f);
                $has = true;
            }
            if (! $has) {
                continue;
            }
            if (in_array($f, ['avg_weight', 'rate'], true)) {
                $val = (float) $val;
            } elseif (in_array($f, ['low_stock', 'capacity', 'status'], true)) {
                $val = (int) $val;
            } elseif (is_string($val)) {
                $val = trim($val);
            }
            $out[$f] = $val;
        }
        return $out;
    }
}
