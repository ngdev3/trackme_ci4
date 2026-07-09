<?php

namespace App\Models;

use CodeIgniter\Model;

/** A received batch (lot) with an auto lot number — company-scoped. */
class InvLotModel extends Model
{
    protected $table          = 'inv_lots';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'company_id', 'lot_no', 'product_id', 'warehouse_id', 'party_id', 'rack',
        'opening_bags', 'opening_weight', 'remaining_bags', 'created_by',
    ];

    /** Next sequential lot number for a company, e.g. LOT-000123. */
    public function nextLotNo(int $companyId): string
    {
        $row = $this->withDeleted()->builder()
            ->select('lot_no')->where('company_id', $companyId)
            ->orderBy('id', 'DESC')->limit(200)->get()->getResultArray();
        $max = 0;
        foreach ($row as $r) {
            if (preg_match('/(\d+)\s*$/', (string) $r['lot_no'], $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return 'LOT-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }
}
