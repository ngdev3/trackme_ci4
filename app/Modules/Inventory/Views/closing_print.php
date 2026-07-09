<?php
/**
 * Daily Closing Report — used for both the printable page and the PDF export
 * (Dompdf). Self-contained styles so it renders identically in both. Shows the
 * closing figures and the day's entry detail.
 */
$s   = $summary;
$fmt = static fn ($n) => number_format((float) $n, 2);
$n0  = static fn ($n) => number_format((float) $n, 0);
$firmName = $firm['name'] ?? ($firm['company_name'] ?? 'Company');
$pdf = $pdf ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Daily Closing <?= esc($date) ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; margin: 24px; font-size: 12px; }
    h1 { font-size: 18px; margin: 0 0 2px; }
    .muted { color: #6b7280; }
    .head { border-bottom: 2px solid #7c3aed; padding-bottom: 10px; margin-bottom: 16px; }
    .grid { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
    .grid td { border: 1px solid #e5e7eb; padding: 8px 10px; }
    .grid td.k { color: #6b7280; width: 55%; }
    .grid td.v { text-align: right; font-weight: bold; font-variant-numeric: tabular-nums; }
    .big td.v { font-size: 15px; }
    .up { color: #16a34a; } .down { color: #dc2626; }
    table.detail { width: 100%; border-collapse: collapse; }
    table.detail th, table.detail td { border: 1px solid #e5e7eb; padding: 5px 7px; text-align: left; }
    table.detail th { background: #f3f4f6; }
    table.detail td.num { text-align: right; font-variant-numeric: tabular-nums; }
    .status { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold; }
    .status.closed { background: #dcfce7; color: #15803d; }
    .status.reopened { background: #fef3c7; color: #b45309; }
    .status.live { background: #e0f2fe; color: #0369a1; }
    .foot { margin-top: 22px; color: #9ca3af; font-size: 10px; }
    @media print { .noprint { display: none; } body { margin: 0; } }
</style>
</head>
<body>
    <?php if (! $pdf): ?>
        <div class="noprint" style="text-align:right;margin-bottom:10px;">
            <button onclick="window.print()" style="padding:8px 16px;font-size:13px;cursor:pointer;">🖨 Print</button>
        </div>
    <?php endif; ?>

    <div class="head">
        <h1><?= esc($firmName) ?></h1>
        <div class="muted">Daily Closing Report — <?= esc(date('l, d M Y', strtotime($date))) ?></div>
        <div style="margin-top:6px;">
            <?php if ($existing && $existing['status'] === 'closed'): ?>
                <span class="status closed">CLOSED</span>
            <?php elseif ($existing && $existing['status'] === 'reopened'): ?>
                <span class="status reopened">REOPENED</span>
            <?php else: ?>
                <span class="status live">LIVE (not yet closed)</span>
            <?php endif; ?>
        </div>
    </div>

    <table class="grid">
        <tr><td class="k">Opening Stock</td><td class="v"><?= $n0($s['opening_bags']) ?> bags</td></tr>
        <tr><td class="k">Total Stock Received Today</td><td class="v up">+<?= $n0($s['received_bags']) ?> bags <span class="muted">(<?= $fmt($s['received_weight']) ?> kg)</span></td></tr>
        <tr><td class="k">Total Stock Dispatched Today</td><td class="v down">−<?= $n0($s['dispatched_bags']) ?> bags <span class="muted">(<?= $fmt($s['dispatched_weight']) ?> kg)</span></td></tr>
        <tr><td class="k">Adjustments Today</td><td class="v"><?= $n0($s['adjustment_bags']) ?> bags</td></tr>
        <tr class="big"><td class="k">Closing Stock</td><td class="v"><?= $n0($s['closing_bags']) ?> bags</td></tr>
        <tr><td class="k">Stock Difference</td><td class="v <?= $s['difference_bags'] > 0 ? 'up' : ($s['difference_bags'] < 0 ? 'down' : '') ?>"><?= $s['difference_bags'] > 0 ? '+' : '' ?><?= $n0($s['difference_bags']) ?> bags</td></tr>
        <tr><td class="k">Pending Corrections</td><td class="v"><?= (int) $s['pending_corrections'] ?></td></tr>
        <tr><td class="k">Entries Today</td><td class="v"><?= (int) $s['entry_count'] ?></td></tr>
    </table>

    <h3 style="margin:0 0 6px;">Entries (<?= count($entries) ?>)</h3>
    <?php if (empty($entries)): ?>
        <div class="muted">No stock movements on this day.</div>
    <?php else: ?>
        <table class="detail">
            <thead>
                <tr><th>#</th><th>Entry No</th><th>Type</th><th>Product</th><th>Godown</th><th>Party</th><th class="num">Bags</th><th class="num">Weight</th><th>Time</th></tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $i => $e): ?>
                    <tr>
                        <td><?= $i + 1 ?></td>
                        <td><?= esc($e['entry_no']) ?></td>
                        <td><?= esc(ucfirst($e['movement_type'])) ?></td>
                        <td><?= esc($e['product_name']) ?></td>
                        <td><?= esc($e['warehouse_name']) ?></td>
                        <td><?= esc($e['party_name'] ?: '—') ?></td>
                        <td class="num"><?= ((int) $e['direction'] === 1 ? '+' : '−') . $n0($e['bags']) ?></td>
                        <td class="num"><?= $fmt($e['weight']) ?></td>
                        <td><?= esc(date('H:i', strtotime($e['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <div class="foot">Generated <?= esc(date('d M Y, H:i')) ?><?= $existing && ! empty($existing['closed_at']) ? ' · Closed ' . esc(date('d M Y, H:i', strtotime($existing['closed_at']))) : '' ?></div>
</body>
</html>
