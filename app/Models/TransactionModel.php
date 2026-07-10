<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Jama (money received) / Naam (money paid) transaction register.
 *
 * Per-company ledger: each company keeps its own Hisaab-Kitaab Vahi, scoped by
 * company_id, so switching the active company shows a different book. A null
 * scope (used by the Super Admin) sees every company's rows. All balances are
 * derived from the raw rows so they are always correct after any edit/delete.
 * (user_id is still stored as the row's author, but is not the query scope.)
 */
class TransactionModel extends Model
{
    protected $table          = 'transactions';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;

    protected $allowedFields = ['user_id', 'company_id', 'txn_no', 'txn_date', 'name', 'type', 'amount', 'payment_mode', 'source', 'status', 'notes', 'delete_reason'];

    protected $validationRules = [
        'txn_date' => 'required|valid_date[Y-m-d]',
        'name'     => 'required|min_length[1]|max_length[191]',
        'type'     => 'in_list[jama,naam]',
        'amount'   => 'required|numeric|greater_than[0]|less_than_equal_to[9999999999.99]',
        'status'   => 'in_list[paid,pending,overdue,cancelled,draft]',
    ];

    protected $validationMessages = [
        'amount' => [
            'greater_than'        => 'Amount must be more than 0.',
            'less_than_equal_to'  => 'Amount is too large — please check the value (max ₹9,99,99,99,999.99).',
        ],
    ];

    /** Hard ceiling for a single entry — guards fat-finger amounts (F-7). Fits DECIMAL(15,2). */
    public const MAX_AMOUNT = 9999999999.99;

    public const TYPES    = ['jama', 'naam'];
    public const STATUSES = ['paid', 'pending', 'overdue', 'cancelled', 'draft'];
    public const MODES    = ['cash', 'bank', 'upi', 'cheque', 'card', 'other'];

    /** Human labels. */
    public const TYPE_LABELS = ['jama' => 'Jama (Received)', 'naam' => 'Naam (Paid)'];
    public const MODE_LABELS = [
        'cash'   => 'Cash',
        'bank'   => 'Bank Transfer',
        'upi'    => 'UPI',
        'cheque' => 'Cheque',
        'card'   => 'Card',
        'other'  => 'Other',
    ];

    // -----------------------------------------------------------------
    // Scope helpers
    // -----------------------------------------------------------------

    /** Apply the per-company scope. A null $companyId means "all companies" (Super Admin). */
    private function scoped(?int $companyId)
    {
        $b = $this->where('deleted_at', null);
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return $b;
    }

    private function scopedBuilder(?int $companyId)
    {
        $b = $this->builder()->where('deleted_at', null);
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return $b;
    }

    /** Find one row, honouring the company scope (null = any company). */
    public function findScoped(int $id, ?int $companyId): ?array
    {
        $row = $this->find($id);
        if (! $row) {
            return null;
        }
        if ($companyId !== null && (int) $row['company_id'] !== $companyId) {
            return null;
        }
        return $row;
    }

    // -----------------------------------------------------------------
    // Transaction number
    // -----------------------------------------------------------------

    /**
     * Next sequential transaction number for a company, e.g. TXN-000123.
     * Derived from the highest existing number so gaps never collide, and so
     * each company's book keeps its own running sequence.
     */
    public function nextTxnNo(int $companyId): string
    {
        $row = $this->builder()
            ->select('txn_no')
            ->where('company_id', $companyId)
            ->where('txn_no IS NOT NULL')
            ->orderBy('id', 'DESC')
            ->limit(200)
            ->get()->getResultArray();

        $max = 0;
        foreach ($row as $r) {
            if (preg_match('/(\d+)\s*$/', (string) $r['txn_no'], $m)) {
                $max = max($max, (int) $m[1]);
            }
        }
        return 'TXN-' . str_pad((string) ($max + 1), 6, '0', STR_PAD_LEFT);
    }

    // -----------------------------------------------------------------
    // Filters + listing
    // -----------------------------------------------------------------

    /** Apply search + date range + status/type/mode filters to a builder. */
    private function applyFilters($b, array $f)
    {
        if (($f['q'] ?? '') !== '') {
            $b->groupStart()
                ->like('name', $f['q'])
                ->orLike('notes', $f['q'])
                ->orLike('txn_no', $f['q'])
                ->groupEnd();
        }
        if (($f['from'] ?? '') !== '') {
            $b->where('txn_date >=', $f['from']);
        }
        if (($f['to'] ?? '') !== '') {
            $b->where('txn_date <=', $f['to']);
        }
        if (in_array($f['status'] ?? '', self::STATUSES, true)) {
            $b->where('status', $f['status']);
        }
        if (in_array($f['type'] ?? '', self::TYPES, true)) {
            $b->where('type', $f['type']);
        }
        if (in_array($f['mode'] ?? '', self::MODES, true)) {
            $b->where('payment_mode', $f['mode']);
        }
        return $b;
    }

    /** One page of filtered rows, newest first. */
    public function page(?int $userId, array $f, int $per): array
    {
        $b = $this->applyFilters($this->scoped($userId), $f);
        return $b->orderBy('txn_date', 'DESC')->orderBy('id', 'DESC')->paginate($per);
    }

    /** All filtered rows (no pagination) — used for exports. Newest first. */
    public function allFiltered(?int $userId, array $f): array
    {
        $b = $this->applyFilters($this->scoped($userId), $f);
        return $b->orderBy('txn_date', 'DESC')->orderBy('id', 'DESC')->findAll();
    }

    /** Filtered rows with an explicit limit, used by memory-sensitive PDF exports. */
    public function limitedFiltered(?int $userId, array $f, int $limit, int $offset = 0): array
    {
        $limit  = max(1, $limit);
        $offset = max(0, $offset);

        $b = $this->applyFilters($this->scoped($userId), $f);
        return $b->orderBy('txn_date', 'DESC')->orderBy('id', 'DESC')->findAll($limit, $offset);
    }

    /** Totals for the filtered set: jama, naam, net (jama−naam), count. */
    public function summary(?int $userId, array $f): array
    {
        $b = $this->applyFilters($this->scopedBuilder($userId), $f);
        $row = $b->select(
            "COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS jama,"
            . "COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS naam,"
            . 'COUNT(*) AS cnt'
        )->get()->getRowArray();

        $jama = (float) ($row['jama'] ?? 0);
        $naam = (float) ($row['naam'] ?? 0);
        return ['jama' => $jama, 'naam' => $naam, 'net' => $jama - $naam, 'count' => (int) ($row['cnt'] ?? 0)];
    }

    /**
     * Running balance (jama − naam) of the filtered set up to and including a
     * given row, so the newest visible row shows the current balance.
     */
    public function balanceUpTo(?int $userId, array $f, string $date, int $id): float
    {
        $b = $this->applyFilters($this->scopedBuilder($userId), $f);
        $b->groupStart()
            ->where('txn_date <', $date)
            ->orGroupStart()->where('txn_date', $date)->where('id <=', $id)->groupEnd()
            ->groupEnd();
        $row = $b->select("COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE -amount END),0) AS net")->get()->getRowArray();
        return (float) ($row['net'] ?? 0);
    }

    // -----------------------------------------------------------------
    // Rokadh Parcha — period reporting
    // -----------------------------------------------------------------

    /** Whether any (non-deleted) row exists strictly before $date. */
    public function hasBefore(?int $userId, string $date): bool
    {
        return $this->scopedBuilder($userId)->where('txn_date <', $date)->countAllResults() > 0;
    }

    /** Earliest transaction date in scope, or null when the book is empty. */
    public function earliestDate(?int $userId): ?string
    {
        $row = $this->scopedBuilder($userId)
            ->select('MIN(txn_date) AS d')->get()->getRowArray();
        return ($row['d'] ?? null) ?: null;
    }

    /** Net (jama − naam) of all rows strictly before $date (optionally on/after $since). */
    public function netBefore(?int $userId, string $date, ?string $since = null): float
    {
        $b = $this->scopedBuilder($userId)->where('txn_date <', $date);
        if ($since) {
            $b->where('txn_date >=', $since);
        }
        $row = $b->select("COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE -amount END),0) AS net")->get()->getRowArray();
        return (float) ($row['net'] ?? 0);
    }

    /** All rows within an inclusive date range, oldest first (for a cash-book run). */
    public function rangeRows(?int $userId, string $from, string $to): array
    {
        return $this->scoped($userId)
            ->where('txn_date >=', $from)->where('txn_date <=', $to)
            ->orderBy('txn_date', 'ASC')->orderBy('id', 'ASC')
            ->findAll();
    }

    /** All rows on a single date, oldest first (for the daily Rokad Parcha). */
    public function dayEntries(?int $userId, string $date): array
    {
        return $this->scoped($userId)
            ->where('txn_date', $date)
            ->orderBy('id', 'ASC')
            ->findAll();
    }

    /** Soft-deleted rows on a single date (for the "Deleted Entries" view). */
    public function deletedOnDate(?int $companyId, string $date): array
    {
        $b = $this->withDeleted()->where('txn_date', $date)->where('deleted_at IS NOT NULL');
        if ($companyId !== null) {
            $b->where('company_id', $companyId);
        }
        return $b->orderBy('id', 'DESC')->findAll();
    }

    /** [jama, naam] totals within an inclusive date range. */
    public function rangeTotals(?int $userId, string $from, string $to): array
    {
        $row = $this->scopedBuilder($userId)
            ->where('txn_date >=', $from)->where('txn_date <=', $to)
            ->select("COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS j, COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS n")
            ->get()->getRowArray();
        return [(float) ($row['j'] ?? 0), (float) ($row['n'] ?? 0)];
    }

    /**
     * Daily jama/naam buckets within a range (for charts / grouped reports).
     *
     * @return array<int, array{d:string, jama:float, naam:float}>
     */
    public function dailyBuckets(?int $userId, string $from, string $to): array
    {
        $rows = $this->scopedBuilder($userId)
            ->where('txn_date >=', $from)->where('txn_date <=', $to)
            ->select("txn_date AS d, COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS jama, COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS naam")
            ->groupBy('txn_date')->orderBy('txn_date', 'ASC')
            ->get()->getResultArray();
        return array_map(static fn ($r) => [
            'd'    => $r['d'],
            'jama' => (float) $r['jama'],
            'naam' => (float) $r['naam'],
        ], $rows);
    }

    /** Jama/naam totals grouped by payment mode (for the dashboard chart). */
    public function byMode(?int $userId, array $f): array
    {
        $b = $this->applyFilters($this->scopedBuilder($userId), $f);
        $rows = $b->select("payment_mode AS mode, COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS jama, COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS naam")
            ->groupBy('payment_mode')->get()->getResultArray();
        $out = [];
        foreach ($rows as $r) {
            $out[$r['mode'] ?: 'other'] = ['jama' => (float) $r['jama'], 'naam' => (float) $r['naam']];
        }
        return $out;
    }

    // -----------------------------------------------------------------
    // Account (party) statement
    // -----------------------------------------------------------------

    /**
     * Distinct parties (accounts) with their totals — powers the statement
     * search picker and the browse-accounts directory. Most active first.
     *
     * @return array<int, array{name:string, count:int, jama:float, naam:float, net:float, last_date:?string}>
     */
    public function partyDirectory(?int $userId, int $limit = 500): array
    {
        $rows = $this->scopedBuilder($userId)
            ->select("name,"
                . 'COUNT(*) AS cnt,'
                . "COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS jama,"
                . "COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS naam,"
                . 'MAX(txn_date) AS last_date')
            ->where('name IS NOT NULL')->where('name !=', '')
            ->groupBy('name')
            ->orderBy('cnt', 'DESC')->orderBy('name', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();

        return array_map(static fn ($r) => [
            'name'      => (string) $r['name'],
            'count'     => (int) $r['cnt'],
            'jama'      => (float) $r['jama'],
            'naam'      => (float) $r['naam'],
            'net'       => (float) $r['jama'] - (float) $r['naam'],
            'last_date' => $r['last_date'] ?: null,
        ], $rows);
    }

    /** All rows for one party (account), oldest first, optional date range. */
    public function partyRows(?int $userId, string $name, ?string $from = null, ?string $to = null): array
    {
        $b = $this->scoped($userId)->where('name', $name);
        if ($from) {
            $b->where('txn_date >=', $from);
        }
        if ($to) {
            $b->where('txn_date <=', $to);
        }
        return $b->orderBy('txn_date', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /** Running (jama−naam) balance for a party strictly before a date (opening). */
    public function partyNetBefore(?int $userId, string $name, string $date): float
    {
        $row = $this->scopedBuilder($userId)->where('name', $name)->where('txn_date <', $date)
            ->select("COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE -amount END),0) AS net")
            ->get()->getRowArray();
        return (float) ($row['net'] ?? 0);
    }

    /** [jama, naam] totals for a party within an optional date range. */
    public function partyTotals(?int $userId, string $name, ?string $from = null, ?string $to = null): array
    {
        $b = $this->scopedBuilder($userId)->where('name', $name);
        if ($from) {
            $b->where('txn_date >=', $from);
        }
        if ($to) {
            $b->where('txn_date <=', $to);
        }
        $row = $b->select("COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS j,"
                . "COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS n")
            ->get()->getRowArray();
        return [(float) ($row['j'] ?? 0), (float) ($row['n'] ?? 0)];
    }
}
