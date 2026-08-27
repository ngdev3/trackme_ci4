<?php

namespace Modules\Api\Controllers;

use App\Models\ProductModel;
use App\Models\StockMovementModel;

/**
 * Stock In / Out for the inventory Product Master. Records a movement in the
 * ledger and adjusts the product's current_stock. Company-scoped; the active
 * company is validated in BaseApiController.
 *
 *   POST api/v1/stock/move          (Bearer) {product_id, type: in|out, qty, rate?, note?}
 *   GET  api/v1/stock/movements     (Bearer) [?product_id=]
 */
class StockApiController extends BaseApiController
{
    private function scope(): array
    {
        $user = $this->currentApiUser();
        return $user ? [$user, $this->resolveCompanyId($user)] : [null, null];
    }

    public function move()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $productId = (int) ($this->input('product_id') ?? 0);
        $type      = $this->input('type') === 'out' ? 'out' : 'in';
        $qty       = round((float) ($this->input('qty') ?? 0), 3);
        $rate      = round((float) ($this->input('rate') ?? 0), 2);
        $note      = trim((string) ($this->input('note') ?? '')) ?: null;

        if ($qty <= 0) {
            return $this->failValidationErrors(['qty' => 'Enter a quantity greater than zero.']);
        }
        if ($qty > InvoiceApiController::MAX_QTY) {
            return $this->failValidationErrors(['qty' => 'Quantity is too large (max ' . number_format(InvoiceApiController::MAX_QTY) . ').']);
        }
        if ($rate < 0 || $rate > \App\Models\TransactionModel::MAX_AMOUNT) {
            return $this->failValidationErrors(['rate' => 'Rate is out of range.']);
        }
        $products = new ProductModel();
        $product  = $products->scoped($cid)->find($productId);
        if (! $product) {
            return $this->failNotFound('Product not found.');
        }

        $current = (float) $product['current_stock'];
        $newStock = $type === 'in' ? $current + $qty : $current - $qty;
        if ($newStock < 0) {
            return $this->failValidationErrors(['qty' => 'Not enough stock. Available: ' . rtrim(rtrim((string) $current, '0'), '.') . '.']);
        }

        (new StockMovementModel())->insert([
            'company_id' => $cid,
            'product_id' => $productId,
            'type'       => $type,
            'qty'        => $qty,
            'rate'       => $rate,
            'note'       => $note,
            'created_by' => (int) $user['id'],
        ]);
        $products->update($productId, ['current_stock' => round($newStock, 3)]);

        return $this->respond([
            'status'        => 'ok',
            'message'       => $type === 'in' ? 'Stock added.' : 'Stock removed.',
            'current_stock' => round($newStock, 3),
        ]);
    }

    /**
     * Offline-first PUSH. Applies a batch of stock movements captured offline:
     * each is inserted and its product's current_stock adjusted, exactly like
     * move(). Idempotent per (company, client_uuid). Movements are append-only,
     * so only creates are accepted. Returns server ids to link the local rows.
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
        $moves    = new StockMovementModel();
        $products = new ProductModel();
        $mapped   = [];

        foreach ((array) ($this->input('creates') ?? []) as $c) {
            $uuid = isset($c['client_uuid']) ? trim((string) $c['client_uuid']) : '';
            if ($uuid !== '') {
                $existing = $moves->where('company_id', $cid)->where('client_uuid', $uuid)->first();
                if ($existing) {
                    $mapped[] = ['local_id' => $c['local_id'] ?? null, 'server_id' => (int) $existing['id'], 'updated_at' => $existing['updated_at'] ?? date('Y-m-d H:i:s')];
                    continue;
                }
            }
            $pid     = (int) ($c['product_server_id'] ?? 0);
            $product = $pid > 0 ? $products->scoped($cid)->find($pid) : null;
            if (! $product) {
                continue; // product not on the server yet — the app retries next cycle
            }
            $type = ($c['type'] ?? 'in') === 'out' ? 'out' : 'in';
            $qty  = round((float) ($c['qty'] ?? 0), 3);
            if ($qty <= 0) {
                continue;
            }
            $moves->insert([
                'company_id'  => $cid,
                'client_uuid' => $uuid ?: null,
                'product_id'  => $pid,
                'type'        => $type,
                'qty'         => $qty,
                'rate'        => round((float) ($c['rate'] ?? 0), 2),
                'note'        => trim((string) ($c['note'] ?? '')) ?: null,
                'created_by'  => (int) $user['id'],
            ]);
            $mid   = (int) $moves->getInsertID();
            $delta = $type === 'out' ? -$qty : $qty;
            $products->update($pid, ['current_stock' => round((float) $product['current_stock'] + $delta, 3)]);
            $fresh    = $moves->find($mid);
            $mapped[] = ['local_id' => $c['local_id'] ?? null, 'server_id' => $mid, 'updated_at' => $fresh['updated_at'] ?? date('Y-m-d H:i:s')];
        }

        return $this->respond(['status' => 'ok', 'server_time' => date('Y-m-d H:i:s'), 'mapped' => $mapped]);
    }

    /** Offline-first PULL. Movements changed since the cursor (for the ledger). */
    public function changes()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $since = trim((string) ($this->request->getGet('since') ?? ''));
        $b     = (new StockMovementModel())->where('company_id', $cid);
        if ($since !== '') {
            $b->where('updated_at >=', $since);
        }
        $rows = $b->orderBy('updated_at', 'ASC')->orderBy('id', 'ASC')->findAll();
        return $this->respond([
            'status'      => 'ok',
            'server_time' => date('Y-m-d H:i:s'),
            'changes'     => array_map(static fn ($r) => [
                'id'          => (int) $r['id'],
                'client_uuid' => $r['client_uuid'] ?? null,
                'product_id'  => (int) $r['product_id'],
                'type'        => $r['type'],
                'qty'         => (float) $r['qty'],
                'rate'        => (float) $r['rate'],
                'note'        => $r['note'],
                'created_at'  => $r['created_at'] ?? null,
                'updated_at'  => $r['updated_at'] ?? null,
            ], $rows),
        ]);
    }

    public function movements()
    {
        [$user, $cid] = $this->scope();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $productId = (int) ($this->request->getGet('product_id') ?? 0) ?: null;
        $rows = (new StockMovementModel())->recentFor($cid, $productId, 60);
        return $this->respond([
            'status'    => 'ok',
            'movements' => array_map(static fn ($r) => [
                'id'           => (int) $r['id'],
                'product_id'   => (int) $r['product_id'],
                'product_name' => $r['product_name'],
                'unit'         => $r['unit'],
                'type'         => $r['type'],
                'qty'          => (float) $r['qty'],
                'rate'         => (float) $r['rate'],
                'note'         => $r['note'],
                'at'           => $r['created_at'],
            ], $rows),
        ]);
    }
}
