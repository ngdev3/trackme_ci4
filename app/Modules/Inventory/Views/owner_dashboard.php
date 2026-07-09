<?php
/**
 * Task 8 — Owner Dashboard. A single glance at the whole operation: current
 * inventory & value, today's flow, pending corrections, utilisation, old stock
 * and who's been active. Links straight into the nine reports.
 */
$fmt  = static fn ($n) => number_format((float) $n, 0);
$fmtW = static fn ($n) => number_format((float) $n, 2);
?>
<div class="inv-ws">
    <div class="inv-ws-head">
        <div>
            <h2 class="mb-0"><i class="bi bi-speedometer2 me-2"></i>Owner Dashboard</h2>
            <p class="text-secondary mb-0">Your full inventory picture, live.</p>
        </div>
        <div class="inv-ws-head-actions">
            <a href="<?= site_url('inventory/reports') ?>" class="btn inv-btn-in"><i class="bi bi-graph-up me-1"></i>All Reports</a>
            <a href="<?= site_url('inventory/closing') ?>" class="btn btn-outline-secondary"><i class="bi bi-calendar-check me-1"></i>Daily Closing</a>
            <a href="<?= site_url('inventory') ?>" class="btn btn-outline-secondary"><i class="bi bi-box-seam me-1"></i>Workspace</a>
        </div>
    </div>

    <!-- ===== Owner cards ===== -->
    <div class="inv-own-grid">
        <div class="inv-own-card blue">
            <span class="ic"><i class="bi bi-boxes"></i></span>
            <div><span class="v"><?= $fmt($d['current_bags']) ?></span><span class="l">Current Inventory</span><span class="s"><?= $fmtW($d['current_weight']) ?> kg</span></div>
        </div>
        <div class="inv-own-card green">
            <span class="ic"><i class="bi bi-cash-stack"></i></span>
            <div><span class="v"><?= $d['inventory_value'] > 0 ? money($d['inventory_value']) : '—' ?></span><span class="l">Inventory Value</span><span class="s"><?= $d['inventory_value'] > 0 ? 'at product rates' : 'set product rates' ?></span></div>
        </div>
        <div class="inv-own-card up">
            <span class="ic"><i class="bi bi-box-arrow-in-down"></i></span>
            <div><span class="v">+<?= $fmt($d['received_today']) ?></span><span class="l">Received Today</span></div>
        </div>
        <div class="inv-own-card down">
            <span class="ic"><i class="bi bi-box-arrow-up"></i></span>
            <div><span class="v">−<?= $fmt($d['dispatched_today']) ?></span><span class="l">Dispatched Today</span></div>
        </div>
        <a class="inv-own-card amber <?= $d['pending_corrections'] > 0 ? 'pulse' : '' ?>" href="<?= site_url('inventory/corrections') ?>">
            <span class="ic"><i class="bi bi-hourglass-split"></i></span>
            <div><span class="v"><?= $fmt($d['pending_corrections']) ?></span><span class="l">Pending Corrections</span><span class="s">tap to review</span></div>
        </a>
        <div class="inv-own-card <?= $d['stock_difference'] > 0 ? 'up' : ($d['stock_difference'] < 0 ? 'down' : 'slate') ?>">
            <span class="ic"><i class="bi bi-arrow-left-right"></i></span>
            <div><span class="v"><?= $d['stock_difference'] > 0 ? '+' : '' ?><?= $fmt($d['stock_difference']) ?></span><span class="l">Stock Difference</span><span class="s">net adjustments</span></div>
        </div>
        <div class="inv-own-card violet">
            <span class="ic"><i class="bi bi-buildings"></i></span>
            <div>
                <span class="v"><?= $d['warehouse_cap'] > 0 ? $d['warehouse_util'] . '%' : '—' ?></span>
                <span class="l">Warehouse Utilisation</span>
                <span class="s"><?= $d['warehouse_cap'] > 0 ? $fmt($d['warehouse_used']) . ' / ' . $fmt($d['warehouse_cap']) . ' bags' : 'no capacity set' ?></span>
            </div>
            <?php if ($d['warehouse_cap'] > 0): ?><div class="inv-util-bar"><span style="width: <?= min(100, (float) $d['warehouse_util']) ?>%"></span></div><?php endif; ?>
        </div>
        <div class="inv-own-card slate">
            <span class="ic"><i class="bi bi-hourglass-bottom"></i></span>
            <div><span class="v"><?= $fmt($d['old_stock']) ?></span><span class="l">Old Stock</span><span class="s">bags &gt; 30 days</span></div>
        </div>
    </div>

    <div class="row g-3 mt-1">
        <!-- User activity -->
        <div class="col-lg-5">
            <div class="inv-panel">
                <div class="inv-panel-head"><h3><i class="bi bi-person-lines-fill me-1"></i>User Activity — Today</h3></div>
                <div class="inv-panel-body">
                    <?php if (empty($d['user_activity'])): ?>
                        <div class="inv-empty-mini"><i class="bi bi-person"></i>No entries recorded today.</div>
                    <?php else: ?>
                        <ul class="inv-user-activity">
                            <?php foreach ($d['user_activity'] as $u): ?>
                                <li>
                                    <span class="ua-name"><i class="bi bi-person-circle me-1"></i><?= esc($u['name'] ?: 'Unknown') ?></span>
                                    <span class="ua-count"><?= (int) $u['entries'] ?> entr<?= (int) $u['entries'] === 1 ? 'y' : 'ies' ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Reports quick links -->
        <div class="col-lg-7">
            <div class="inv-panel">
                <div class="inv-panel-head"><h3><i class="bi bi-graph-up me-1"></i>Reports</h3></div>
                <div class="inv-panel-body">
                    <div class="inv-report-links">
                        <?php foreach ($reports as $key => $r): ?>
                            <a href="<?= site_url('inventory/reports/' . $key) ?>"><i class="bi <?= esc($r[1]) ?>"></i><?= esc($r[0]) ?></a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
