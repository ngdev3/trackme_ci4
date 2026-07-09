<?php
/**
 * Task 8 — Generic report view. Renders any normalised report dataset (columns +
 * rows + optional totals) with an optional date-range filter and export buttons.
 */
$qs = $needsRange ? ('?from=' . urlencode($range['from']) . '&to=' . urlencode($range['to'])) : '';
$align = $report['align'] ?? [];
$cls   = static fn ($i) => (($align[$i] ?? 'l') === 'r') ? ' class="num"' : '';
?>
<div class="inv-ws">
    <div class="inv-ws-head">
        <div>
            <h2 class="mb-0"><i class="bi bi-table me-2"></i><?= esc($label) ?></h2>
            <p class="text-secondary mb-0"><?= count($report['rows']) ?> row<?= count($report['rows']) === 1 ? '' : 's' ?><?= $needsRange ? ' · ' . esc(date('d M Y', strtotime($range['from']))) . ' → ' . esc(date('d M Y', strtotime($range['to']))) : '' ?></p>
        </div>
        <div class="inv-ws-head-actions">
            <a href="<?= site_url('inventory/reports') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Reports</a>
        </div>
    </div>

    <div class="inv-report-toolbar">
        <?php if ($needsRange): ?>
            <form method="get" class="inv-report-range">
                <label>From <input type="date" name="from" value="<?= esc($range['from'], 'attr') ?>" class="form-control" max="<?= date('Y-m-d') ?>"></label>
                <label>To <input type="date" name="to" value="<?= esc($range['to'], 'attr') ?>" class="form-control" max="<?= date('Y-m-d') ?>"></label>
                <button class="btn btn-primary"><i class="bi bi-funnel me-1"></i>Apply</button>
            </form>
        <?php endif; ?>
        <div class="inv-report-exports">
            <a class="btn btn-sm btn-light border" href="<?= site_url('inventory/reports/export/' . $key . '/pdf') . $qs ?>"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
            <a class="btn btn-sm btn-light border" href="<?= site_url('inventory/reports/export/' . $key . '/xlsx') . $qs ?>"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
            <a class="btn btn-sm btn-light border" href="<?= site_url('inventory/reports/export/' . $key . '/csv') . $qs ?>"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
            <a class="btn btn-sm btn-light border" href="<?= site_url('inventory/reports/print/' . $key) . $qs ?>" target="_blank"><i class="bi bi-printer me-1"></i>Print</a>
        </div>
    </div>

    <div class="inv-report-tablewrap">
        <?php if (empty($report['rows'])): ?>
            <div class="inv-empty-card"><i class="bi bi-inboxes"></i><h3>Nothing to show</h3><p>No data for this report<?= $needsRange ? ' in the selected dates' : '' ?>.</p></div>
        <?php else: ?>
            <table class="inv-report-table">
                <thead>
                    <tr><?php foreach ($report['columns'] as $i => $c): ?><th<?= $cls($i) ?>><?= esc($c) ?></th><?php endforeach; ?></tr>
                </thead>
                <tbody>
                    <?php foreach ($report['rows'] as $row): ?>
                        <tr><?php foreach ($row as $i => $cell): ?><td<?= $cls($i) ?>><?= esc($cell) ?></td><?php endforeach; ?></tr>
                    <?php endforeach; ?>
                </tbody>
                <?php if (! empty($report['totals'])): ?>
                    <tfoot>
                        <tr><?php foreach ($report['totals'] as $i => $cell): ?><td<?= $cls($i) ?>><?= esc($cell) ?></td><?php endforeach; ?></tr>
                    </tfoot>
                <?php endif; ?>
            </table>
        <?php endif; ?>
    </div>
</div>
