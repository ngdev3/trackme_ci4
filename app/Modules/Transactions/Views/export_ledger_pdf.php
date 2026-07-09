<?php
/** Jama-Naam ledger — PDF layout (rendered by dompdf, no scripts). */
$fmt = fn ($n) => number_format((float) $n, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1.4cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: "DejaVu Sans", sans-serif; color: #1b2436; font-size: 11px; }
        h1 { font-size: 18px; margin: 0; }
        .head { text-align: center; border-bottom: 2px solid #333; padding-bottom: 8px; margin-bottom: 10px; }
        .head small { color: #555; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 4px 6px; }
        th { background: #eef; text-align: left; }
        .num { text-align: right; }
        .jama { color: #16a34a; } .naam { color: #dc2626; }
        tfoot td { font-weight: bold; background: #fafafa; }
        .summary { margin-top: 12px; text-align: right; }
        .summary div { margin: 2px 0; }
        .summary b { display: inline-block; min-width: 120px; text-align: left; }
    </style>
</head>
<body>
    <div class="head">
        <h1><?= esc($firm['name'] ?? 'Jama / Naam Ledger') ?></h1>
        <small>Transactions Ledger &middot; Generated <?= date('d M Y, H:i') ?></small>
    </div>

    <table>
        <thead>
            <tr>
                <th>Txn No</th><th>Date</th><th>Party</th><th>Type</th><th>Mode</th>
                <th class="num">Amount</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" style="text-align:center;color:#777">No transactions.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= esc($r['txn_no']) ?></td>
                    <td><?= esc(date('d-m-Y', strtotime($r['txn_date']))) ?></td>
                    <td><?= esc($r['name']) ?></td>
                    <td class="<?= $r['type'] ?>"><?= esc(ucfirst($r['type'])) ?></td>
                    <td><?= esc(ucfirst((string) $r['payment_mode'])) ?></td>
                    <td class="num <?= $r['type'] ?>"><?= $fmt($r['amount']) ?></td>
                    <td><?= esc(ucfirst($r['status'])) ?></td>
                </tr>
            <?php endforeach; endif; ?>
        </tbody>
    </table>

    <div class="summary">
        <div><b>Total Jama:</b> <?= $fmt($summary['jama']) ?></div>
        <div><b>Total Naam:</b> <?= $fmt($summary['naam']) ?></div>
        <div><b>Net Balance:</b> <?= $fmt($summary['net']) ?></div>
        <div><b>Transactions:</b> <?= (int) $summary['count'] ?></div>
    </div>
</body>
</html>
