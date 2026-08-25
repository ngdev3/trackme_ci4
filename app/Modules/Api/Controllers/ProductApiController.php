<?php

namespace Modules\Api\Controllers;

use App\Models\ProductModel;

/**
 * Product Master (Stock / Inventory) API for the mobile app. Company-scoped
 * CRUD over the `products` table + a headline summary for the inventory
 * dashboard. The active company is attached by the app (company_id) and always
 * validated against the caller's memberships in BaseApiController.
 *
 *   GET    api/v1/products              (Bearer) [?company_id=&q=]
 *   GET    api/v1/products/summary      (Bearer)
 *   POST   api/v1/products              (Bearer) {name, ...}
 *   POST   api/v1/products/update/(:num)(Bearer) {name, ...}
 *   POST   api/v1/products/delete/(:num)(Bearer)
 */
class ProductApiController extends BaseApiController
{
    private function scope(): array
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return [null, null];
        }
        return [$user, $this->resolveCompanyId($user)];
    }

    public function index()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $q = trim((string) $this->request->getGet('q'));
        $builder = (new ProductModel())->scoped($cid)->orderBy('name', 'ASC');
        if ($q !== '') {
            $builder->groupStart()
                ->like('name', $q)->orLike('sku', $q)->orLike('category', $q)
                ->groupEnd();
        }
        return $this->respond([
            'status'   => 'ok',
            'products' => array_map([$this, 'shape'], $builder->findAll()),
        ]);
    }

    public function summary()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        return $this->respond(['status' => 'ok', 'summary' => (new ProductModel())->summary($cid)]);
    }

    public function create()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        if (! $cid) {
            return $this->failValidationErrors('Select a company first.');
        }
        $data = $this->payload($cid, (int) $user['id']);
        if ($data['name'] === '') {
            return $this->failValidationErrors(['name' => 'Product name is required.']);
        }
        $model = new ProductModel();
        $model->skipValidation(true)->insert($data);
        return $this->respondCreated(['status' => 'ok', 'message' => 'Product saved.', 'id' => (int) $model->getInsertID()]);
    }

    public function update($id = null)
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $model = new ProductModel();
        $row   = $model->scoped($cid)->find((int) $id);
        if (! $row) {
            return $this->failNotFound('Product not found.');
        }
        $data = $this->payload($cid, (int) $user['id']);
        unset($data['created_by']); // keep original author
        if ($data['name'] === '') {
            return $this->failValidationErrors(['name' => 'Product name is required.']);
        }
        $model->skipValidation(true)->update((int) $id, $data);
        return $this->respond(['status' => 'ok', 'message' => 'Product updated.']);
    }

    public function remove($id = null)
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $model = new ProductModel();
        $row   = $model->scoped($cid)->find((int) $id);
        if (! $row) {
            return $this->failNotFound('Product not found.');
        }
        $model->delete((int) $id);
        return $this->respond(['status' => 'ok', 'message' => 'Product removed.']);
    }

    /** Assemble a validated write payload from the request. */
    private function payload(int $cid, int $userId): array
    {
        $num = fn ($k) => (float) ($this->input($k) ?? 0);
        return [
            'company_id'     => $cid,
            'created_by'     => $userId,
            'name'           => trim((string) ($this->input('name') ?? '')),
            'sku'            => trim((string) ($this->input('sku') ?? '')) ?: null,
            'category'       => trim((string) ($this->input('category') ?? '')) ?: null,
            'unit'           => trim((string) ($this->input('unit') ?? '')) ?: null,
            'hsn'            => trim((string) ($this->input('hsn') ?? '')) ?: null,
            'sale_price'     => round($num('sale_price'), 2),
            'purchase_price' => round($num('purchase_price'), 2),
            'opening_stock'  => round($num('opening_stock'), 3),
            'current_stock'  => round($num('current_stock'), 3),
            'low_stock'      => round($num('low_stock'), 3),
            'tax_rate'       => round($num('tax_rate'), 2),
            'description'    => trim((string) ($this->input('description') ?? '')) ?: null,
            'status'         => (int) ($this->input('status') ?? 1) === 0 ? 0 : 1,
        ];
    }

    /** Public shape of a product row for the app. */
    private function shape(array $r): array
    {
        return [
            'id'             => (int) $r['id'],
            'name'           => $r['name'],
            'sku'            => $r['sku'],
            'category'       => $r['category'],
            'unit'           => $r['unit'],
            'hsn'            => $r['hsn'],
            'sale_price'     => (float) $r['sale_price'],
            'purchase_price' => (float) $r['purchase_price'],
            'opening_stock'  => (float) $r['opening_stock'],
            'current_stock'  => (float) $r['current_stock'],
            'low_stock'      => (float) $r['low_stock'],
            'tax_rate'       => (float) $r['tax_rate'],
            'description'    => $r['description'],
            'status'         => (int) $r['status'],
        ];
    }
}
