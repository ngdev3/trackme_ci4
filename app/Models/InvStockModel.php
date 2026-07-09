<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Running stock balance per (company, product, warehouse) — a denormalised cache
 * for fast reads on large datasets. Always adjusted through InventoryService so
 * it stays in step with the inv_movements ledger.
 */
class InvStockModel extends Model
{
    protected $table         = 'inv_stock';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = ['company_id', 'product_id', 'warehouse_id', 'bags', 'weight', 'updated_at'];

    /**
     * Apply a signed delta (bags / weight) to a stock cell, creating it if
     * absent. Uses an atomic upsert so concurrent movements don't clobber.
     */
    public function applyDelta(int $companyId, int $productId, int $warehouseId, float $bags, float $weight): void
    {
        $now = date('Y-m-d H:i:s');
        $this->db->query(
            'INSERT INTO `inv_stock` (`company_id`,`product_id`,`warehouse_id`,`bags`,`weight`,`updated_at`)
             VALUES (?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE `bags` = `bags` + VALUES(`bags`), `weight` = `weight` + VALUES(`weight`), `updated_at` = VALUES(`updated_at`)',
            [$companyId, $productId, $warehouseId, $bags, $weight, $now]
        );
    }

    /** Current bags available for a product+warehouse (0 if none). */
    public function bagsAt(int $companyId, int $productId, int $warehouseId): float
    {
        $row = $this->where('company_id', $companyId)->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)->first();
        return $row ? (float) $row['bags'] : 0.0;
    }

    /** Stock rows joined to product + warehouse names (for search / reports). */
    public function scopedList(?int $companyId)
    {
        $b = $this->select('inv_stock.*, p.name AS product_name, p.avg_weight, p.low_stock, p.sku, w.name AS warehouse_name, w.location')
            ->join('inv_products p', 'p.id = inv_stock.product_id', 'left')
            ->join('inv_warehouses w', 'w.id = inv_stock.warehouse_id', 'left');
        if ($companyId !== null) {
            $b->where('inv_stock.company_id', $companyId);
        }
        return $b;
    }
}
