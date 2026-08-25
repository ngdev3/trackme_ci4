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
