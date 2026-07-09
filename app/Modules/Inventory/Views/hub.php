<?php
/** Inventory worker hub — large touch-friendly action tiles. */
$tiles = [
    ['inward',  'Stock Inward',  'Goods received', 'bi-box-arrow-in-down', 'in',   site_url('inventory/inward'), ! empty($canAdd)],
    ['outward', 'Stock Outward', 'Goods dispatched','bi-box-arrow-up',      'out',  '#', false],
    ['search',  'Stock Search',  'Find stock',      'bi-search',            'find', '#', false],
    ['verify',  'Verify Stock',  'Physical check',  'bi-clipboard-check',   'chk',  '#', false],
    ['closing', 'Daily Closing', 'Close the day',   'bi-calendar-check',    'day',  '#', false],
    ['owner',   'Reports',       'Owner dashboard', 'bi-graph-up',          'rep',  '#', false],
];
?>
<div class="inv-hub">
    <div class="inv-hub-head">
        <div>
            <h2 class="mb-0"><i class="bi bi-box-seam me-2"></i>Inventory</h2>
            <p class="text-secondary mb-0">Tap a big button to begin.</p>
        </div>
        <div class="inv-hub-stats">
            <div class="inv-stat in"><span><?= number_format($todayIn) ?></span><small>Bags in today</small></div>
            <div class="inv-stat out"><span><?= number_format($todayOut) ?></span><small>Bags out today</small></div>
        </div>
    </div>

    <div class="inv-tiles">
        <?php foreach ($tiles as [$key, $label, $sub, $icon, $tone, $url, $enabled]): ?>
            <?php if ($enabled): ?>
                <a class="inv-tile <?= esc($tone) ?>" href="<?= esc($url, 'attr') ?>">
                    <span class="inv-tile-ic"><i class="bi <?= esc($icon) ?>"></i></span>
                    <span class="inv-tile-label"><?= esc($label) ?></span>
                    <span class="inv-tile-sub"><?= esc($sub) ?></span>
                </a>
            <?php else: ?>
                <span class="inv-tile disabled" aria-disabled="true" title="Coming soon">
                    <span class="inv-tile-ic"><i class="bi <?= esc($icon) ?>"></i></span>
                    <span class="inv-tile-label"><?= esc($label) ?></span>
                    <span class="inv-tile-sub">Coming soon</span>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
