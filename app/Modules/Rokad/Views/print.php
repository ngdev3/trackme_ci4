<?php
/** Standalone printable Rokad Parcha for one day (no app chrome). */
$fmt = fn ($n) => number_format((float) $n, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rokad Parcha — <?= esc(date('d-m-Y', strtotime($date))) ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: "Segoe UI", Arial, sans-serif; color: #1b2436; margin: 24px; font-size: 13px; }
        h1 { font-size: 20px; margin: 0; }
        .head { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 14px; }
        .head small { color: #555; }
        .meta { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 6px 8px; }
        th { background: #f0f0f0; text-align: left; }
        .num { text-align: right; }
        tfoot td, .open td { font-weight: bold; background: #fafafa; }
        .summary { margin-top: 14px; display: flex; gap: 24px; justify-content: flex-end; font-size: 13px; }
        .summary b { display: inline-block; min-width: 110px; }
        @media print { .noprint { display: none; } body { margin: 8px; } }
    </style>
</head>
<body onload="window.print()">
    <div class="head">
        <h1><?= esc($firm['name'] ?? 'Rokad Parcha') ?></h1>
        <small>Rokad Parcha (Cash Book) &middot; <?= esc(date('l, d F Y', strtotime($date))) ?></small>
    </div>

    <table>
        <thead>
            <tr><th style="width:40px">#</th><th>Particular</th><th class="num">Jama (In)</th><th class="num">Naam (Out)</th><th class="num">Balance</th><th>Remarks</th></tr>
        </thead>
        <tbody>
            <tr class="open"><td></td><td>Opening Balance</td><td></td><td></td><td class="num"><?= $fmt($opening) ?></td><td></td></tr>
            <?php if (empty($entries)): ?>
                <tr><td colspan="6" style="text-align:center;color:#777">No transactions.</td></tr>
            <?php else: $i = 1; foreach ($entries as $e): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= esc($e['particular']) ?></td>
                    <td class="num"><?= (float) $e['jama'] > 0 ? $fmt($e['jama']) : '' ?></td>
                    <td class="num"><?= (float) $e['naam'] > 0 ? $fmt($e['naam']) : '' ?></td>
                    <td class="num"><?= $fmt($e['balance']) ?></td>
                    <td><?= esc($e['remarks'] ?: '') ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
        <tfoot>
            <tr><td></td><td>Closing Balance</td><td class="num"><?= $fmt($totalJama) ?></td><td class="num"><?= $fmt($totalNaam) ?></td><td class="num"><?= $fmt($closing) ?></td><td></td></tr>
        </tfoot>
    </table>

    <div class="summary">
        <div>
            <div><b>Opening:</b> <?= $fmt($opening) ?></div>
            <div><b>Total Jama:</b> <?= $fmt($totalJama) ?></div>
            <div><b>Total Naam:</b> <?= $fmt($totalNaam) ?></div>
            <div><b>Closing:</b> <?= $fmt($closing) ?></div>
        </div>
    </div>

    <p class="noprint" style="text-align:center;margin-top:20px">
        <button onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </p>
</body>
</html>
