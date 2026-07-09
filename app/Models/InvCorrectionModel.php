<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Physical-verification correction requests. Workers create them; owners/admins
 * approve or reject. Approval generates the adjustment ledger entry. Company-scoped.
 */
class InvCorrectionModel extends Model
{
    protected $table          = 'inv_corrections';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = [
        'company_id', 'product_id', 'warehouse_id', 'system_bags', 'physical_bags', 'difference',
        'reason', 'note', 'status', 'movement_id', 'requested_by', 'reviewed_by', 'review_note', 'reviewed_at',
    ];

    /** Reason codes offered when there is a difference. */
    public const REASONS = [
        'moisture_loss' => 'Moisture Loss',
        'damaged'       => 'Damaged',
        'wrong_entry'   => 'Wrong Entry',
        'missing_stock' => 'Missing Stock',
        'unknown'       => 'Unknown Reason',
    ];

    /** Scoped list joined to product/godown + requester names. */
    public function scopedList(?int $companyId)
    {
        $b = $this->select('inv_corrections.*, p.name AS product_name, w.name AS warehouse_name, u.name AS requested_name')
            ->join('inv_products p', 'p.id = inv_corrections.product_id', 'left')
            ->join('inv_warehouses w', 'w.id = inv_corrections.warehouse_id', 'left')
            ->join('users u', 'u.id = inv_corrections.requested_by', 'left')
            ->where('inv_corrections.deleted_at', null);
        if ($companyId !== null) {
            $b->where('inv_corrections.company_id', $companyId);
        }
        return $b;
    }

    public function pendingCount(?int $companyId): int
    {
        $b = $this->where('status', 'pending')->where('deleted_at', null);
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return $b->countAllResults();
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
