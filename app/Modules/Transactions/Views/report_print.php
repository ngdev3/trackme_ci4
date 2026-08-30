<?php
/** Standalone printable Rokad Parcha for a period (no app chrome).
 *  $noPrint = true when rendered for PDF (dompdf) — no auto-print, no buttons. */
$fmt = fn ($n) => number_format((float) $n, 2);
$noPrint = $noPrint ?? false;
// dompdf's default font lacks the ₹ glyph, so use "Rs" for PDF; ₹ for browser print.
$cur = $noPrint ? 'Rs ' : '₹ ';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rokadh Parcha — <?= esc($period->label) ?></title>
    <style>
        @page { margin: 1.4cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: "DejaVu Sans", "Segoe UI", Arial, sans-serif; color: #1b2436; font-size: 12.5px; }
        h1 { font-size: 20px; margin: 0; }
        .head { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 6px; }
        .head small { color: #555; }
        .period { text-align: center; font-weight: 600; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 8px; }
        th { background: #f0f0f0; text-align: left; }
        .num { text-align: right; }
        tfoot td, .open td { font-weight: bold; background: #fafafa; }
        .jama { color: #16a34a; } .naam { color: #dc2626; }
        .summary { margin-top: 14px; text-align: right; }
        .summary div { margin: 2px 0; }
        .summary b { display: inline-block; min-width: 130px; text-align: left; }
        @media print { .noprint { display: none; } body { margin: 8px; } }
    </style>
</head>
<body>
    <div class="head">
        <h1><?= esc($firm['name'] ?? 'Rokadh Parcha') ?></h1>
        <small>Rokadh Parcha (Cash Book)</small>
    </div>
    <div class="period"><?= esc($period->label) ?></div>

    <table>
        <thead>
            <tr>
                <th style="width:34px">#</th><th>Date</th><th>Txn No</th><th>Party</th><th>Mode</th>
                <th class="num">Jama (In)</th><th class="num">Naam (Out)</th><th class="num">Balance</th>
            </tr>
        </thead>
        <tbody>
            <tr class="open"><td></td><td colspan="4">Opening Balance</td><td class="num"></td><td class="num"></td><td class="num"><?= $fmt($opening) ?></td></tr>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" style="text-align:center;color:#777">No transactions in this period.</td></tr>
            <?php else: $i = 1; foreach ($rows as $r): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= esc(date('d-m-Y', strtotime($r['txn_date']))) ?></td>
                    <td><?= esc($r['txn_no']) ?></td>
                    <td><?= esc($r['name']) ?></td>
                    <td><?= esc(ucfirst($r['payment_mode'])) ?></td>
                    <td class="num jama"><?= $r['type'] === 'jama' ? $fmt($r['amount']) : '' ?></td>
                    <td class="num naam"><?= $r['type'] === 'naam' ? $fmt($r['amount']) : '' ?></td>
                    <td class="num"><?= $fmt($r['balance']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr><td></td><td colspan="4">Closing Balance</td><td class="num jama"><?= $fmt($totalJama) ?></td><td class="num naam"><?= $fmt($totalNaam) ?></td><td class="num"><?= $fmt($closing) ?></td></tr>
        </tfoot>
    </table>

    <div class="summary">
        <div><b>Opening Balance:</b> <?= $cur . $fmt($opening) ?></div>
        <div><b>Total Jama:</b> <?= $cur . $fmt($totalJama) ?></div>
        <div><b>Total Naam:</b> <?= $cur . $fmt($totalNaam) ?></div>
        <div><b>Closing Balance:</b> <?= $cur . $fmt($closing) ?></div>
        <div><b>Carry Forward:</b> <?= $cur . $fmt($carry) ?></div>
    </div>

    <?php if (! $noPrint): ?>
    <p class="noprint" style="text-align:center;margin-top:20px">
        <button type="button" data-window="print">Print</button>
        <button type="button" data-window="close">Close</button>
    </p>
    <script>
        // CSP-clean: no inline on* attributes on the buttons above.
        document.querySelectorAll('[data-window]').forEach(function (b) {
            b.addEventListener('click', function () {
                if (this.getAttribute('data-window') === 'print') { window.print(); }
                else { window.close(); }
            });
        });
        // Auto-print once loaded (replaces the old body-load handler).
        window.addEventListener('load', function () { window.print(); });
    </script>
    <?php endif; ?>
</body>
</html>
