<?php

namespace Modules\Api\Controllers;

use App\Models\CompanyModel;
use App\Models\TransactionModel;

/**
 * Home dashboard aggregate for the mobile app.
 *
 *   GET /api/v1/dashboard   (Bearer) [?company_id=]
 *
 * One round-trip that assembles everything the dashboard screen renders:
 *   - the active company (name),
 *   - cash-book (Jama/Naam) summaries for today, this month and last month,
 *   - the most recent cash-book entries,
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

        $today      = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $prevStart  = date('Y-m-01', strtotime('first day of last month'));
        $prevEnd    = date('Y-m-t', strtotime('last day of last month'));

        $todaySum = $this->shapeSummary($txn->summary($cid, ['from' => $today, 'to' => $today]));
        $monthSum = $this->shapeSummary($txn->summary($cid, ['from' => $monthStart, 'to' => $today]));
        $prevSum  = $this->shapeSummary($txn->summary($cid, ['from' => $prevStart, 'to' => $prevEnd]));

        // Percent change of this month's net vs last month's (null when no base).
        $monthSum['net_delta'] = $this->percentDelta($monthSum['net'], $prevSum['net']);

        // Redesigned-dashboard blocks (money-in framed as "sales", money-out as
        // "expenses"; this is a cash book, so there is no separate purchase feed —
        // the 4th card shows the running balance instead). Each metric carries a
        // signed % delta vs last month so the UI can render the up/down chips.
        $allTime = $this->shapeSummary($txn->summary($cid, []));
        $metrics = [
            'sales'    => ['value' => $monthSum['deposits'], 'delta' => $this->percentDelta($monthSum['deposits'], $prevSum['deposits'])],
            'expenses' => ['value' => $monthSum['expenses'], 'delta' => $this->percentDelta($monthSum['expenses'], $prevSum['expenses'])],
            'profit'   => ['value' => $monthSum['net'],      'delta' => $this->percentDelta($monthSum['net'],      $prevSum['net'])],
            'balance'  => ['value' => $allTime['net'],       'delta' => null],
        ];

        // Last 6 months of money-in / money-out / net for the Sales Overview chart.
        $series = $this->monthlySeries($txn, $cid, 6);

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
        ], $txn->limitedFiltered($cid, [], 6, 0));

        $company = (new CompanyModel())->find($cid);

        return $this->respond([
            'status'    => 'ok',
            'company'   => [
                'id'            => $cid,
                'name'          => $company['name'] ?? null,
                'business_type' => $company['business_type'] ?? null,
                'state'         => $company['state'] ?? null,
            ],
            'cash'      => [
                'today'      => $todaySum,
                'month'      => $monthSum,
                'prev_month' => $prevSum,
            ],
            'metrics'   => $metrics,
            'series'    => $series,
            'recent'    => $recent,
            'inventory' => $this->inventorySnapshot($cid, $company, $user),
        ]);
    }

    /**
     * Build a month-by-month money-in / money-out / net series for the last
     * $months calendar months (oldest first), each labelled with its short
     * month name for the chart axis.
     *
     * @return list<array{label:string, month:string, sales:float, expenses:float, net:float}>
     */
    private function monthlySeries(TransactionModel $txn, int $cid, int $months): array
    {
        $out = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $start = date('Y-m-01', strtotime("first day of -{$i} month"));
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
