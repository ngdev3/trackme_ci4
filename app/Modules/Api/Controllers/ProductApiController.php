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
        helper('url'); // base_url() for image URLs
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

    /**
     * Offline-first PUSH. Applies a batch of local creates/updates/deletes from
     * the mobile outbox and returns the server ids so the app can link its rows.
     * Idempotent per (company, client_uuid). current_stock is movement-owned, so
     * a create seeds it from opening_stock and updates never touch it.
     */
    public function sync()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        if (! $cid) {
            return $this->failValidationErrors('Select a company first.');
        }
        helper('url');
        $imgUrl = static fn (?string $p) => ! empty($p) ? base_url($p) : null;
        $model  = new ProductModel();
        $mapped = [];

        foreach ((array) ($this->input('creates') ?? []) as $c) {
            $uuid = isset($c['client_uuid']) ? trim((string) $c['client_uuid']) : '';
            if ($uuid !== '') {
                $existing = $model->withDeleted()->where('company_id', $cid)->where('client_uuid', $uuid)->first();
                if ($existing) {
                    $mapped[] = ['local_id' => $c['local_id'] ?? null, 'server_id' => (int) $existing['id'], 'image_path' => $existing['image_path'] ?? null, 'image_url' => $imgUrl($existing['image_path'] ?? null), 'updated_at' => $existing['updated_at'] ?? date('Y-m-d H:i:s')];
                    continue;
                }
            }
            $data = $this->payloadFrom($c, $cid, (int) $user['id']);
            if ($data['name'] === '') {
                continue;
            }
            $data['client_uuid']   = $uuid ?: null;
            $data['current_stock'] = $data['opening_stock']; // seed; movements adjust later
            $model->skipValidation(true)->insert($data);
            $id  = (int) $model->getInsertID();
            $row = $model->find($id);
            $mapped[] = ['local_id' => $c['local_id'] ?? null, 'server_id' => $id, 'image_path' => $row['image_path'] ?? null, 'image_url' => $imgUrl($row['image_path'] ?? null), 'updated_at' => $row['updated_at'] ?? date('Y-m-d H:i:s')];
        }

        foreach (array_merge((array) ($this->input('updates') ?? []), (array) ($this->input('deletes') ?? [])) as $u) {
            $sid = (int) ($u['server_id'] ?? 0);
            if ($sid <= 0) {
                continue;
            }
            $row = $model->withDeleted()->where('company_id', $cid)->find($sid);
            if (! $row) {
                continue;
            }
            if (! empty($u['is_deleted'])) {
                if (empty($row['deleted_at'])) {
                    $this->deleteImageFile($row['image_path'] ?? null);
                    $model->delete($sid);
                }
            } else {
                $data = $this->payloadFrom($u, $cid, (int) $user['id']);
                unset($data['created_by']); // keep original author; never touch current_stock
                if ($data['name'] !== '') {
                    $model->skipValidation(true)->update($sid, $data);
                }
            }
            $fresh    = $model->withDeleted()->find($sid);
            $mapped[] = ['local_id' => $u['local_id'] ?? null, 'server_id' => $sid, 'image_path' => $fresh['image_path'] ?? null, 'image_url' => $imgUrl($fresh['image_path'] ?? null), 'updated_at' => $fresh['updated_at'] ?? date('Y-m-d H:i:s')];
        }

        return $this->respond(['status' => 'ok', 'server_time' => date('Y-m-d H:i:s'), 'mapped' => $mapped]);
    }

    /** Offline-first PULL. Products changed since the cursor (incl. soft-deleted). */
    public function changes()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        helper('url');
        $since = trim((string) ($this->request->getGet('since') ?? ''));
        $b     = (new ProductModel())->withDeleted()->where('company_id', $cid);
        if ($since !== '') {
            $b->where('updated_at >=', $since);
        }
        $rows = $b->orderBy('updated_at', 'ASC')->orderBy('id', 'ASC')->findAll();
        return $this->respond([
            'status'      => 'ok',
            'server_time' => date('Y-m-d H:i:s'),
            'changes'     => array_map([$this, 'shapeSync'], $rows),
        ]);
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
        // Photo changed or cleared → remove the old file so it doesn't orphan.
        if (array_key_exists('image_path', $data)
            && ! empty($row['image_path'])
            && $row['image_path'] !== $data['image_path']) {
            $this->deleteImageFile($row['image_path']);
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
        // Delete the uploaded photo along with the product.
        $this->deleteImageFile($row['image_path'] ?? null);
        $model->delete((int) $id);
        return $this->respond(['status' => 'ok', 'message' => 'Product removed.']);
    }

    /** Remove a stored product image file (scoped to uploads/products for safety). */
    private function deleteImageFile(?string $rel): void
    {
        if (! $rel || strpos($rel, 'uploads/products/') !== 0) {
            return;
        }
        $full = FCPATH . $rel;
        if (is_file($full)) {
            @unlink($full);
        }
    }

    /** Assemble a validated write payload from the request. */
    private function payload(int $cid, int $userId): array
    {
        $num = fn ($k) => (float) ($this->input($k) ?? 0);
        $data = [
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

        // Product photo: a new base64 data URL replaces the image; `remove_image`
        // clears it. When neither is sent, image_path is left untouched (so an
        // update without a new photo keeps the existing one).
        $img = (string) ($this->input('image') ?? '');
        if ($img !== '') {
            $stored = $this->storeImage($cid, $img);
            if ($stored !== null) {
                $data['image_path'] = $stored;
            }
        } elseif ($this->input('remove_image')) {
            $data['image_path'] = null;
        }

        return $data;
    }

    /** Decode a base64 image data URL and store it; returns the relative path or null. */
    private function storeImage(int $cid, string $dataUrl): ?string
    {
        if (! preg_match('#^data:image/(png|jpe?g|webp);base64,(.+)$#is', $dataUrl, $m)) {
            return null;
        }
        $ext = strtolower($m[1]) === 'jpeg' ? 'jpg' : strtolower($m[1]);
        $bin = base64_decode($m[2], true);
        if ($bin === false || strlen($bin) < 64 || strlen($bin) > 4 * 1024 * 1024) {
            return null; // invalid or > 4 MB
        }
        $dir = FCPATH . 'uploads/products/' . $cid;
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        $name = bin2hex(random_bytes(8)) . '.' . $ext;
        if (@file_put_contents($dir . '/' . $name, $bin) === false) {
            return null;
        }
        return 'uploads/products/' . $cid . '/' . $name;
    }

    /** Like payload() but reads from a sync-batch array item (no current_stock —
     *  that is movement-owned and never overwritten by a master update). */
    private function payloadFrom(array $item, int $cid, int $userId): array
    {
        $num  = fn ($k) => (float) ($item[$k] ?? 0);
        $data = [
            'company_id'     => $cid,
            'created_by'     => $userId,
            'name'           => trim((string) ($item['name'] ?? '')),
            'sku'            => trim((string) ($item['sku'] ?? '')) ?: null,
            'category'       => trim((string) ($item['category'] ?? '')) ?: null,
            'unit'           => trim((string) ($item['unit'] ?? '')) ?: null,
            'hsn'            => trim((string) ($item['hsn'] ?? '')) ?: null,
            'sale_price'     => round($num('sale_price'), 2),
            'purchase_price' => round($num('purchase_price'), 2),
            'opening_stock'  => round($num('opening_stock'), 3),
            'low_stock'      => round($num('low_stock'), 3),
            'tax_rate'       => round($num('tax_rate'), 2),
            'description'    => trim((string) ($item['description'] ?? '')) ?: null,
            'status'         => (int) ($item['status'] ?? 1) === 0 ? 0 : 1,
        ];
        $img = (string) ($item['image'] ?? '');
        if ($img !== '') {
            $stored = $this->storeImage($cid, $img);
            if ($stored !== null) {
                $data['image_path'] = $stored;
            }
        } elseif (! empty($item['remove_image'])) {
            $data['image_path'] = null;
        }
        return $data;
    }

    /** shape() + the fields the offline client needs to reconcile (sync feed). */
    private function shapeSync(array $r): array
    {
        return $this->shape($r) + [
            'client_uuid' => $r['client_uuid'] ?? null,
            'is_deleted'  => ! empty($r['deleted_at']) ? 1 : 0,
            'created_at'  => $r['created_at'] ?? null,
            'updated_at'  => $r['updated_at'] ?? null,
            'deleted_at'  => $r['deleted_at'] ?? null,
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
            'image_path'     => $r['image_path'] ?? null,
            'image_url'      => ! empty($r['image_path']) ? base_url($r['image_path']) : null,
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
