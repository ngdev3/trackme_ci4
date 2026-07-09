<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Daily Closing snapshots (Task 7). One row per company per day. A day is
 * "locked" when a row exists for it with status = 'closed'; reopening flips the
 * status to 'reopened' so workers may enter/edit again. Company-scoped.
 */
class InvDailyClosingModel extends Model
{
    protected $table         = 'inv_daily_closings';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'company_id', 'closing_date', 'opening_bags', 'received_bags', 'dispatched_bags',
        'adjustment_bags', 'closing_bags', 'received_weight', 'dispatched_weight',
        'difference_bags', 'pending_corrections', 'entry_count', 'status', 'notes',
        'closed_by', 'closed_at', 'reopened_by', 'reopened_at',
    ];

    /** The closing row for a company on a date, or null. */
    public function forDate(?int $companyId, string $date): ?array
    {
        $b = $this->where('closing_date', $date);
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return $b->first();
    }

    /** Is the given date locked (closed and not reopened) for this company? */
    public function isLocked(?int $companyId, string $date): bool
    {
        $row = $this->forDate($companyId, $date);
        return $row !== null && $row['status'] === 'closed';
    }

    /** Recent closings for the history list. */
    public function recent(?int $companyId, int $limit = 60): array
    {
        $b = $this->orderBy('closing_date', 'DESC');
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return $b->findAll($limit);
    }
}
