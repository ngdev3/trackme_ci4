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
 *   - the most recent cash-book entries within the period.
 *
 * It only reads — no business logic is added here; it reuses TransactionModel.
 * (The inventory snapshot was removed with the inventory module; see
 * inventorySnapshot() which now returns null until inventory is re-implemented.)
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

        $txn     = new TransactionModel();
        $today   = date('Y-m-d');
        $p       = $this->resolvePeriod();
        $company = (new CompanyModel())->find($cid);

        // Cache the heavy aggregate block per company + period: ~10 SUM queries
        // plus the 6-month series and the recent feed. TTL is short and any
        // transaction write for this firm busts the cache instantly (dash_bust,
        // fired from TransactionModel + the sync endpoint), so figures never go
        // stale. `?fresh=1` forces a recompute (pull-to-refresh). Shared with the
        // web dashboard cache via the same per-company version key.
        $data = dash_remember($cid, 'apiv1:dash:' . $p['type'] . ':' . md5($p['from'] . '|' . $p['to']), 60, function () use ($txn, $cid, $today, $p, $company, $user) {
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
            // start). The closing balance is then Opening + this period's Jama − Naam.
            $ob        = new OpeningBalance($cid, $cid);
            $opening   = round($ob->carryInto($periodFrom), 2);
            $periodNet = $this->shapeSummary($txn->summary($cid, ['from' => $periodFrom, 'to' => $balanceTo]))['net'];
            $closing   = round($opening + $periodNet, 2);

            // Headline cards. When the firm uses the billing module we report on an
            // ACCRUAL basis — Sales = net sale bills, Purchases = net purchase bills,
            // Profit = Sales − Purchases — so a credit sale counts the moment it is
            // billed (not only when the cash arrives). Firms that keep a pure cash
            // book (no bills) fall back to the cash figures unchanged.
            $inv      = new \App\Models\InvoiceModel();
            $bills    = $inv->periodTotals($cid, $periodFrom, $periodTo);
            $prevBill = $inv->periodTotals($cid, $p['prevFrom'], $p['prevTo']);
            $accrual  = $bills['count'] > 0;

            if ($accrual) {
                $sales    = $bills['net_sales'];
                $purch    = $bills['net_purchases'];
                // TRUE profit = revenue − COST OF GOODS SOLD (Σ qty×(rate−cost)),
                // NOT sales − purchases: buying stock is an asset, so a big stock
                // purchase must never make "profit" go negative on a profitable sale.
                $profit   = $inv->salesProfit($cid, $periodFrom, $periodTo);
                $pSales   = $prevBill['net_sales'];
                $pPurch   = $prevBill['net_purchases'];
                $pProfit  = $inv->salesProfit($cid, $p['prevFrom'], $p['prevTo']);
                // Net position for an inventory business = cash-in-hand + stock value
                // (at cost). Cash spent buying stock isn't lost — it moved into
                // inventory — so the headline shouldn't go negative just because the
                // firm stocked up. `cash` is still returned separately.
                $stockValue = (float) ((new \App\Models\ProductModel())->summary($cid)['stock_value'] ?? 0);
                $netWorth   = round($closing + $stockValue, 2);
                $metrics  = [
                    'sales'    => ['value' => $sales,  'delta' => $this->percentDelta($sales,  $pSales)],
                    'expenses' => ['value' => $purch,  'delta' => $this->percentDelta($purch,  $pPurch)],
                    'profit'   => ['value' => $profit, 'delta' => $this->percentDelta($profit, $pProfit)],
                    'opening'  => ['value' => $opening, 'delta' => null],
                    'balance'  => ['value' => $netWorth, 'delta' => null],
                    'cash'     => ['value' => $closing, 'delta' => null],
                    'stock'    => ['value' => round($stockValue, 2), 'delta' => null],
                    'basis'    => 'accrual',
                ];
            } else {
                // Pure cash book: money-in framed as "sales", money-out as "expenses".
                $metrics = [
                    'sales'    => ['value' => $periodSum['deposits'], 'delta' => $this->percentDelta($periodSum['deposits'], $prevSum['deposits'])],
                    'expenses' => ['value' => $periodSum['expenses'], 'delta' => $this->percentDelta($periodSum['expenses'], $prevSum['expenses'])],
                    'profit'   => ['value' => $periodSum['net'],      'delta' => $this->percentDelta($periodSum['net'],      $prevSum['net'])],
                    'opening'  => ['value' => $opening,               'delta' => null],
                    'balance'  => ['value' => $closing,               'delta' => null],
                    'basis'    => 'cash',
                ];
            }

            $series = $this->monthlySeries($txn, $cid, $p['seriesMonths'], $p['seriesAnchor']);

            $recent = array_map(static fn (array $r): array => [
                'id'           => (int) $r['id'],
                'txn_no'       => $r['txn_no'],
                'date'         => $r['txn_date'],
                'created_at'   => $r['created_at'] ?? null,
                'name'         => $r['name'],
                'party_type'   => $r['party_type'],
                'type'         => $r['type'],
                'amount'       => (float) $r['amount'],
                'payment_mode' => $r['payment_mode'],
                'status'       => $r['status'],
                // source/notes let the app badge a Sale/Purchase in the recent feed.
                'source'       => $r['source'] ?? null,
                'notes'        => $r['notes'] ?? null,
            ], $txn->limitedFiltered($cid, ['from' => $periodFrom, 'to' => $periodTo], 6, 0));

            return [
                'cash'      => [
                    'today'      => $todaySum,
                    'month'      => $periodSum, // "month" key kept for back-compat; holds the selected period
                    'prev_month' => $prevSum,
                ],
                'metrics'   => $metrics,
                'series'    => $series,
                'recent'    => $recent,
                'inventory' => $this->inventorySnapshot($cid, $company, $user),
            ];
        });

        return $this->respond(array_merge([
            'status'  => 'ok',
            'company' => [
                'id'            => $cid,
                'name'          => $company['name'] ?? null,
                'business_type' => $company['business_type'] ?? null,
                'state'         => $company['state'] ?? null,
            ],
            'period'  => [
                'type'  => $p['type'],
                'from'  => $p['from'],
                'to'    => $p['to'],
                'label' => $p['label'],
            ],
        ], $data));
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
     * Inventory snapshot placeholder — the inventory module was removed and will
     * be re-implemented later; always returns null so the app hides the section.
     */
    private function inventorySnapshot(int $cid, ?array $company, array $user): ?array
    {
        return null;
    }
}
