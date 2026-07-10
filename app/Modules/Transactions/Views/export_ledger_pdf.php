<?php
/**
 * Jama-Naam ledger — PDF, laid out like a bank account statement (dompdf, no
 * scripts). Debit/Credit columns, a running balance over the listed entries,
 * party type + remarks folded into Particulars. No Txn No.
 *
 * The `balance` column is a running total of the entries shown in this PDF, in
 * date order — not a per-party account balance. The headline totals come from
 * the full filtered set, so they stay correct even when the list is capped.
 */
use App\Models\TransactionModel;

$money   = static fn ($n) => 'Rs ' . number_format((float) $n, 2);
$appName = function_exists('setting') ? setting('app_name', 'ERP Admin') : 'ERP Admin';
$genOn   = date('d M Y, h:i A');

$filters = $filters ?? [];
$modeLabels = TransactionModel::MODE_LABELS;

// Human summary of the filters that produced this list.
$chips = [];
if (($filters['q'] ?? '') !== '')      { $chips[] = 'Search "' . $filters['q'] . '"'; }
if (($filters['type'] ?? '') !== '')   { $chips[] = 'Type: ' . ucfirst($filters['type']); }
if (($filters['mode'] ?? '') !== '')   { $chips[] = 'Mode: ' . (($modeLabels[$filters['mode']] ?? ucfirst($filters['mode']))); }
if (($filters['status'] ?? '') !== '') { $chips[] = 'Status: ' . ucfirst($filters['status']); }
$fromF = ($filters['from'] ?? '') !== '' ? date('d M Y', strtotime($filters['from'])) : '';
$toF   = ($filters['to'] ?? '') !== '' ? date('d M Y', strtotime($filters['to'])) : '';
$range = ($fromF || $toF) ? (($fromF ?: 'Start') . ' - ' . ($toF ?: 'Latest')) : 'All time';

$totalRows  = (int) ($summary['count'] ?? count($rows));
$shownRows  = count($rows);
$offset     = (int) ($offset ?? 0);
$capped     = $totalRows > $shownRows;
$opening    = (float) ($opening ?? 0);
$hasOpening = ! empty($hasOpening);

// Running balance is only meaningful oldest-first, so order a copy by date then id.
// It builds on the carried-forward opening balance (bank-statement style).
$asc = $rows;
usort($asc, static function ($a, $b) {
    return [$a['txn_date'], (int) $a['id']] <=> [$b['txn_date'], (int) $b['id']];
});
$run = $opening;
foreach ($asc as $i => $r) {
    $run += $r['type'] === 'jama' ? (float) $r['amount'] : -(float) $r['amount'];
    $asc[$i]['_bal'] = $run;
}
$net     = (float) ($summary['net'] ?? 0);
$closing = $opening + $net;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Transactions Statement</title>
    <style>
        @page { margin: 18px 18px 20px 18px; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: "DejaVu Sans", Arial, sans-serif; color: #1f2937; font-size: 8.7px; line-height: 1.2; }
        table { width: 100%; border-collapse: collapse; }

        .top td { vertical-align: bottom; border-bottom: 2px solid #334155; padding-bottom: 8px; }
        .firm { font-size: 17px; font-weight: 800; letter-spacing: .4px; }
        .subtitle { margin-top: 2px; color: #667085; font-size: 8.4px; letter-spacing: 1.9px; text-transform: uppercase; }
        .date-label { color: #667085; font-size: 7.6px; text-transform: uppercase; letter-spacing: .6px; text-align: right; }
        .date-value { font-size: 10.5px; font-weight: 800; text-align: right; }

        .info { margin-top: 10px; }
        .info td { vertical-align: top; }
        .heading-label { color: #667085; font-size: 7.8px; text-transform: uppercase; letter-spacing: .8px; }
        .heading-name { font-size: 13.5px; font-weight: 800; margin-top: 3px; }
        .heading-sub { color: #667085; font-size: 8px; margin-top: 2px; }
        .meta { width: 260px; margin-left: auto; }
        .meta td { border-bottom: 1px solid #d7dce3; padding: 3px 0; }
        .meta .k { color: #667085; width: 74px; }
        .meta .v { text-align: right; font-weight: 700; }

        .summary { margin-top: 10px; border: 1px solid #cfd6df; table-layout: fixed; }
        .summary td { width: 25%; padding: 6px 7px; border-right: 1px solid #cfd6df; }
        .summary td:last-child { border-right: 0; }
        .summary .label { color: #667085; font-size: 7px; text-transform: uppercase; font-weight: 800; letter-spacing: .25px; }
        .summary .value { margin-top: 3px; font-size: 9.5px; font-weight: 800; white-space: nowrap; }
        .soft { background: #f3f5f7; }
        .green { color: #157347; } .red { color: #b02a37; } .muted { color: #667085; }

        .note { margin-top: 8px; padding: 5px 6px; border: 1px solid #d7dce3; background: #fafbfc; color: #4b5563; font-size: 8px; }

        .ledger { margin-top: 9px; table-layout: fixed; }
        .ledger th { background: #eef1f5; border-top: 1px solid #cfd6df; border-bottom: 1.5px solid #334155;
            color: #1f2937; padding: 5px 4px; text-align: left; font-size: 7.2px; text-transform: uppercase; letter-spacing: .2px; }
        .ledger td { border-bottom: 1px solid #d7dce3; padding: 4px 4px; vertical-align: top; }
        .ledger tbody tr:nth-child(even) td { background: #fbfcfd; }
        .ledger .close td { background: #f3f5f7 !important; font-weight: 800; border-top: 1.5px solid #334155; }
        .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .center { text-align: center; }
        .pname { font-weight: 800; }
        .psub { color: #667085; font-size: 7.4px; margin-top: 1px; }
        .type { font-weight: 800; }
        .footer { margin-top: 10px; border-top: 1px solid #d7dce3; padding-top: 6px; color: #667085; font-size: 7.6px; }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <div class="firm"><?= esc($firm['name'] ?? $appName) ?></div>
                <div class="subtitle">Transactions Statement</div>
            </td>
            <td style="width:220px">
                <div class="date-label">Statement Date</div>
                <div class="date-value"><?= esc(date('d M Y')) ?></div>
            </td>
        </tr>
    </table>

    <table class="info">
        <tr>
            <td>
                <div class="heading-label">Statement Of</div>
                <div class="heading-name">Jama / Naam Ledger</div>
                <div class="heading-sub"><?php if (! empty($firm['state'])): ?><?= esc($firm['state']) ?> &middot; <?php endif; ?>Cash book (all parties)</div>
                <?php if ($chips): ?>
                    <div class="psub" style="margin-top:5px">Filters: <?= esc(implode('  |  ', $chips)) ?></div>
                <?php endif; ?>
            </td>
            <td style="width:360px">
                <table class="meta">
                    <tr><td class="k">Period</td><td class="v"><?= esc($range) ?></td></tr>
                    <tr><td class="k">Entries</td><td class="v"><?= number_format($totalRows) ?></td></tr>
                    <tr><td class="k">PDF Rows</td><td class="v"><?= number_format($shownRows) ?> of <?= number_format($totalRows) ?><?= $offset > 0 ? ' from #' . number_format($offset + 1) : '' ?></td></tr>
                    <tr><td class="k">Generated</td><td class="v"><?= esc($genOn) ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="summary">
        <?php if ($hasOpening): ?>
        <tr>
            <td class="soft"><div class="label">Opening Balance</div><div class="value <?= $opening < 0 ? 'red' : '' ?>"><?= $money($opening) ?></div></td>
            <td><div class="label">Total Credit (Jama)</div><div class="value green"><?= $money($summary['jama'] ?? 0) ?></div></td>
            <td><div class="label">Total Debit (Naam)</div><div class="value red"><?= $money($summary['naam'] ?? 0) ?></div></td>
            <td><div class="label">Net (Cr - Dr)</div><div class="value <?= $net < 0 ? 'red' : 'green' ?>"><?= $money($net) ?></div></td>
            <td class="soft"><div class="label">Closing Balance</div><div class="value <?= $closing < 0 ? 'red' : 'green' ?>"><?= $money($closing) ?></div></td>
        </tr>
        <?php else: ?>
        <tr>
            <td><div class="label">Total Credit (Jama)</div><div class="value green"><?= $money($summary['jama'] ?? 0) ?></div></td>
            <td><div class="label">Total Debit (Naam)</div><div class="value red"><?= $money($summary['naam'] ?? 0) ?></div></td>
            <td class="soft"><div class="label">Net (Credit - Debit)</div><div class="value <?= $net < 0 ? 'red' : 'green' ?>"><?= $money($net) ?></div></td>
            <td><div class="label">Transactions</div><div class="value"><?= number_format($totalRows) ?></div></td>
        </tr>
        <?php endif; ?>
    </table>

    <?php if ($hasOpening): ?>
        <div class="note">Opening Balance is the cash carried forward before <?= esc(($filters['from'] ?? '') !== '' ? date('d M Y', strtotime($filters['from'])) : date('d M Y', strtotime($asc[0]['txn_date']))) ?> (any old pending balance). The Balance column runs on from it, so the Closing Balance is your current cash-in-hand.</div>
    <?php elseif ($capped): ?>
        <div class="note">This PDF lists <?= number_format($shownRows) ?> of <?= number_format($totalRows) ?> entries for readability. The Total Credit / Debit / Net above cover all <?= number_format($totalRows) ?> filtered entries; the Balance column is a running total of the listed rows only.</div>
    <?php else: ?>
        <div class="note">Balance is a running total of Credit minus Debit across the entries below, in date order. Filters are applied, so it is not the full cash-book balance.</div>
    <?php endif; ?>

    <table class="ledger">
        <thead>
            <tr>
                <th style="width:20px" class="center">#</th>
                <th style="width:56px">Date</th>
                <th>Particulars</th>
                <th style="width:40px">Type</th>
                <th style="width:46px">Mode</th>
                <th class="num" style="width:74px">Debit (Naam)</th>
                <th class="num" style="width:74px">Credit (Jama)</th>
                <th class="num" style="width:80px">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($hasOpening): ?>
                <tr class="close">
                    <td></td><td colspan="4">Opening Balance (b/f)</td>
                    <td class="num">-</td><td class="num">-</td><td class="num"><?= $money($opening) ?></td>
                </tr>
            <?php endif; ?>
            <?php if (empty($asc)): ?>
                <tr><td colspan="8" class="center muted">No transactions.</td></tr>
            <?php else: $i = $offset + 1; foreach ($asc as $r):
                $isJama = $r['type'] === 'jama';
                $sub = [];
                if (! empty($r['party_type'])) { $sub[] = $r['party_type']; }
                if (! empty($r['status']) && $r['status'] !== 'paid') { $sub[] = ucfirst($r['status']); }
                if (! empty($r['notes'])) { $sub[] = $r['notes']; }
            ?>
                <tr>
                    <td class="center muted"><?= $i++ ?></td>
                    <td><?= esc(date('d M Y', strtotime($r['txn_date']))) ?></td>
                    <td>
                        <div class="pname"><?= esc($r['name']) ?></div>
                        <?php if ($sub): ?><div class="psub"><?= esc(implode(' · ', $sub)) ?></div><?php endif; ?>
                    </td>
                    <td class="type <?= $isJama ? 'green' : 'red' ?>"><?= $isJama ? 'Jama' : 'Naam' ?></td>
                    <td><?= esc($modeLabels[$r['payment_mode']] ?? ucfirst((string) $r['payment_mode'])) ?></td>
                    <td class="num red"><?= $isJama ? '' : $money($r['amount']) ?></td>
                    <td class="num green"><?= $isJama ? $money($r['amount']) : '' ?></td>
                    <td class="num"><strong><?= $money($r['_bal']) ?></strong></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <?php if (! empty($asc)): ?>
        <tfoot>
            <tr class="close">
                <td colspan="5"><?= $hasOpening ? 'Closing Balance (c/f)' : 'Totals (listed entries)' ?></td>
                <td class="num red"><?= $money(array_sum(array_map(static fn ($r) => $r['type'] === 'naam' ? (float) $r['amount'] : 0, $asc))) ?></td>
                <td class="num green"><?= $money(array_sum(array_map(static fn ($r) => $r['type'] === 'jama' ? (float) $r['amount'] : 0, $asc))) ?></td>
                <td class="num"><?= $money(end($asc)['_bal']) ?></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    <div class="footer">
        Generated by <?= esc($appName) ?> - <?= esc($genOn) ?>. This is a computer-generated statement.
    </div>
</body>
</html>
