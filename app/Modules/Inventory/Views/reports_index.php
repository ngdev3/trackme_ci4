<?php
/** Task 8 — Reports hub. One card per report; each opens a filterable, exportable table. */
$blurb = [
    'product'    => 'Current stock and value by product.',
    'warehouse'  => 'Stock and utilisation by godown.',
    'party'      => 'Received & dispatched by supplier/customer.',
    'lot'        => 'Opening vs remaining bags per lot.',
    'inward'     => 'All goods received in a date range.',
    'outward'    => 'All goods dispatched in a date range.',
    'difference' => 'Every counted difference and its outcome.',
    'pending'    => 'Corrections awaiting owner/admin approval.',
    'movement'   => 'The full stock ledger for a date range.',
];
?>
<div class="inv-ws">
    <div class="inv-ws-head">
        <div>
            <h2 class="mb-0"><i class="bi bi-graph-up me-2"></i>Inventory Reports</h2>
            <p class="text-secondary mb-0">Drill down, then export to PDF, Excel, CSV or print.</p>
        </div>
        <div class="inv-ws-head-actions">
            <a href="<?= site_url('inventory/dashboard') ?>" class="btn btn-outline-secondary"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
        </div>
    </div>

    <div class="inv-report-cards">
        <?php foreach ($reports as $key => $r): ?>
            <a class="inv-report-card" href="<?= site_url('inventory/reports/' . $key) ?>">
                <span class="ic"><i class="bi <?= esc($r[1]) ?>"></i></span>
                <span class="t"><?= esc($r[0]) ?></span>
                <span class="d"><?= esc($blurb[$key] ?? '') ?></span>
                <?php if ($r[2]): ?><span class="badge">date range</span><?php endif; ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>
