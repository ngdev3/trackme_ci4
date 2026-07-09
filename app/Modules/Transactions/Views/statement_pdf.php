<?php
/** Dompdf-only Account Statement layout. Keep this table-based for reliable PDF rendering. */
$fmt = static fn ($n) => number_format((float) $n, 2);
$money = static fn ($n) => 'Rs ' . number_format((float) $n, 2);
$appName = function_exists('setting') ? setting('app_name', 'ERP Admin') : 'ERP Admin';
$range = ($from || $to)
    ? (($from ? date('d M Y', strtotime($from)) : 'Start') . ' - ' . ($to ? date('d M Y', strtotime($to)) : 'Latest'))
    : 'All time';
$genOn = date('d M Y, h:i A');
$net = (float) $totalJama - (float) $totalNaam;
$pdfTotalRows = (int) ($pdfTotalRows ?? count($rows ?? []));
$pdfOffset = (int) ($pdfOffset ?? 0);
$pdfShownRows = count($rows ?? []);
$rowNo = $pdfOffset + 1;
$closingClass = (float) $closing < 0 ? 'red' : 'green';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Account Statement - <?= esc($party) ?></title>
    <style>
        @page { margin: 18px 18px 20px 18px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "DejaVu Sans", Arial, sans-serif;
            color: #1f2937;
            font-size: 8.7px;
            line-height: 1.2;
        }
        table { width: 100%; border-collapse: collapse; }
        .top td { vertical-align: bottom; border-bottom: 2px solid #334155; padding-bottom: 8px; }
        .firm { font-size: 17px; font-weight: 800; letter-spacing: .4px; }
        .subtitle { margin-top: 2px; color: #667085; font-size: 8.4px; letter-spacing: 1.9px; text-transform: uppercase; }
        .date-label { color: #667085; font-size: 7.6px; text-transform: uppercase; letter-spacing: .6px; text-align: right; }
        .date-value { font-size: 10.5px; font-weight: 800; text-align: right; }

        .info { margin-top: 10px; }
        .info td { vertical-align: top; }
        .party-label { color: #667085; font-size: 7.8px; text-transform: uppercase; letter-spacing: .8px; }
        .party-name { font-size: 13.5px; font-weight: 800; margin-top: 3px; }
        .party-sub { color: #667085; font-size: 8px; margin-top: 2px; }
        .meta { width: 220px; margin-left: auto; }
        .meta td { border-bottom: 1px solid #d7dce3; padding: 3px 0; }
        .meta .k { color: #667085; width: 62px; }
        .meta .v { text-align: right; font-weight: 700; }

        .summary { margin-top: 10px; border: 1px solid #cfd6df; table-layout: fixed; }
        .summary td { width: 20%; padding: 6px 7px; border-right: 1px solid #cfd6df; }
        .summary td:last-child { border-right: 0; }
        .summary .label { color: #667085; font-size: 7px; text-transform: uppercase; font-weight: 800; letter-spacing: .25px; }
        .summary .value { margin-top: 3px; font-size: 9.5px; font-weight: 800; white-space: nowrap; }
        .soft { background: #f3f5f7; }
        .green { color: #157347; }
        .red { color: #b02a37; }
        .muted { color: #667085; }

        .note {
            margin-top: 8px;
            padding: 5px 6px;
            border: 1px solid #d7dce3;
            background: #fafbfc;
            color: #4b5563;
            font-size: 8px;
        }

        .ledger { margin-top: 9px; table-layout: fixed; }
        .ledger th {
            background: #eef1f5;
            border-top: 1px solid #cfd6df;
            border-bottom: 1.5px solid #334155;
            color: #1f2937;
            padding: 5px 4px;
            text-align: left;
            font-size: 7.2px;
            text-transform: uppercase;
            letter-spacing: .2px;
        }
        .ledger td {
            border-bottom: 1px solid #d7dce3;
            padding: 4px 4px;
            vertical-align: middle;
        }
        .ledger tbody tr:nth-child(even) td { background: #fbfcfd; }
        .ledger .open td, .ledger .close td {
            background: #f3f5f7 !important;
            font-weight: 800;
        }
        .ledger .close td { border-top: 1.5px solid #334155; }
        .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .center { text-align: center; }
        .type { font-weight: 800; }
        .txn { color: #667085; }
        .footer {
            margin-top: 10px;
            border-top: 1px solid #d7dce3;
            padding-top: 6px;
            color: #667085;
            font-size: 7.6px;
        }
    </style>
</head>
<body>
    <table class="top">
        <tr>
            <td>
                <div class="firm"><?= esc($firm['name'] ?? $appName) ?></div>
                <div class="subtitle">Account Statement</div>
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
                <div class="party-label">Statement For</div>
                <div class="party-name"><?= esc($party) ?></div>
                <div class="party-sub">Party / Account</div>
            </td>
            <td style="width:350px">
                <table class="meta">
                    <tr><td class="k">Period</td><td class="v"><?= esc($range) ?></td></tr>
                    <tr><td class="k">Entries</td><td class="v"><?= number_format((int) $count) ?></td></tr>
                    <tr><td class="k">PDF Rows</td><td class="v"><?= number_format($pdfShownRows) ?> of <?= number_format($pdfTotalRows) ?><?= $pdfOffset > 0 ? ' from #' . number_format($pdfOffset + 1) : '' ?></td></tr>
                    <tr><td class="k">Generated</td><td class="v"><?= esc($genOn) ?></td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td class="soft"><div class="label">Opening</div><div class="value"><?= $money($opening) ?></div></td>
            <td><div class="label">Total Jama</div><div class="value green"><?= $money($totalJama) ?></div></td>
            <td><div class="label">Total Naam</div><div class="value red"><?= $money($totalNaam) ?></div></td>
            <td><div class="label">Net (J - N)</div><div class="value"><?= $money($net) ?></div></td>
            <td class="soft"><div class="label">Closing</div><div class="value <?= $closingClass ?>"><?= $money($closing) ?></div></td>
        </tr>
    </table>

    <?php if ($pdfTotalRows > $pdfShownRows): ?>
        <div class="note">This PDF shows <?= number_format($pdfShownRows) ?> rows for readability. Totals and balances above are calculated from all <?= number_format((int) $count) ?> statement entries.</div>
    <?php endif; ?>

    <table class="ledger">
        <thead>
            <tr>
                <th style="width:24px" class="center">#</th>
                <th style="width:61px">Date</th>
                <th style="width:75px">Txn No</th>
                <th style="width:42px">Type</th>
                <th style="width:45px">Mode</th>
                <th class="num" style="width:78px">Jama (In)</th>
                <th class="num" style="width:78px">Naam (Out)</th>
                <th class="num" style="width:86px">Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($pdfOffset === 0): ?>
                <tr class="open">
                    <td></td><td colspan="4">Opening Balance</td>
                    <td class="num">-</td><td class="num">-</td><td class="num"><?= $money($opening) ?></td>
                </tr>
            <?php endif; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="center muted">No transactions for this account in the selected range.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td class="center"><?= $rowNo++ ?></td>
                    <td><?= esc(date('d M Y', strtotime($r['txn_date']))) ?></td>
                    <td class="txn"><?= esc($r['txn_no'] ?: '-') ?></td>
                    <td class="type <?= $r['type'] === 'jama' ? 'green' : 'red' ?>"><?= $r['type'] === 'jama' ? 'Jama' : 'Naam' ?></td>
                    <td><?= esc(ucfirst((string) $r['payment_mode'])) ?></td>
                    <td class="num green"><?= $r['type'] === 'jama' ? $money($r['amount']) : '' ?></td>
                    <td class="num red"><?= $r['type'] === 'naam' ? $money($r['amount']) : '' ?></td>
                    <td class="num"><strong><?= $money($r['balance']) ?></strong></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr class="close">
                <td></td><td colspan="4">Closing Balance</td>
                <td class="num green"><?= $money($totalJama) ?></td>
                <td class="num red"><?= $money($totalNaam) ?></td>
                <td class="num"><?= $money($closing) ?></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">
        Generated by <?= esc($appName) ?> - <?= esc($genOn) ?>. This is a computer-generated statement.
    </div>
</body>
</html>
