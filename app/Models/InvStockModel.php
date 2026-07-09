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

    /**
     * Current bags on hand per product for a company (summed across godowns).
     *
     * @return array<int, float> productId => bags
     */
    public function bagsByProduct(int $companyId): array
    {
        $rows = $this->select('product_id, COALESCE(SUM(bags), 0) AS bags')
            ->where('company_id', $companyId)
            ->groupBy('product_id')
            ->findAll();
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['product_id']] = (float) $r['bags'];
        }
        return $out;
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

    /**
     * Worker stock search. Matches a free-text term against product name, godown
     * name, product SKU/QR, lot number or party (supplier/customer) name, and
     * returns rich cards (bags, weight, godown, rack, status). Blank term = all.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(?int $companyId, string $q, int $limit = 60): array
    {
        // Latest rack for a product+godown (from its most recent lot).
        $rackSub = '(SELECT l.rack FROM inv_lots l WHERE l.company_id = inv_stock.company_id'
            . ' AND l.product_id = inv_stock.product_id AND l.warehouse_id = inv_stock.warehouse_id'
            . ' AND l.rack IS NOT NULL AND l.deleted_at IS NULL ORDER BY l.id DESC LIMIT 1)';

        $b = $this->scopedList($companyId)->select($rackSub . ' AS rack', false);
        $q = trim($q);

        if ($q !== '') {
            // Products this party has dealt with (so a party name finds their stock).
            $partyProductIds = [];
            $pb = $this->db->table('inv_movements m')
                ->distinct()->select('m.product_id')
                ->join('inv_parties pt', 'pt.id = m.party_id', 'left')
                ->where('m.deleted_at', null)
                ->like('pt.name', $q);
            if ($companyId !== null) {
                $pb->where('m.company_id', $companyId);
            }
            foreach ($pb->get()->getResultArray() as $r) {
                $partyProductIds[] = (int) $r['product_id'];
            }

            $b->groupStart()
                ->like('p.name', $q)
                ->orLike('w.name', $q)
                ->orWhere('p.sku', $q)
                // Match a lot number → its product.
                ->orWhereIn('inv_stock.product_id', $this->productsForLot($companyId, $q) ?: [0]);
            if ($partyProductIds !== []) {
                $b->orWhereIn('inv_stock.product_id', $partyProductIds);
            }
            $b->groupEnd();
        }

        return $b->orderBy('p.name', 'ASC')->orderBy('w.name', 'ASC')->limit($limit)->get()->getResultArray();
    }

    /** Product ids whose lot number matches the term (for QR / lot search). */
    private function productsForLot(?int $companyId, string $q): array
    {
        $b = $this->db->table('inv_lots')->distinct()->select('product_id')->where('deleted_at', null)->like('lot_no', $q);
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return array_map(static fn ($r) => (int) $r['product_id'], $b->get()->getResultArray());
    }
}
