<?php
/** Task 8 — Generic report print / PDF page. Self-contained styles for Dompdf + print. */
$firmName = $firm['name'] ?? ($firm['company_name'] ?? 'Company');
$pdf   = $pdf ?? false;
$align = $report['align'] ?? [];
$cls   = static fn ($i) => (($align[$i] ?? 'l') === 'r') ? ' class="num"' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= esc($label) ?></title>
<style>
    * { box-sizing: border-box; }
    body { font-family: DejaVu Sans, Arial, sans-serif; color: #1f2937; margin: 20px; font-size: 11px; }
    h1 { font-size: 16px; margin: 0 0 2px; }
    .muted { color: #6b7280; }
    .head { border-bottom: 2px solid #2563eb; padding-bottom: 8px; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #e5e7eb; padding: 4px 6px; text-align: left; }
    th { background: #f3f4f6; }
    td.num, th.num { text-align: right; font-variant-numeric: tabular-nums; }
    tfoot td { font-weight: bold; background: #f9fafb; }
    .foot { margin-top: 18px; color: #9ca3af; font-size: 10px; }
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
        <div class="muted">
            <?= esc($label) ?>
            <?php if (! empty($needsRange)): ?> — <?= esc(date('d M Y', strtotime($range['from']))) ?> to <?= esc(date('d M Y', strtotime($range['to']))) ?><?php endif; ?>
        </div>
    </div>

    <?php if (empty($report['rows'])): ?>
        <div class="muted">No data for this report.</div>
    <?php else: ?>
        <table>
            <thead>
                <tr><?php foreach ($report['columns'] as $i => $c): ?><th<?= $cls($i) ?>><?= esc($c) ?></th><?php endforeach; ?></tr>
            </thead>
            <tbody>
                <?php foreach ($report['rows'] as $row): ?>
                    <tr><?php foreach ($row as $i => $cell): ?><td<?= $cls($i) ?>><?= esc($cell) ?></td><?php endforeach; ?></tr>
                <?php endforeach; ?>
            </tbody>
            <?php if (! empty($report['totals'])): ?>
                <tfoot><tr><?php foreach ($report['totals'] as $i => $cell): ?><td<?= $cls($i) ?>><?= esc($cell) ?></td><?php endforeach; ?></tr></tfoot>
            <?php endif; ?>
        </table>
    <?php endif; ?>

    <div class="foot">Generated <?= esc(date('d M Y, H:i')) ?> · <?= count($report['rows']) ?> rows</div>
</body>
</html>
