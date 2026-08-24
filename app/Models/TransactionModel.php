<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

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

    protected $allowedFields = ['user_id', 'company_id', 'client_uuid', 'txn_no', 'txn_date', 'name', 'party_type', 'type', 'amount', 'payment_mode', 'source', 'status', 'notes', 'delete_reason'];

    // Any write invalidates the cached firm dashboard for that company, so a new
    // entry / edit / delete shows up immediately instead of after the cache TTL.
    protected $afterInsert = ['bustDashboardCache'];
    protected $afterUpdate = ['bustDashboardCache'];
    protected $afterDelete = ['bustDashboardCache'];

    /** Bust the dashboard cache for the affected company (or the active firm). */
    protected function bustDashboardCache(array $eventData): array
    {
        if (function_exists('dash_bust')) {
            $cid = $eventData['data']['company_id'] ?? null;
            if ($cid === null && function_exists('company_id')) {
                $cid = company_id(); // updates/deletes rarely carry company_id — use the active firm
            }
            dash_bust($cid !== null ? (int) $cid : null);
        }
        return $eventData;
    }

    protected $validationRules = [
        'txn_date'   => 'required|valid_date[Y-m-d]',
        'name'       => 'required|min_length[1]|max_length[191]',
        'type'       => 'in_list[jama,naam]',
        'amount'     => 'required|numeric|greater_than[0]|less_than_equal_to[9999999999.99]',
        'status'     => 'in_list[paid,received,pending,overdue,cancelled,draft]',
        'party_type' => 'permit_empty|max_length[32]',
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
    public const STATUSES = ['paid', 'received', 'pending', 'overdue', 'cancelled', 'draft'];
    public const MODES    = ['cash', 'bank', 'upi', 'cheque', 'card', 'other'];

    /** Starting suggestions for "who is this?" — the field is free text, so a company can add its own. */
    public const PARTY_TYPES = ['Farmer', 'Firm', 'Trader', 'Transporter', 'Labour', 'Other'];

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

    /**
     * Count of non-deleted entries per company, for the given company ids.
     *
     * @param  list<int> $companyIds
     * @return array<int, int> company_id → entry count
     */
    public function countsByCompany(array $companyIds): array
    {
        if ($companyIds === []) {
            return [];
        }
        // Sanitise to ints and force the (company_id, deleted_at) covering index.
        // With a multi-value IN() list the optimiser otherwise mis-picks the
        // deleted_at-leading dashboard index and scans ~half the table (58s on a
        // large book); FORCE INDEX keeps it an index-only company scan (~0.6s).
        $ids = array_values(array_filter(array_map('intval', $companyIds)));
        if ($ids === []) {
            return [];
        }
        $inList = implode(',', $ids);
        try {
            $rows = $this->db->query(
                "SELECT company_id, COUNT(*) AS cnt
                   FROM {$this->table} FORCE INDEX (idx_txn_company_deleted)
                  WHERE deleted_at IS NULL AND company_id IN ({$inList})
                  GROUP BY company_id"
            )->getResultArray();
        } catch (\Throwable $e) {
            // Index not present yet (migration not run) — fall back to the plain
            // grouped count so the endpoint still works, just without the hint.
            $rows = $this->builder()
                ->select('company_id, COUNT(*) AS cnt')
                ->where('deleted_at', null)
                ->whereIn('company_id', $ids)
                ->groupBy('company_id')
                ->get()->getResultArray();
        }
        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r['company_id']] = (int) $r['cnt'];
        }
        return $out;
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

    /** [jama, naam] totals within an inclusive date range, optionally narrowed by party type / tag. */
    public function rangeTotals(?int $userId, string $from, string $to, array $f = []): array
    {
        $b = $this->scopedBuilder($userId)->where('txn_date >=', $from)->where('txn_date <=', $to);
        self::applyClassFilters($b, '', $f);

        $row = $b->select("COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS j, COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS n")
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

    /** Columns the breakdown report may group by. Anything else is a programming error. */
    public const GROUPABLE = ['party_type', 'payment_mode', 'name'];

    /** Filter sentinel meaning "the value is not set" — e.g. an entry with no party type. */
    public const UNSET_VALUE = '__none';

    /**
     * Narrow a builder to the report's party-type filter.
     * $alias is the transactions table's alias ('' when the table is unaliased).
     *
     * @param array{ptype?:string} $f
     */
    public static function applyClassFilters($b, string $alias, array $f)
    {
        $col = $alias === '' ? '' : $alias . '.';

        $ptype = trim((string) ($f['ptype'] ?? ''));
        if ($ptype === self::UNSET_VALUE) {
            $b->groupStart()->where($col . 'party_type', null)->orWhere($col . 'party_type', '')->groupEnd();
        } elseif ($ptype !== '') {
            $b->where($col . 'party_type', $ptype);
        }

        return $b;
    }

    /**
     * Jama / Naam totals grouped by party_type or payment_mode over a date range.
     * A NULL or empty value collapses to the '' label, shown as "Unspecified".
     *
     * @return array<int, array{label:string, count:int, jama:float, naam:float, net:float}>
     */
    public function groupTotals(?int $companyId, string $column, string $from, string $to, array $f = []): array
    {
        if (! in_array($column, self::GROUPABLE, true)) {
            throw new InvalidArgumentException("Cannot group transactions by '{$column}'.");
        }

        $b = $this->scopedBuilder($companyId)->where('txn_date >=', $from)->where('txn_date <=', $to);
        self::applyClassFilters($b, '', $f);

        $rows = $b
            ->select("COALESCE({$column}, '') AS label, COUNT(*) AS cnt,"
                . "COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS jama,"
                . "COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS naam", false)
            ->groupBy('label')
            ->get()->getResultArray();

        return self::shapeGroups($rows);
    }

    /** Turn raw label/cnt/jama/naam rows into the report's shape, busiest group first. */
    public static function shapeGroups(array $rows): array
    {
        $out = array_map(static fn ($r) => [
            'label' => (string) $r['label'],
            'count' => (int) $r['cnt'],
            'jama'  => (float) $r['jama'],
            'naam'  => (float) $r['naam'],
            'net'   => (float) $r['jama'] - (float) $r['naam'],
        ], $rows);

        usort($out, static fn ($a, $b) => ($b['jama'] + $b['naam']) <=> ($a['jama'] + $a['naam']));

        return $out;
    }

    /** Entries behind one group cell. An empty $value means "the column is unset". */
    public function rowsByColumn(?int $companyId, string $column, string $value, string $from, string $to, array $f = []): array
    {
        if (! in_array($column, self::GROUPABLE, true)) {
            throw new InvalidArgumentException("Cannot filter transactions by '{$column}'.");
        }

        $b = $this->scoped($companyId)->where('txn_date >=', $from)->where('txn_date <=', $to);
        if ($value === '') {
            $b->groupStart()->where($column, null)->orWhere($column, '')->groupEnd();
        } else {
            $b->where($column, $value);
        }
        self::applyClassFilters($b, '', $f);

        return $b->orderBy('txn_date', 'ASC')->orderBy('id', 'ASC')->findAll();
    }

    /**
     * Party-type suggestions: the built-in list plus every type this book already
     * uses, so a company's own wording (e.g. "Aadhati") comes back as a one-click chip.
     */
    public function partyTypes(?int $companyId): array
    {
        $rows = $this->scopedBuilder($companyId)
            ->select('party_type')->distinct()
            ->where('party_type IS NOT NULL')->where('party_type !=', '')
            ->orderBy('party_type', 'ASC')
            ->get()->getResultArray();

        $out = [];
        foreach (array_merge(self::PARTY_TYPES, array_column($rows, 'party_type')) as $t) {
            $t = trim((string) $t);
            if ($t !== '') {
                $out[mb_strtolower($t)] = $t;
            }
        }

        return array_values($out);
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

    /**
     * Type-ahead account search — a small, name-filtered slice of partyDirectory
     * for the add/entry forms. Scales to any number of accounts because the page
     * never embeds the whole list; it queries this as the user types.
     *
     * @return array<int, array{name:string, count:int, net:float, last_date:?string}>
     */
    public function searchParties(?int $companyId, string $q, int $limit = 20): array
    {
        $q     = trim($q);
        $limit = max(1, min($limit, 50));

        $b = $this->scopedBuilder($companyId)
            ->select("name, COUNT(*) AS cnt,"
                . "COALESCE(SUM(CASE WHEN type='jama' THEN amount ELSE 0 END),0) AS jama,"
                . "COALESCE(SUM(CASE WHEN type='naam' THEN amount ELSE 0 END),0) AS naam,"
                . 'MAX(txn_date) AS last_date')
            ->where('name IS NOT NULL')->where('name !=', '');
        if ($q !== '') {
            $b->like('name', $q);
        }

        $rows = $b->groupBy('name')
            ->orderBy('cnt', 'DESC')->orderBy('name', 'ASC')
            ->limit($limit)
            ->get()->getResultArray();

        return array_map(static fn ($r) => [
            'name'      => (string) $r['name'],
            'count'     => (int) $r['cnt'],
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
