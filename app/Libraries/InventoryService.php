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
        $movementId = (int) $this->movements->insert(array_filter([
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
            'rate'          => isset($d['rate']) ? round((float) $d['rate'], 2) : 0,
            'amount'        => isset($d['amount']) ? round((float) $d['amount'], 2) : 0,
            'rack'          => $d['rack'] ?? null,
            'notes'         => $d['notes'] ?? null,
            'photo'         => $d['photo'] ?? null,
            'source'        => $d['source'] ?? 'web',
            'created_by'    => $d['created_by'] ?? null,
            'created_at'    => $d['created_at'] ?? null,
        ], static fn ($v) => $v !== null));

        $this->stock->applyDelta($companyId, $productId, $warehouseId, $bags, $weight);

        $this->db->transComplete();

        $this->safeLog('Add', "Inward {$entryNo} ({$lotNo}): +{$bags} bags");

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
        $movementId = (int) $this->movements->insert(array_filter([
            'company_id'    => $companyId,
            'entry_no'      => $entryNo,
            'movement_type' => 'outward',
            'direction'     => -1,
            'product_id'    => $productId,
            'warehouse_id'  => $warehouseId,
            'party_id'      => $d['party_id'] ?? null,
            'bags'          => $bags,
            'weight'        => $weight,
            'rate'          => isset($d['rate']) ? round((float) $d['rate'], 2) : 0,
            'amount'        => isset($d['amount']) ? round((float) $d['amount'], 2) : 0,
            'vehicle_no'    => $d['vehicle_no'] ?? null,
            'notes'         => $d['notes'] ?? null,
            'photo'         => $d['photo'] ?? null,
            'source'        => $d['source'] ?? 'web',
            'created_by'    => $d['created_by'] ?? null,
            'created_at'    => $d['created_at'] ?? null,
        ], static fn ($v) => $v !== null));

        // Reduce the fast balance and draw down lots oldest-first (FIFO).
        $this->stock->applyDelta($companyId, $productId, $warehouseId, -$bags, -$weight);
        $this->drawDownLots($companyId, $productId, $warehouseId, $bags);

        $this->db->transComplete();

        $this->safeLog('Edit', "Outward {$entryNo}: -{$bags} bags");

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

        $sign = $delta >= 0 ? '+' : '−';
        $this->safeLog('Edit', "Adjustment {$entryNo}: {$sign}{$bags} bags ({$d['reason']})");

        return ['movement_id' => $movementId, 'entry_no' => $entryNo];
    }

    /**
     * Move stock between two godowns as ONE atomic pair of ledger rows — an
     * outward leg from the source and an inward leg to the destination — sharing a
     * link_group so the two halves are always reported together. Refuses to send
     * more than is on hand unless allow_negative is set. Never leaves a half-done
     * transfer: both legs commit, or neither does.
     *
     * @param array $d company_id, product_id, from_warehouse_id, to_warehouse_id,
     *                 bags, weight?, allow_negative?, notes?, source?, created_by?
     * @throws \InvalidArgumentException on bad godowns / bags
     * @throws \RuntimeException (message = available) when it would go negative
     * @return array{group:string, out_entry_no:string, in_entry_no:string, from_available:float, to_available:float}
     */
    public function recordTransfer(array $d): array
    {
        $companyId = (int) $d['company_id'];
        $productId = (int) $d['product_id'];
        $fromId    = (int) $d['from_warehouse_id'];
        $toId      = (int) $d['to_warehouse_id'];
        $bags      = round((float) $d['bags'], 2);
        $allowNeg  = ! empty($d['allow_negative']);

        if ($fromId === $toId || $fromId <= 0 || $toId <= 0) {
            throw new \InvalidArgumentException('Choose two different godowns.');
        }
        if ($bags <= 0) {
            throw new \InvalidArgumentException('Transfer bags must be greater than zero.');
        }
        $available = $this->stock->bagsAt($companyId, $productId, $fromId);
        if ($bags > $available && ! $allowNeg) {
            throw new \RuntimeException((string) $available);
        }

        $weight = $this->resolveWeight($companyId, $productId, $bags, isset($d['weight']) ? (float) $d['weight'] : null);
        $group  = 'TRF' . strtoupper(bin2hex(random_bytes(6)));

        $this->db->transStart();

        // OUT leg — reduce the source godown (and its lots, FIFO).
        $outNo = $this->movements->nextEntryNo($companyId, 'transfer');
        $this->movements->insert(array_filter([
            'company_id' => $companyId, 'entry_no' => $outNo, 'link_group' => $group,
            'movement_type' => 'transfer', 'direction' => -1,
            'product_id' => $productId, 'warehouse_id' => $fromId, 'to_warehouse_id' => $toId,
            'bags' => $bags, 'weight' => $weight,
            'notes' => $d['notes'] ?? null, 'source' => $d['source'] ?? 'web',
            'created_by' => $d['created_by'] ?? null, 'created_at' => $d['created_at'] ?? null,
        ], static fn ($v) => $v !== null));
        $this->stock->applyDelta($companyId, $productId, $fromId, -$bags, -$weight);
        $this->drawDownLots($companyId, $productId, $fromId, $bags);

        // IN leg — a fresh lot at the receiving godown, and bump its balance.
        $lotNo = $this->lots->nextLotNo($companyId);
        $lotId = (int) $this->lots->insert([
            'company_id' => $companyId, 'lot_no' => $lotNo, 'product_id' => $productId,
            'warehouse_id' => $toId, 'opening_bags' => $bags, 'opening_weight' => $weight,
            'remaining_bags' => $bags, 'created_by' => $d['created_by'] ?? null,
        ]);
        $inNo = $this->movements->nextEntryNo($companyId, 'transfer');
        $this->movements->insert(array_filter([
            'company_id' => $companyId, 'entry_no' => $inNo, 'link_group' => $group,
            'movement_type' => 'transfer', 'direction' => 1,
            'product_id' => $productId, 'warehouse_id' => $toId, 'lot_id' => $lotId,
            'bags' => $bags, 'weight' => $weight,
            'notes' => $d['notes'] ?? null, 'source' => $d['source'] ?? 'web',
            'created_by' => $d['created_by'] ?? null, 'created_at' => $d['created_at'] ?? null,
        ], static fn ($v) => $v !== null));
        $this->stock->applyDelta($companyId, $productId, $toId, $bags, $weight);

        $this->db->transComplete();

        $this->safeLog('Edit', "Transfer {$group}: {$bags} bags moved between godowns");

        return [
            'group'          => $group,
            'out_entry_no'   => $outNo,
            'in_entry_no'    => $inNo,
            'from_available' => $this->stock->bagsAt($companyId, $productId, $fromId),
            'to_available'   => $this->stock->bagsAt($companyId, $productId, $toId),
        ];
    }

    /**
     * Convert one raw input into one or more finished outputs (e.g. Paddy →
     * Rice + Bran + Husk, with wastage) as a SINGLE atomic batch. The input is
     * consumed (outward) and each output is produced (inward, its own lot), all
     * sharing a link_group. Wastage is recorded on the input row only — it is
     * never added to stock — so the ledger↔balance identity is preserved:
     * consumed = produced + wastage.
     *
     * @param array $d company_id, input{product_id,warehouse_id,bags,weight?},
     *                 outputs[{product_id,bags,warehouse_id?,weight?}], wastage_bags?,
     *                 allow_negative?, notes?, source?, created_by?
     * @throws \InvalidArgumentException on bad input / no outputs
     * @throws \RuntimeException (message = available) when input would go negative
     * @return array{group:string, input_entry_no:string, outputs:array, wastage_bags:float}
     */
    public function recordProduction(array $d): array
    {
        $companyId = (int) $d['company_id'];
        $input     = (array) ($d['input'] ?? []);
        $inProduct = (int) ($input['product_id'] ?? 0);
        $inWh      = (int) ($input['warehouse_id'] ?? 0);
        $inBags    = round((float) ($input['bags'] ?? 0), 2);
        $wastage   = round((float) ($d['wastage_bags'] ?? 0), 2);
        $allowNeg  = ! empty($d['allow_negative']);
        $outputs   = array_values(array_filter((array) ($d['outputs'] ?? []), static fn ($o) => (int) ($o['product_id'] ?? 0) > 0 && (float) ($o['bags'] ?? 0) > 0));

        if ($inProduct <= 0 || $inWh <= 0 || $inBags <= 0) {
            throw new \InvalidArgumentException('A valid input product, godown and quantity are required.');
        }
        if ($outputs === []) {
            throw new \InvalidArgumentException('Add at least one output product.');
        }
        $available = $this->stock->bagsAt($companyId, $inProduct, $inWh);
        if ($inBags > $available && ! $allowNeg) {
            throw new \RuntimeException((string) $available);
        }

        $inWeight = $this->resolveWeight($companyId, $inProduct, $inBags, isset($input['weight']) ? (float) $input['weight'] : null);
        $group    = 'PRD' . strtoupper(bin2hex(random_bytes(6)));

        $this->db->transStart();

        // Consume the raw material (records wastage on this row for reporting).
        $inNo = $this->movements->nextEntryNo($companyId, 'production');
        $this->movements->insert(array_filter([
            'company_id' => $companyId, 'entry_no' => $inNo, 'link_group' => $group,
            'movement_type' => 'production', 'direction' => -1,
            'product_id' => $inProduct, 'warehouse_id' => $inWh,
            'bags' => $inBags, 'wastage_bags' => $wastage, 'weight' => $inWeight,
            'notes' => $d['notes'] ?? null, 'source' => $d['source'] ?? 'web',
            'created_by' => $d['created_by'] ?? null, 'created_at' => $d['created_at'] ?? null,
        ], static fn ($v) => $v !== null));
        $this->stock->applyDelta($companyId, $inProduct, $inWh, -$inBags, -$inWeight);
        $this->drawDownLots($companyId, $inProduct, $inWh, $inBags);

        // Produce each finished good into a fresh lot at its (default: input) godown.
        $outEntries = [];
        foreach ($outputs as $o) {
            $op      = (int) $o['product_id'];
            $ob      = round((float) $o['bags'], 2);
            $ow      = isset($o['warehouse_id']) && (int) $o['warehouse_id'] > 0 ? (int) $o['warehouse_id'] : $inWh;
            $oweight = $this->resolveWeight($companyId, $op, $ob, isset($o['weight']) ? (float) $o['weight'] : null);

            $lotNo = $this->lots->nextLotNo($companyId);
            $lotId = (int) $this->lots->insert([
                'company_id' => $companyId, 'lot_no' => $lotNo, 'product_id' => $op,
                'warehouse_id' => $ow, 'opening_bags' => $ob, 'opening_weight' => $oweight,
                'remaining_bags' => $ob, 'created_by' => $d['created_by'] ?? null,
            ]);
            $oNo = $this->movements->nextEntryNo($companyId, 'production');
            $this->movements->insert(array_filter([
                'company_id' => $companyId, 'entry_no' => $oNo, 'link_group' => $group,
                'movement_type' => 'production', 'direction' => 1,
                'product_id' => $op, 'warehouse_id' => $ow, 'lot_id' => $lotId,
                'bags' => $ob, 'weight' => $oweight,
                'source' => $d['source'] ?? 'web', 'created_by' => $d['created_by'] ?? null,
                'created_at' => $d['created_at'] ?? null,
            ], static fn ($v) => $v !== null));
            $this->stock->applyDelta($companyId, $op, $ow, $ob, $oweight);
            $outEntries[] = ['entry_no' => $oNo, 'product_id' => $op, 'bags' => $ob];
        }

        $this->db->transComplete();

        $this->safeLog('Add', "Production {$group}: −{$inBags} input, " . count($outEntries) . " outputs, {$wastage} wastage");

        return ['group' => $group, 'input_entry_no' => $inNo, 'outputs' => $outEntries, 'wastage_bags' => $wastage];
    }

    /** Bags currently available for a product+warehouse. */
    public function availableBags(int $companyId, int $productId, int $warehouseId): float
    {
        return $this->stock->bagsAt($companyId, $productId, $warehouseId);
    }

    /**
     * Best-effort audit log. Never lets a logging failure bubble up after the
     * stock write has already committed — the movement is the source of truth.
     */
    private function safeLog(string $action, string $message): void
    {
        if (! function_exists('activity_log')) {
            return;
        }
        try {
            activity_log('Inventory', $action, $message);
        } catch (\Throwable $e) {
            log_message('error', 'Inventory activity_log failed: ' . $e->getMessage());
        }
    }
}
