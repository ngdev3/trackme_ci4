<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Stock In / Out ledger. Company-scoped; each row adjusts a product's stock.
 */
class StockMovementModel extends Model
{
    protected $table         = 'stock_movements';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'company_id', 'product_id', 'type', 'qty', 'rate', 'note', 'created_by',
    ];

    /** Recent movements for a company (optionally one product), newest first. */
    public function recentFor(?int $companyId, ?int $productId = null, int $limit = 50): array
    {
        $b = $this->select('stock_movements.*, products.name AS product_name, products.unit AS unit')
            ->join('products', 'products.id = stock_movements.product_id', 'left')
            ->where('stock_movements.company_id', (int) $companyId)
            ->orderBy('stock_movements.id', 'DESC')
            ->limit($limit);
        if ($productId) {
            $b->where('stock_movements.product_id', $productId);
        }
        return $b->findAll();
    }
}
