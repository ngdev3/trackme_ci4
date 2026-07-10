<?php
/** Standalone printable Breakdown report (no app chrome).
 *  $noPrint = true when rendered for PDF (dompdf) — no auto-print. */
use Modules\Transactions\Controllers\ReportController;
use App\Models\TransactionModel;

$noPrint = $noPrint ?? false;
// dompdf's default font lacks the ₹ glyph, so use "Rs" for PDF; ₹ for browser print.
$cur = $noPrint ? 'Rs ' : '₹ ';
$amt = fn ($n) => $cur . number_format((float) $n, 2);
$dmy = fn ($d) => date('d-m-Y', strtotime($d));

$NONE  = TransactionModel::UNSET_VALUE;
$shown = ($filters['ptype'] ?? '') === ''
    ? ''
    : 'Party type: ' . ($filters['ptype'] === $NONE ? 'Unspecified' : $filters['ptype']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Breakdown — <?= esc($dmy($from)) ?> to <?= esc($dmy($to)) ?></title>
    <style>
        @page { margin: 1.4cm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body { font-family: "DejaVu Sans", "Segoe UI", Arial, sans-serif; color: #1b2436; font-size: 12.5px; }
        h1 { font-size: 20px; margin: 0; }
        h2 { font-size: 14px; margin: 18px 0 6px; }
        .head { text-align: center; border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 6px; }
        .head small { color: #555; }
        .period { text-align: center; font-weight: 600; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #999; padding: 5px 8px; }
        th { background: #f0f0f0; text-align: left; }
        .num { text-align: right; }
        .muted { color: #666; font-style: italic; }
        .note { color: #666; font-size: 11px; margin-top: 4px; }
        .jama { color: #16a34a; } .naam { color: #dc2626; }
        .summary { margin-top: 14px; text-align: right; }
        .summary div { margin: 2px 0; }
        .summary b { display: inline-block; min-width: 130px; text-align: left; }
        @media print { body { margin: 8px; } }
    </style>
</head>
<body <?= $noPrint ? '' : 'onload="window.print()"' ?>>
    <div class="head">
        <h1><?= esc($firm['name'] ?? 'Breakdown Report') ?></h1>
        <small>Report — by party type and payment mode</small>
    </div>
    <div class="period">
        <?= esc($dmy($from)) ?> &ndash; <?= esc($dmy($to)) ?>
        <?php if ($shown !== ''): ?>
            <div class="note">Filtered &mdash; <?= esc($shown) ?>. Totals cover only the matching entries.</div>
        <?php endif; ?>
    </div>

    <?php foreach (ReportController::GROUPS as $key => $meta): $rows = $groups[$key]; ?>
        <h2><?= esc($meta['title']) ?></h2>
        <table>
            <thead>
                <tr>
                    <th><?= $key === 'party_type' ? 'Type' : 'Mode' ?></th>
                    <th class="num" style="width:70px">Entries</th>
                    <th class="num" style="width:110px">Jama</th>
                    <th class="num" style="width:110px">Naam</th>
                    <th class="num" style="width:110px">Net</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="5" class="muted">Nothing recorded in this range.</td></tr>
            <?php else: foreach ($rows as $g): ?>
                <tr>
                    <td>
                        <?php if ($g['label'] === ''): ?>
                            <span class="muted"><?= esc($meta['empty']) ?></span>
                        <?php elseif ($key === 'payment_mode'): ?>
                            <?= esc(TransactionModel::MODE_LABELS[$g['label']] ?? ucfirst($g['label'])) ?>
                        <?php else: ?>
                            <?= esc($g['label']) ?>
                        <?php endif; ?>
                    </td>
                    <td class="num"><?= number_format($g['count']) ?></td>
                    <td class="num jama"><?= $g['jama'] ? $amt($g['jama']) : '—' ?></td>
                    <td class="num naam"><?= $g['naam'] ? $amt($g['naam']) : '—' ?></td>
                    <td class="num"><?= $amt($g['net']) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    <?php endforeach; ?>

    <div class="summary">
        <div><b>Total Jama</b> <span class="jama"><?= $amt($summary['jama']) ?></span></div>
        <div><b>Total Naam</b> <span class="naam"><?= $amt($summary['naam']) ?></span></div>
        <div><b>Net</b> <?= $amt($summary['net']) ?></div>
    </div>
</body>
</html>
