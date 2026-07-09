<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * The inventory ledger — one row per inward / outward / adjustment. The single
 * source of truth for stock; the inv_stock balance is derived from these rows.
 * Company-scoped.
 */
class InvMovementModel extends Model
{
    protected $table          = 'inv_movements';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'company_id', 'entry_no', 'movement_type', 'direction', 'product_id', 'warehouse_id',
        'party_id', 'lot_id', 'bags', 'weight', 'rate', 'amount', 'rack', 'vehicle_no', 'reason', 'notes',
        'photo', 'source', 'day_closed', 'created_by', 'created_at',
    ];

    public const TYPES = ['inward', 'outward', 'adjustment'];

    /** Next sequential entry number per type, e.g. INW-000123 / OUT-000123 / ADJ-000123. */
    public function nextEntryNo(int $companyId, string $type): string
    {
        $prefix = ['inward' => 'INW', 'outward' => 'OUT', 'adjustment' => 'ADJ'][$type] ?? 'MOV';
        $rows   = $this->withDeleted()->builder()
            ->select('entry_no')->where('company_id', $companyId)->where('movement_type', $type)
            ->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
        $max = 0;
        foreach ($rows as $r) {
            if (preg_match('/(\d+)\s*$/', (string) $r['entry_no'], $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return $prefix . '-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    /** Scoped builder joined to the master names (for listings). */
    public function scopedList(?int $companyId)
    {
        $b = $this->select('inv_movements.*, p.name AS product_name, w.name AS warehouse_name, pt.name AS party_name')
            ->join('inv_products p', 'p.id = inv_movements.product_id', 'left')
            ->join('inv_warehouses w', 'w.id = inv_movements.warehouse_id', 'left')
            ->join('inv_parties pt', 'pt.id = inv_movements.party_id', 'left')
            ->where('inv_movements.deleted_at', null);
        if ($companyId !== null) {
            $b->where('inv_movements.company_id', $companyId);
        }
        return $b;
    }

    public function findForCompany(int $id, ?int $companyId): ?array
    {
        $row = $this->find($id);
        if (! $row) {
            return null;
        }
        return ($companyId === null || (int) $row['company_id'] === $companyId) ? $row : null;
    }
}
