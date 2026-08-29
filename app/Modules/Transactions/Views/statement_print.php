<?php
/** Standalone printable Account (party) statement — clean, print-friendly.
 *  Minimal ink: mostly white + thin rules, colour only for Jama/Naam amounts.
 *  $noPrint = true when rendered for PDF (dompdf) — no auto-print, no buttons. */
$fmt     = fn ($n) => number_format((float) $n, 2);
$noPrint = $noPrint ?? false;
$cur     = $noPrint ? 'Rs ' : '₹';
$money   = fn ($n) => $cur . ' ' . $fmt($n);
$net     = (float) $totalJama - (float) $totalNaam;
$appName = function_exists('setting') ? setting('app_name', 'ERP Admin') : 'ERP Admin';
$range   = ($from || $to)
    ? (($from ? date('d M Y', strtotime($from)) : 'Start') . '  —  ' . ($to ? date('d M Y', strtotime($to)) : 'Latest'))
    : 'All time';
$genOn   = date('d M Y, h:i A');
$pdfTotalRows = $pdfTotalRows ?? null;
$pdfOffset    = (int) ($pdfOffset ?? 0);
$pdfShownRows = is_array($rows ?? null) ? count($rows) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Account Statement — <?= esc($party) ?></title>
    <style>
        :root {
            --ink: #1f2733; --muted: #6b7280; --line: #d7dce3; --soft: #f4f6f8;
            --accent: #334155;          /* single restrained accent */
            --green: #157347; --red: #b02a37;
        }
        * { box-sizing: border-box; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        html, body { margin: 0; padding: 0; }
        body { font-family: "Segoe UI", "DejaVu Sans", Arial, sans-serif; color: var(--ink); font-size: 12.5px; background: #fff; }
        .sheet { max-width: 820px; margin: 0 auto; padding: 0; }
        @page { margin: 1.2cm; }

        /* ---- Header ---- */
        .stmt-head { display: flex; justify-content: space-between; align-items: flex-end;
            padding-bottom: 12px; border-bottom: 2.5px solid var(--accent); }
        .brand-name { font-size: 21px; font-weight: 800; color: var(--ink); letter-spacing: .2px; }
        .brand-sub  { font-size: 10.5px; letter-spacing: 3px; text-transform: uppercase; color: var(--muted); margin-top: 3px; }
        .head-meta { text-align: right; }
        .head-meta .hm-lbl { font-size: 9.5px; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); }
        .head-meta .hm-val { font-size: 14px; font-weight: 700; }

        /* ---- Party + meta ---- */
        .info { display: flex; justify-content: space-between; gap: 18px; margin-top: 16px; }
        .who-label { font-size: 10px; text-transform: uppercase; letter-spacing: 1.5px; color: var(--muted); }
        .who-name { font-size: 19px; font-weight: 800; line-height: 1.15; margin-top: 3px; }
        .who-tag  { font-size: 11px; color: var(--muted); }
        .meta-box { min-width: 260px; }
        .meta-line { display: flex; justify-content: space-between; gap: 20px; padding: 4px 0; border-bottom: 1px solid var(--line); }
        .meta-line:last-child { border-bottom: 0; }
        .meta-line span { color: var(--muted); }
        .meta-line b { font-weight: 700; }

        /* ---- Summary strip (bordered, no fills) ---- */
        .cards { display: flex; margin-top: 16px; border: 1px solid var(--line); border-radius: 6px; overflow: hidden; }
        .card { flex: 1; padding: 9px 12px; border-right: 1px solid var(--line); }
        .card:last-child { border-right: 0; }
        .card .c-lbl { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: var(--muted); font-weight: 700; }
        .card .c-val { font-size: 14px; font-weight: 800; margin-top: 3px; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .card.head-cell { background: var(--soft); }
        .c-jama .c-val { color: var(--green); }
        .c-naam .c-val { color: var(--red); }
        .c-close .c-val { color: var(--accent); }

        /* ---- Table ---- */
        .tbl-wrap { margin-top: 16px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        thead th { background: var(--soft); color: var(--ink); text-align: left; padding: 8px 10px;
            font-weight: 700; font-size: 10px; text-transform: uppercase; letter-spacing: .4px;
            border-top: 1px solid var(--line); border-bottom: 1.5px solid var(--accent); }
        tbody td { padding: 7px 10px; border-bottom: 1px solid var(--line); }
        tbody tr:nth-child(even) td { background: #fbfcfd; }
        .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .jama { color: var(--green); font-weight: 600; }
        .naam { color: var(--red); font-weight: 600; }
        .bal  { font-weight: 700; }
        .type-j, .type-n { font-weight: 700; font-size: 11px; }
        .type-j { color: var(--green); } .type-n { color: var(--red); }
        .row-open td, .row-close td { font-weight: 700; background: var(--soft) !important; }
        .row-close td { border-top: 1.5px solid var(--accent); }
        .txn-no { color: var(--muted); font-size: 11px; }
        .empty { text-align: center; color: var(--muted); padding: 22px; }

        /* ---- Closing line ---- */
        .netstrip { margin-top: 14px; padding: 10px 14px; border: 1px solid var(--line);
            border-left: 4px solid var(--accent); border-radius: 4px;
            display: flex; justify-content: space-between; align-items: center; }
        .netstrip .ns-lbl { font-weight: 700; color: var(--ink); }
        .netstrip .ns-val { font-size: 18px; font-weight: 800; font-variant-numeric: tabular-nums;
            color: <?= $closing < 0 ? 'var(--red)' : 'var(--green)' ?>; }

        /* ---- Footer ---- */
        .foot { margin-top: 18px; padding-top: 10px; border-top: 1px solid var(--line);
            color: var(--muted); font-size: 10.5px; display: flex; justify-content: space-between; }

        .actions { text-align: center; padding: 16px 0; }
        .actions button { padding: 8px 18px; margin: 0 4px; border: 1px solid var(--line); border-radius: 6px; font-weight: 600; cursor: pointer; background: #fff; color: var(--ink); }
        .actions .btn-print { background: var(--accent); color: #fff; border-color: var(--accent); }

        @media print { .noprint { display: none !important; } .sheet { max-width: none; } }
    </style>
</head>
<body <?= $noPrint ? '' : 'onload="window.print()"' ?>>
<div class="sheet">
    <!-- Header -->
    <div class="stmt-head">
        <div>
            <div class="brand-name"><?= esc($firm['name'] ?? $appName) ?></div>
            <div class="brand-sub">Account Statement</div>
        </div>
        <div class="head-meta">
            <div class="hm-lbl">Statement Date</div>
            <div class="hm-val"><?= esc(date('d M Y')) ?></div>
        </div>
    </div>

    <!-- Party + meta -->
    <div class="info">
        <div>
            <div class="who-label">Statement For</div>
            <div class="who-name"><?= esc($party) ?></div>
            <div class="who-tag">Party / Account</div>
        </div>
        <div class="meta-box">
            <div class="meta-line"><span>Period</span><b><?= esc($range) ?></b></div>
            <div class="meta-line"><span>Entries</span><b><?= number_format((int) $count) ?></b></div>
            <?php if ($noPrint && $pdfTotalRows !== null && (int) $pdfTotalRows > $pdfShownRows): ?>
                <div class="meta-line"><span>PDF Rows</span><b><?= number_format($pdfShownRows) ?> of <?= number_format((int) $pdfTotalRows) ?><?= $pdfOffset > 0 ? ' from #' . number_format($pdfOffset + 1) : '' ?></b></div>
            <?php endif; ?>
            <div class="meta-line"><span>Generated</span><b><?= esc($genOn) ?></b></div>
        </div>
    </div>

    <!-- Summary strip -->
    <div class="cards">
        <div class="card head-cell c-open"><div class="c-lbl">Opening</div><div class="c-val"><?= $money($opening) ?></div></div>
        <div class="card c-jama"><div class="c-lbl">Total Jama</div><div class="c-val"><?= $money($totalJama) ?></div></div>
        <div class="card c-naam"><div class="c-lbl">Total Naam</div><div class="c-val"><?= $money($totalNaam) ?></div></div>
        <div class="card c-net"><div class="c-lbl">Net (J − N)</div><div class="c-val"><?= $money($net) ?></div></div>
        <div class="card head-cell c-close"><div class="c-lbl">Closing</div><div class="c-val"><?= $money($closing) ?></div></div>
    </div>

    <!-- Ledger table -->
    <div class="tbl-wrap">
        <table>
            <thead>
                <tr>
                    <th style="width:30px">#</th><th>Date</th><th>Txn No</th><th>Type</th><th>Mode</th>
                    <th class="num">Jama (In)</th><th class="num">Naam (Out)</th><th class="num">Balance</th>
                </tr>
            </thead>
            <tbody>
                <tr class="row-open">
                    <td></td><td colspan="4">Opening Balance</td>
                    <td class="num">—</td><td class="num">—</td><td class="num"><?= $money($opening) ?></td>
                </tr>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="8" class="empty">No transactions for this account in the selected range.</td></tr>
                <?php else: $i = 1; foreach ($rows as $r): ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= esc(date('d M Y', strtotime($r['txn_date']))) ?></td>
                        <td class="txn-no"><?= esc($r['txn_no'] ?: '—') ?></td>
                        <td class="<?= $r['type'] === 'jama' ? 'type-j' : 'type-n' ?>"><?= $r['type'] === 'jama' ? 'Jama' : 'Naam' ?></td>
                        <td><?= esc(ucfirst((string) $r['payment_mode'])) ?></td>
                        <td class="num jama"><?= $r['type'] === 'jama' ? $money($r['amount']) : '' ?></td>
                        <td class="num naam"><?= $r['type'] === 'naam' ? $money($r['amount']) : '' ?></td>
                        <td class="num bal"><?= $money($r['balance']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr class="row-close">
                    <td></td><td colspan="4">Closing Balance</td>
                    <td class="num jama"><?= $money($totalJama) ?></td>
                    <td class="num naam"><?= $money($totalNaam) ?></td>
                    <td class="num bal"><?= $money($closing) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Closing line -->
    <div class="netstrip">
        <div class="ns-lbl"><?= $closing < 0 ? 'Net Payable / Balance' : 'Net Receivable / Balance' ?></div>
        <div class="ns-val"><?= $money($closing) ?></div>
    </div>

    <!-- Footer -->
    <div class="foot">
        <span>Generated by <?= esc($appName) ?> · <?= esc($genOn) ?></span>
        <span>This is a computer-generated statement.</span>
    </div>

    <?php if (! $noPrint): ?>
    <div class="actions noprint">
        <button type="button" class="btn-print" data-window="print">Print / Save PDF</button>
        <button type="button" data-window="close">Close</button>
    </div>
    <script>
        // CSP-clean: no inline on* attributes on the buttons above.
        document.querySelectorAll('[data-window]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (this.getAttribute('data-window') === 'print') { window.print(); }
                else { window.close(); }
            });
        });
    </script>
    <?php endif; ?>
</div>
</body>
</html>
