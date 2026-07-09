<?php
/**
 * Inventory workspace — flexible home that adapts to empty vs populated state.
 * Big primary action for workers, live stock + activity for owners.
 */
$fmt  = static fn ($n) => number_format((float) $n, 0);
$fmtW = static fn ($n) => number_format((float) $n, 2);

// Quick actions. `on` = route exists today; others show as muted "soon" chips.
$actions = [
    ['New Inward',   'bi-box-arrow-in-down', 'in',   site_url('inventory/inward'), ! empty($canAdd)],
    ['Stock Outward','bi-box-arrow-up',      'out',  '#', false],
    ['Search',       'bi-search',            'find', '#', false],
    ['Verify',       'bi-clipboard-check',   'chk',  '#', false],
    ['Daily Closing','bi-calendar-check',    'day',  '#', false],
    ['Reports',      'bi-graph-up',          'rep',  '#', false],
];
?>
<div class="inv-ws">

    <!-- ===== Header + primary actions ===== -->
    <div class="inv-ws-head">
        <div>
            <h2 class="mb-0"><i class="bi bi-box-seam me-2"></i>Inventory</h2>
            <p class="text-secondary mb-0">Your live stock at a glance.</p>
        </div>
        <div class="inv-ws-head-actions">
            <?php if (! empty($canAdd)): ?>
                <a href="<?= site_url('inventory/inward') ?>" class="btn inv-btn-in"><i class="bi bi-box-arrow-in-down me-1"></i>New Inward</a>
            <?php endif; ?>
            <?php if (! empty($canEdit)): ?>
                <a href="<?= site_url('inventory/masters') ?>" class="btn btn-outline-secondary"><i class="bi bi-gear me-1"></i>Setup</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- ===== Quick action rail (scrolls on mobile) ===== -->
    <div class="inv-action-rail">
        <?php foreach ($actions as [$label, $icon, $tone, $url, $on]): ?>
            <?php if ($on): ?>
                <a class="inv-action <?= esc($tone) ?>" href="<?= esc($url, 'attr') ?>">
                    <span class="inv-action-ic"><i class="bi <?= esc($icon) ?>"></i></span><span><?= esc($label) ?></span>
                </a>
            <?php else: ?>
                <span class="inv-action soon" title="Coming soon">
                    <span class="inv-action-ic"><i class="bi <?= esc($icon) ?>"></i></span><span><?= esc($label) ?></span>
                    <em>soon</em>
                </span>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if (empty($hasMasters)): ?>
        <!-- ===== Empty: need setup ===== -->
        <div class="inv-empty-card">
            <i class="bi bi-box-seam"></i>
            <h3>Let’s set up your inventory</h3>
            <p>Add your products and godowns once — then recording stock takes just a few taps.</p>
            <?php if (! empty($canEdit)): ?>
                <a href="<?= site_url('inventory/masters') ?>" class="btn inv-btn-in btn-lg"><i class="bi bi-gear me-1"></i>Set up Products &amp; Godowns</a>
            <?php else: ?>
                <p class="text-muted small">Ask your owner/admin to add products and godowns.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>

        <!-- ===== Stat tiles ===== -->
        <div class="inv-stats">
            <div class="inv-stat-tile total"><span class="v"><?= $fmt($totalBags) ?></span><span class="l">Bags in stock</span></div>
            <div class="inv-stat-tile wt"><span class="v"><?= $fmtW($totalWt) ?></span><span class="l">Total kg</span></div>
            <div class="inv-stat-tile in"><span class="v">+<?= $fmt($todayIn) ?></span><span class="l">In today</span></div>
            <div class="inv-stat-tile out"><span class="v">−<?= $fmt($todayOut) ?></span><span class="l">Out today</span></div>
            <div class="inv-stat-tile prod"><span class="v"><?= $fmt($productCount) ?></span><span class="l">Products</span></div>
            <div class="inv-stat-tile low <?= $lowCount > 0 ? 'alert' : '' ?>"><span class="v"><?= $fmt($lowCount) ?></span><span class="l">Low stock</span></div>
        </div>

        <div class="row g-3 mt-1">
            <!-- ===== Current stock (searchable cards) ===== -->
            <div class="col-xl-7">
                <div class="inv-panel">
                    <div class="inv-panel-head">
                        <h3><i class="bi bi-boxes me-1"></i>Current Stock</h3>
                        <div class="inv-search-sm">
                            <i class="bi bi-search"></i>
                            <input type="search" id="invStockSearch" placeholder="Search product or godown…" aria-label="Search stock">
                        </div>
                    </div>
                    <div class="inv-panel-body">
                        <?php if (empty($stock)): ?>
                            <div class="inv-empty-mini"><i class="bi bi-inboxes"></i>No stock yet. Record a <a href="<?= site_url('inventory/inward') ?>">Stock Inward</a> to begin.</div>
                        <?php else: ?>
                            <div class="inv-stock-grid" id="invStockGrid">
                                <?php foreach ($stock as $s):
                                    $bags = (float) $s['bags'];
                                    $low  = (int) $s['low_stock'];
                                    if ($bags <= 0)              { $st = ['out', 'Out of stock', '#dc2626']; }
                                    elseif ($low > 0 && $bags <= $low) { $st = ['low', 'Low stock', '#f59e0b']; }
                                    else                          { $st = ['ok', 'In stock', '#16a34a']; }
                                ?>
                                    <div class="inv-stock-card" data-search="<?= esc(strtolower($s['product_name'] . ' ' . $s['warehouse_name']), 'attr') ?>">
                                        <div class="inv-stock-top">
                                            <span class="inv-stock-name"><?= esc($s['product_name']) ?></span>
                                            <span class="inv-stock-badge" style="--c: <?= $st[2] ?>"><?= esc($st[1]) ?></span>
                                        </div>
                                        <div class="inv-stock-bags"><?= $fmt($bags) ?><small>bags</small></div>
                                        <div class="inv-stock-meta">
                                            <span><i class="bi bi-buildings"></i><?= esc($s['warehouse_name']) ?></span>
                                            <span><i class="bi bi-basket"></i><?= $fmtW($s['weight']) ?> kg</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div id="invStockEmpty" class="inv-empty-mini" hidden><i class="bi bi-search"></i>No stock matches your search.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ===== Recent activity ===== -->
            <div class="col-xl-5">
                <div class="inv-panel">
                    <div class="inv-panel-head"><h3><i class="bi bi-clock-history me-1"></i>Recent Activity</h3></div>
                    <div class="inv-panel-body">
                        <?php if (empty($recent)): ?>
                            <div class="inv-empty-mini"><i class="bi bi-inbox"></i>No movements yet.</div>
                        <?php else: ?>
                            <ul class="inv-feed">
                                <?php foreach ($recent as $r):
                                    $isIn = (int) $r['direction'] === 1;
                                ?>
                                    <li>
                                        <span class="inv-feed-ic <?= $isIn ? 'in' : 'out' ?>"><i class="bi <?= $isIn ? 'bi-arrow-down' : 'bi-arrow-up' ?>"></i></span>
                                        <span class="inv-feed-main">
                                            <span class="inv-feed-name"><?= esc($r['product_name']) ?> <small>· <?= esc($r['warehouse_name']) ?></small></span>
                                            <span class="inv-feed-meta"><?= esc($r['entry_no']) ?> · <?= esc(date('d M, H:i', strtotime($r['created_at']))) ?><?= ! empty($r['party_name']) ? ' · ' . esc($r['party_name']) : '' ?></span>
                                        </span>
                                        <span class="inv-feed-bags <?= $isIn ? 'in' : 'out' ?>"><?= $isIn ? '+' : '−' ?><?= $fmt($r['bags']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var box = document.getElementById('invStockSearch');
    if (!box) { return; }
    var cards = Array.prototype.slice.call(document.querySelectorAll('.inv-stock-card'));
    var empty = document.getElementById('invStockEmpty');
    box.addEventListener('input', function () {
        var q = box.value.trim().toLowerCase(), n = 0;
        cards.forEach(function (c) {
            var hit = !q || c.getAttribute('data-search').indexOf(q) !== -1;
            c.style.display = hit ? '' : 'none'; if (hit) { n++; }
        });
        if (empty) { empty.hidden = n !== 0; }
    });
})();
</script>
