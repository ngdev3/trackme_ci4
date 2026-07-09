<?php

namespace App\Models;

use CodeIgniter\Model;

class ReminderModel extends Model
{
    protected $table          = 'reminders';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = [
        'user_id', 'company_id', 'title', 'description', 'remind_at', 'priority', 'status',
        'repeat_type', 'repeat_interval', 'repeat_until', 'snoozed_until',
        'completed_at', 'notified', 'last_notified_at', 'attach_module', 'attach_ref',
    ];

    protected $validationRules = [
        'title'     => 'required|min_length[1]|max_length[191]',
        'remind_at' => 'required',
        'priority'  => 'in_list[low,medium,high]',
    ];

    public const PRIORITIES = ['low', 'medium', 'high'];
    public const REPEATS    = ['none', 'daily', 'weekly', 'monthly', 'yearly', 'custom'];

    /** Effective due time = snooze overrides the original time. */
    private const EFFECTIVE = 'COALESCE(snoozed_until, remind_at)';

    /**
     * A safely-escaped raw condition on the effective due time. Built as a raw
     * string (not `where(key, value)`) because CI4 escapes a function-valued
     * key as an identifier, which breaks COALESCE(...).
     */
    public function effectiveCond(string $op, string $value): string
    {
        return self::EFFECTIVE . ' ' . $op . ' ' . $this->db->escape($value);
    }

    /**
     * Company-scoped query builder. Reminders are shared across all members of a
     * company; `user_id` records the author only.
     */
    public function scoped(?int $companyId)
    {
        return $this->where('company_id', $companyId);
    }

    public function findForCompany(int $id, ?int $companyId): ?array
    {
        return $this->where('company_id', $companyId)->find($id) ?: null;
    }

    /** Pending reminders whose effective time falls within today. */
    public function today(?int $companyId): array
    {
        return $this->where('company_id', $companyId)->where('status', 'pending')
            ->where($this->effectiveCond('>=', date('Y-m-d 00:00:00')), null, false)
            ->where($this->effectiveCond('<=', date('Y-m-d 23:59:59')), null, false)
            ->orderBy(self::EFFECTIVE, 'ASC', false)->findAll();
    }

    /** Pending reminders due after today. */
    public function upcoming(?int $companyId, int $limit = 0): array
    {
        $b = $this->where('company_id', $companyId)->where('status', 'pending')
            ->where($this->effectiveCond('>', date('Y-m-d 23:59:59')), null, false)
            ->orderBy(self::EFFECTIVE, 'ASC', false);
        return $limit > 0 ? $b->findAll($limit) : $b->findAll();
    }

    /** Pending reminders whose effective time is already in the past. */
    public function overdue(?int $companyId): array
    {
        return $this->where('company_id', $companyId)->where('status', 'pending')
            ->where($this->effectiveCond('<', date('Y-m-d H:i:s')), null, false)
            ->orderBy(self::EFFECTIVE, 'ASC', false)->findAll();
    }

    /**
     * Reminders that just became due and have not yet been announced, across a
     * whole company (any member's page load may fire them).
     */
    public function dueForNotification(?int $companyId): array
    {
        return $this->where('company_id', $companyId)->where('status', 'pending')
            ->where('notified', 0)
            ->where($this->effectiveCond('<=', date('Y-m-d H:i:s')), null, false)
            ->orderBy(self::EFFECTIVE, 'ASC', false)->findAll();
    }

    /**
     * Derived display status for a single row (overdue is computed, not stored).
     */
    public function displayStatus(array $r): string
    {
        if (($r['status'] ?? '') === 'completed') {
            return 'completed';
        }
        $effective = ! empty($r['snoozed_until']) ? $r['snoozed_until'] : $r['remind_at'];
        return strtotime($effective) < time() ? 'overdue' : 'pending';
    }

    /**
     * Next occurrence datetime for a repeating reminder, or null when the series
     * has ended (past repeat_until or non-repeating).
     */
    public function nextOccurrence(string $from, string $type, ?int $interval, ?string $until): ?string
    {
        if (! in_array($type, ['daily', 'weekly', 'monthly', 'yearly', 'custom'], true)) {
            return null;
        }
        $date = new \DateTime($from);
        switch ($type) {
            case 'daily':   $date->modify('+1 day');   break;
            case 'weekly':  $date->modify('+1 week');  break;
            case 'monthly': $date->modify('+1 month'); break;
            case 'yearly':  $date->modify('+1 year');  break;
            case 'custom':  $date->modify('+' . max(1, (int) $interval) . ' days'); break;
        }
        if ($until && $date->format('Y-m-d') > $until) {
            return null;
        }
        return $date->format('Y-m-d H:i:s');
    }
}
