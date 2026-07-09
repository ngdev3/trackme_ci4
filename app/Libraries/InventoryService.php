<?php

namespace App\Libraries;

use App\Models\InvLotModel;
use App\Models\InvMovementModel;
use App\Models\InvProductModel;
use App\Models\InvStockModel;
use Config\Database;

/**
 * The single seam through which stock ever changes. Every inward, outward and
 * adjustment goes through here so that — atomically — the ledger row is written,
 * the fast stock balance is updated, lots/entry numbers are generated and an
 * audit log is recorded. Reused by the web controllers and the mobile REST API.
 */
class InventoryService
{
    private $db;
    private InvMovementModel $movements;
    private InvStockModel $stock;
    private InvLotModel $lots;
    private InvProductModel $products;

    public function __construct()
    {
        $this->db        = Database::connect();
        $this->movements = new InvMovementModel();
        $this->stock     = new InvStockModel();
        $this->lots      = new InvLotModel();
        $this->products  = new InvProductModel();
    }

    /** Weight to store: the given value, else product avg-weight × bags. */
    private function resolveWeight(int $companyId, int $productId, float $bags, ?float $weight): float
    {
        if ($weight !== null && $weight > 0) {
            return round($weight, 2);
        }
        $p = $this->products->find($productId);
        $avg = $p ? (float) ($p['avg_weight'] ?? 0) : 0.0;
        return round($avg * $bags, 2);
    }

    /**
     * Goods received. Creates a lot, writes the inward ledger row and bumps the
     * stock balance — all in one transaction.
     *
     * @param array $d company_id, product_id, warehouse_id, party_id?, bags,
     *                 weight?, rack?, notes?, photo?, source?, created_by?
     * @return array{movement_id:int, entry_no:string, lot_no:string, weight:float}
     */
    public function recordInward(array $d): array
    {
        $companyId   = (int) $d['company_id'];
        $productId   = (int) $d['product_id'];
        $warehouseId = (int) $d['warehouse_id'];
        $bags        = round((float) $d['bags'], 2);
        $weight      = $this->resolveWeight($companyId, $productId, $bags, isset($d['weight']) ? (float) $d['weight'] : null);

        $this->db->transStart();

        $lotNo = $this->lots->nextLotNo($companyId);
        $lotId = (int) $this->lots->insert([
            'company_id'     => $companyId,
            'lot_no'         => $lotNo,
            'product_id'     => $productId,
            'warehouse_id'   => $warehouseId,
            'party_id'       => $d['party_id'] ?? null,
            'rack'           => $d['rack'] ?? null,
            'opening_bags'   => $bags,
            'opening_weight' => $weight,
            'remaining_bags' => $bags,
            'created_by'     => $d['created_by'] ?? null,
        ]);

        $entryNo    = $this->movements->nextEntryNo($companyId, 'inward');
        $movementId = (int) $this->movements->insert([
            'company_id'    => $companyId,
            'entry_no'      => $entryNo,
            'movement_type' => 'inward',
            'direction'     => 1,
            'product_id'    => $productId,
            'warehouse_id'  => $warehouseId,
            'party_id'      => $d['party_id'] ?? null,
            'lot_id'        => $lotId,
            'bags'          => $bags,
            'weight'        => $weight,
            'rack'          => $d['rack'] ?? null,
            'notes'         => $d['notes'] ?? null,
            'photo'         => $d['photo'] ?? null,
            'source'        => $d['source'] ?? 'web',
            'created_by'    => $d['created_by'] ?? null,
        ]);

        $this->stock->applyDelta($companyId, $productId, $warehouseId, $bags, $weight);

        $this->db->transComplete();

        if (function_exists('activity_log')) {
            activity_log('Inventory', 'Add', "Inward {$entryNo} ({$lotNo}): +{$bags} bags");
        }

        return ['movement_id' => $movementId, 'entry_no' => $entryNo, 'lot_no' => $lotNo, 'weight' => $weight];
    }

    /**
     * Goods dispatched. Writes the outward ledger row and reduces the stock
     * balance (and lot remainders, oldest first). By default it refuses to let
     * stock go negative; pass allow_negative=true to permit it (the shortfall is
     * still recorded truthfully).
     *
     * @throws \RuntimeException when the dispatch would go negative and it is not allowed
     * @return array{movement_id:int, entry_no:string, weight:float, available:float}
     */
    public function recordOutward(array $d): array
    {
        $companyId   = (int) $d['company_id'];
        $productId   = (int) $d['product_id'];
        $warehouseId = (int) $d['warehouse_id'];
        $bags        = round((float) $d['bags'], 2);
        $allowNeg    = ! empty($d['allow_negative']);

        $available = $this->stock->bagsAt($companyId, $productId, $warehouseId);
        if ($bags > $available && ! $allowNeg) {
            throw new \RuntimeException((string) $available);
        }

        $weight = $this->resolveWeight($companyId, $productId, $bags, isset($d['weight']) ? (float) $d['weight'] : null);

        $this->db->transStart();

        $entryNo    = $this->movements->nextEntryNo($companyId, 'outward');
        $movementId = (int) $this->movements->insert([
            'company_id'    => $companyId,
            'entry_no'      => $entryNo,
            'movement_type' => 'outward',
            'direction'     => -1,
            'product_id'    => $productId,
            'warehouse_id'  => $warehouseId,
            'party_id'      => $d['party_id'] ?? null,
            'bags'          => $bags,
            'weight'        => $weight,
            'vehicle_no'    => $d['vehicle_no'] ?? null,
            'notes'         => $d['notes'] ?? null,
            'photo'         => $d['photo'] ?? null,
            'source'        => $d['source'] ?? 'web',
            'created_by'    => $d['created_by'] ?? null,
        ]);

        // Reduce the fast balance and draw down lots oldest-first (FIFO).
        $this->stock->applyDelta($companyId, $productId, $warehouseId, -$bags, -$weight);
        $this->drawDownLots($companyId, $productId, $warehouseId, $bags);

        $this->db->transComplete();

        if (function_exists('activity_log')) {
            activity_log('Inventory', 'Edit', "Outward {$entryNo}: -{$bags} bags");
        }

        return [
            'movement_id' => $movementId,
            'entry_no'    => $entryNo,
            'weight'      => $weight,
            'available'   => $this->stock->bagsAt($companyId, $productId, $warehouseId),
        ];
    }

    /** Reduce remaining bags across the oldest open lots (FIFO). Best effort. */
    private function drawDownLots(int $companyId, int $productId, int $warehouseId, float $bags): void
    {
        $lots = $this->lots->where('company_id', $companyId)->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)->where('remaining_bags >', 0)
            ->orderBy('id', 'ASC')->findAll();
        $left = $bags;
        foreach ($lots as $lot) {
            if ($left <= 0) {
                break;
            }
            $take = min($left, (float) $lot['remaining_bags']);
            $this->lots->update((int) $lot['id'], ['remaining_bags' => round((float) $lot['remaining_bags'] - $take, 2)]);
            $left -= $take;
        }
    }

    /**
     * Reconcile system stock to a counted physical figure. Writes an adjustment
     * ledger row (signed by whether stock went up or down) and moves the balance
     * by exactly the difference. Called only after an owner/admin approves a
     * correction request — never by a worker directly.
     *
     * @return array{movement_id:int, entry_no:string}
     */
    public function recordAdjustment(array $d): array
    {
        $companyId   = (int) $d['company_id'];
        $productId   = (int) $d['product_id'];
        $warehouseId = (int) $d['warehouse_id'];
        $delta       = round((float) $d['delta_bags'], 2);          // physical − system (signed)
        $direction   = $delta >= 0 ? 1 : -1;
        $bags        = abs($delta);
        $weight      = $this->resolveWeight($companyId, $productId, $bags, null) * ($delta >= 0 ? 1 : 1);

        $this->db->transStart();

        $entryNo    = $this->movements->nextEntryNo($companyId, 'adjustment');
        $movementId = (int) $this->movements->insert([
            'company_id'    => $companyId,
            'entry_no'      => $entryNo,
            'movement_type' => 'adjustment',
            'direction'     => $direction,
            'product_id'    => $productId,
            'warehouse_id'  => $warehouseId,
            'bags'          => $bags,
            'weight'        => $weight,
            'reason'        => $d['reason'] ?? null,
            'notes'         => $d['notes'] ?? null,
            'source'        => $d['source'] ?? 'web',
            'created_by'    => $d['created_by'] ?? null,
        ]);

        // Move the balance by the signed difference so system now equals physical.
        $this->stock->applyDelta($companyId, $productId, $warehouseId, $delta, $direction * $weight);

        $this->db->transComplete();

        if (function_exists('activity_log')) {
            $sign = $delta >= 0 ? '+' : '−';
            activity_log('Inventory', 'Edit', "Adjustment {$entryNo}: {$sign}{$bags} bags ({$d['reason']})");
        }

        return ['movement_id' => $movementId, 'entry_no' => $entryNo];
    }

    /** Bags currently available for a product+warehouse. */
    public function availableBags(int $companyId, int $productId, int $warehouseId): float
    {
        return $this->stock->bagsAt($companyId, $productId, $warehouseId);
    }
}
