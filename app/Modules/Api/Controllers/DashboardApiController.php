<?php

namespace Modules\Api\Controllers;

use App\Libraries\OpeningBalance;
use App\Models\CompanyModel;
use App\Models\TransactionModel;

/**
 * Home dashboard aggregate for the mobile app.
 *
 *   GET /api/v1/dashboard   (Bearer) [?company_id=]
 *       [?period=month&month=YYYY-MM]          — a calendar month (default: this month)
 *       [?period=range&from=YYYY-MM-DD&to=…]   — an arbitrary date range (date-wise)
 *       [?period=fy&fy=YYYY]                   — an Indian financial year (Apr–Mar)
 *
 * One round-trip that assembles everything the dashboard screen renders:
 *   - the active company (name),
 *   - the selected reporting period + its cash-book (Jama/Naam) summaries,
 *   - cash-book summaries for today, the period and the previous period,
 *   - the most recent cash-book entries within the period,
 *   - a compact, feature-gated inventory snapshot.
 *
 * It only reads — no business logic is added here; it reuses TransactionModel
 * and the InventoryReport library the web app already uses.
 */
class DashboardApiController extends BaseApiController
{
    protected $helpers = ['settings', 'subscription'];

    public function index()
    {
        $user = $this->currentApiUser();
        if (! $user) {
            return $this->failUnauthorized('Invalid or missing token.');
        }
        $cid = $this->resolveCompanyId($user);
        if (! $cid) {
            return $this->failValidationErrors('No company for this user.');
        }

        $txn = new TransactionModel();
        $today = date('Y-m-d');

        // Selected reporting period (month / range / FY) + the comparable prior
        // period used for the up/down deltas.
        $p          = $this->resolvePeriod();
        $periodFrom = $p['from'];
        $periodTo   = $p['to'];

        $todaySum  = $this->shapeSummary($txn->summary($cid, ['from' => $today, 'to' => $today]));
        $periodSum = $this->shapeSummary($txn->summary($cid, ['from' => $periodFrom, 'to' => $periodTo]));
        $prevSum   = $this->shapeSummary($txn->summary($cid, ['from' => $p['prevFrom'], 'to' => $p['prevTo']]));

        // Percent change of the period's net vs the previous period (null = no base).
        $periodSum['net_delta'] = $this->percentDelta($periodSum['net'], $prevSum['net']);

        // Running balance is cumulative to the period end (capped at today so a
        // current/future period end doesn't imply future transactions).
        $balanceTo = min($periodTo, $today);

        // Opening cash carried into the selected period (Shri Rokad Nagad opening
        // + net of entries from the FY start up to, but excluding, the period
        // start). The closing balance is then Opening + this period's Jama − Naam,
        // so the 4th card reflects true cash-in-hand, not just transaction net.
        $ob        = new OpeningBalance($cid, $cid);
        $opening   = round($ob->carryInto($periodFrom), 2);
        $periodNet = $this->shapeSummary($txn->summary($cid, ['from' => $periodFrom, 'to' => $balanceTo]))['net'];
        $closing   = round($opening + $periodNet, 2);

        // Headline cards (money-in framed as "sales", money-out as "expenses";
        // this is a cash book, so the 4th card shows the running balance). Each
        // metric carries a signed % delta vs the previous period for the chips.
        $metrics = [
            'sales'    => ['value' => $periodSum['deposits'], 'delta' => $this->percentDelta($periodSum['deposits'], $prevSum['deposits'])],
            'expenses' => ['value' => $periodSum['expenses'], 'delta' => $this->percentDelta($periodSum['expenses'], $prevSum['expenses'])],
            'profit'   => ['value' => $periodSum['net'],      'delta' => $this->percentDelta($periodSum['net'],      $prevSum['net'])],
            'opening'  => ['value' => $opening,               'delta' => null],
            'balance'  => ['value' => $closing,               'delta' => null],
        ];

        // Money-in / money-out / net series for the chart: the FY's 12 months, or
        // the 6 months ending at the period end for month/range.
        $series = $this->monthlySeries($txn, $cid, $p['seriesMonths'], $p['seriesAnchor']);

        // Recent entries within the selected period.
        $recent = array_map(static fn (array $r): array => [
            'id'           => (int) $r['id'],
            'txn_no'       => $r['txn_no'],
            'date'         => $r['txn_date'],
            'name'         => $r['name'],
            'party_type'   => $r['party_type'],
            'type'         => $r['type'],
            'amount'       => (float) $r['amount'],
            'payment_mode' => $r['payment_mode'],
            'status'       => $r['status'],
        ], $txn->limitedFiltered($cid, ['from' => $periodFrom, 'to' => $periodTo], 6, 0));

        $company = (new CompanyModel())->find($cid);

        return $this->respond([
            'status'    => 'ok',
            'company'   => [
                'id'            => $cid,
                'name'          => $company['name'] ?? null,
                'business_type' => $company['business_type'] ?? null,
                'state'         => $company['state'] ?? null,
            ],
            'period'    => [
                'type'  => $p['type'],
                'from'  => $periodFrom,
                'to'    => $periodTo,
                'label' => $p['label'],
            ],
            'cash'      => [
                'today'      => $todaySum,
                'month'      => $periodSum, // "month" key kept for back-compat; holds the selected period
                'prev_month' => $prevSum,
            ],
            'metrics'   => $metrics,
            'series'    => $series,
            'recent'    => $recent,
            'inventory' => $this->inventorySnapshot($cid, $company, $user),
        ]);
    }

    /**
     * Resolve the requested reporting period from the query string into concrete
     * date bounds plus the comparable previous period and chart parameters.
     *
     * @return array{type:string, from:string, to:string, prevFrom:string, prevTo:string, label:string, seriesMonths:int, seriesAnchor:string}
     */
    private function resolvePeriod(): array
    {
        $type  = (string) ($this->request->getGet('period') ?: 'month');
        $today = date('Y-m-d');

        if ($type === 'fy') {
            $startYear = (int) ($this->request->getGet('fy') ?: $this->currentFyStartYear());
            $from      = sprintf('%04d-04-01', $startYear);
            $to        = sprintf('%04d-03-31', $startYear + 1);

            return [
                'type'         => 'fy',
                'from'         => $from,
                'to'           => $to,
                'prevFrom'     => sprintf('%04d-04-01', $startYear - 1),
                'prevTo'       => sprintf('%04d-03-31', $startYear),
                'label'        => sprintf('FY %d-%s', $startYear, substr((string) ($startYear + 1), -2)),
                'seriesMonths' => 12,
                'seriesAnchor' => $to,
            ];
        }

        if ($type === 'range') {
            $from = $this->validDate((string) $this->request->getGet('from'), date('Y-m-01'));
            $to   = $this->validDate((string) $this->request->getGet('to'), $today);
            if ($from > $to) {
                [$from, $to] = [$to, $from];
            }
            $lenDays  = (int) (((int) strtotime($to) - (int) strtotime($from)) / 86400) + 1;
            $prevTo   = date('Y-m-d', strtotime($from . ' -1 day'));
            $prevFrom = date('Y-m-d', strtotime($prevTo . ' -' . ($lenDays - 1) . ' day'));

            return [
                'type'         => 'range',
                'from'         => $from,
                'to'           => $to,
                'prevFrom'     => $prevFrom,
                'prevTo'       => $prevTo,
                'label'        => date('j M Y', strtotime($from)) . ' – ' . date('j M Y', strtotime($to)),
                'seriesMonths' => 6,
                'seriesAnchor' => $to,
            ];
        }

        // Default: a calendar month.
        $month = (string) ($this->request->getGet('month') ?: date('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = date('Y-m');
        }
        $from     = $month . '-01';
        $to       = date('Y-m-t', strtotime($from));
        $prevFrom = date('Y-m-01', strtotime($from . ' -1 month'));

        return [
            'type'         => 'month',
            'from'         => $from,
            'to'           => $to,
            'prevFrom'     => $prevFrom,
            'prevTo'       => date('Y-m-t', strtotime($prevFrom)),
            'label'        => date('F Y', strtotime($from)),
            'seriesMonths' => 6,
            'seriesAnchor' => $to,
        ];
    }

    /** Start year of the Indian financial year (Apr–Mar) containing today. */
    private function currentFyStartYear(): int
    {
        $y = (int) date('Y');
        return (int) date('n') >= 4 ? $y : $y - 1;
    }

    /** Return $value if it is a valid YYYY-MM-DD date, else $default. */
    private function validDate(string $value, string $default): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $value);
        return ($d && $d->format('Y-m-d') === $value) ? $value : $default;
    }

    /**
     * Build a month-by-month money-in / money-out / net series ending at the
     * month containing $anchor and spanning $months months (oldest first), each
     * labelled with its short month name for the chart axis.
     *
     * @return list<array{label:string, month:string, sales:float, expenses:float, net:float}>
     */
    private function monthlySeries(TransactionModel $txn, int $cid, int $months, string $anchor): array
    {
        $base = (int) strtotime(date('Y-m-01', strtotime($anchor)));
        $out  = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("-{$i} month", $base));
            $end   = date('Y-m-t', strtotime($start));
            $s     = $this->shapeSummary($txn->summary($cid, ['from' => $start, 'to' => $end]));
            $out[] = [
                'label'    => date('M', strtotime($start)),
                'month'    => date('Y-m', strtotime($start)),
                'sales'    => $s['deposits'],
                'expenses' => $s['expenses'],
                'net'      => $s['net'],
            ];
        }
        return $out;
    }

    /** Normalise a TransactionModel::summary() row to rounded, typed fields. */
    private function shapeSummary(array $s): array
    {
        return [
            'deposits' => round((float) ($s['jama'] ?? 0), 2),
            'expenses' => round((float) ($s['naam'] ?? 0), 2),
            'net'      => round((float) ($s['net'] ?? 0), 2),
            'count'    => (int) ($s['count'] ?? 0),
        ];
    }

    /** Signed percent change of $curr vs $base, or null when no meaningful base. */
    private function percentDelta(float $curr, float $base): ?float
    {
        if (abs($base) < 0.005) {
            return null;
        }
        return round(($curr - $base) / abs($base) * 100, 1);
    }

    /**
     * Compact inventory snapshot, but only when the company's plan includes the
     * inventory feature. Returns null otherwise so the app hides the section.
     */
    private function inventorySnapshot(int $cid, ?array $company, array $user): ?array
    {
        $ownerId = $company ? (int) $company['owner_id'] : (int) $user['id'];
        if (! function_exists('customer_has_feature') || ! customer_has_feature($ownerId, 'inventory')) {
            return null;
        }

        $d = (new \App\Libraries\InventoryReport())->dashboard($cid);

        return [
            'current_bags'        => $d['current_bags'] ?? 0,
            'inventory_value'     => $d['inventory_value'] ?? 0,
            'received_today'      => $d['received_today'] ?? 0,
            'dispatched_today'    => $d['dispatched_today'] ?? 0,
            'product_count'       => $d['product_count'] ?? 0,
            'warehouse_count'     => $d['warehouse_count'] ?? 0,
            'warehouse_util'      => $d['warehouse_util'] ?? 0,
            'pending_corrections' => $d['pending_corrections'] ?? 0,
        ];
    }
}
