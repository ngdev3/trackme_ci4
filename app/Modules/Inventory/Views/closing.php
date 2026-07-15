<?php
/**
 * Task 7 — Daily Closing. Big, glanceable end-of-day summary with a single close
 * action. Shows received / dispatched / closing / difference / pending. Once
 * closed the day is locked; owner/admin can reopen. Exports to PDF/Excel/CSV/Print.
 */
$fmt   = static fn ($n) => number_format((float) $n, 0);
$fmtW  = static fn ($n) => number_format((float) $n, 2);
$s     = $summary;
$closed   = $existing && $existing['status'] === 'closed';
$reopened = $existing && $existing['status'] === 'reopened';
$isFuture = $date > date('Y-m-d');
$qs = '?date=' . urlencode($date);
?>
<div class="inv-form-wrap wide">
    <div class="inv-form-card">
        <div class="inv-form-head day">
            <a href="<?= site_url('inventory') ?>" class="inv-back"><i class="bi bi-arrow-left"></i></a>
            <div>
                <h2><i class="bi bi-calendar-check me-2"></i>Daily Closing</h2>
                <p><?= esc(date('l, d M Y', strtotime($date))) ?><?= $isToday ? ' · Today' : '' ?></p>
            </div>
        </div>

        <div class="inv-form" style="gap:1.1rem;">

            <!-- Date picker -->
            <form method="get" action="<?= site_url('inventory/closing') ?>" class="inv-close-datebar">
                <label><i class="bi bi-calendar3 me-1"></i>View date</label>
                <input type="date" name="date" value="<?= esc($date, 'attr') ?>" max="<?= date('Y-m-d') ?>" class="form-control" onchange="this.form.submit()">
            </form>

            <!-- Status banner -->
            <?php if ($closed): ?>
                <div class="inv-close-status closed">
                    <i class="bi bi-lock-fill"></i>
                    <div>
                        <strong>Closed</strong>
                        <span>Locked<?= ! empty($existing['closed_at']) ? ' on ' . esc(date('d M Y, H:i', strtotime($existing['closed_at']))) : '' ?>. Entries for this day cannot be edited.</span>
                    </div>
                    <?php if ($canApprove): ?>
                        <form method="post" action="<?= site_url('inventory/closing/reopen') ?>" data-no-validate data-confirm="Entries for this day can be edited again once reopened." data-confirm-title="Reopen this day?" data-confirm-btn="Yes, reopen" data-confirm-icon="warning">
                            <?= csrf_field() ?>
                            <input type="hidden" name="date" value="<?= esc($date, 'attr') ?>">
                            <button class="btn btn-light border"><i class="bi bi-unlock me-1"></i>Reopen</button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php elseif ($reopened): ?>
                <div class="inv-close-status reopened">
                    <i class="bi bi-unlock-fill"></i>
                    <div><strong>Reopened</strong><span>This day was reopened for edits<?= ! empty($existing['reopened_at']) ? ' on ' . esc(date('d M Y, H:i', strtotime($existing['reopened_at']))) : '' ?>.</span></div>
                </div>
            <?php endif; ?>

            <!-- The 5 required metric cards -->
            <div class="inv-close-cards">
                <div class="inv-close-card in">
                    <span class="ic"><i class="bi bi-box-arrow-in-down"></i></span>
                    <span class="v">+<?= $fmt($s['received_bags']) ?></span>
                    <span class="l">Received Today</span>
                    <span class="sub"><?= $fmtW($s['received_weight']) ?> kg</span>
                </div>
                <div class="inv-close-card out">
                    <span class="ic"><i class="bi bi-box-arrow-up"></i></span>
                    <span class="v">−<?= $fmt($s['dispatched_bags']) ?></span>
                    <span class="l">Dispatched Today</span>
                    <span class="sub"><?= $fmtW($s['dispatched_weight']) ?> kg</span>
                </div>
                <div class="inv-close-card stock">
                    <span class="ic"><i class="bi bi-boxes"></i></span>
                    <span class="v"><?= $fmt($s['closing_bags']) ?></span>
                    <span class="l">Closing Stock</span>
                    <span class="sub">bags in hand</span>
                </div>
                <div class="inv-close-card diff <?= $s['difference_bags'] > 0 ? 'up' : ($s['difference_bags'] < 0 ? 'down' : '') ?>">
                    <span class="ic"><i class="bi bi-arrow-left-right"></i></span>
                    <span class="v"><?= $s['difference_bags'] > 0 ? '+' : '' ?><?= $fmt($s['difference_bags']) ?></span>
                    <span class="l">Stock Difference</span>
                    <span class="sub">adjustments today</span>
                </div>
                <div class="inv-close-card pending <?= $s['pending_corrections'] > 0 ? 'alert' : '' ?>">
                    <span class="ic"><i class="bi bi-hourglass-split"></i></span>
                    <span class="v"><?= $fmt($s['pending_corrections']) ?></span>
                    <span class="l">Pending Corrections</span>
                    <span class="sub"><a href="<?= site_url('inventory/corrections') ?>">review</a></span>
                </div>
            </div>

            <!-- Opening → closing recap -->
            <div class="inv-close-recap">
                <span>Opening <strong><?= $fmt($s['opening_bags']) ?></strong></span><i class="bi bi-plus"></i>
                <span>In <strong class="in"><?= $fmt($s['received_bags']) ?></strong></span><i class="bi bi-dash"></i>
                <span>Out <strong class="out"><?= $fmt($s['dispatched_bags']) ?></strong></span>
                <?php if ($s['adjustment_bags'] != 0): ?><i class="bi bi-plus-slash-minus"></i><span>Adj <strong><?= $fmt($s['adjustment_bags']) ?></strong></span><?php endif; ?>
                <i class="bi bi-arrow-right"></i><span>Closing <strong class="stock"><?= $fmt($s['closing_bags']) ?></strong></span>
                <span class="ec"><?= (int) $s['entry_count'] ?> entr<?= (int) $s['entry_count'] === 1 ? 'y' : 'ies' ?></span>
            </div>

            <!-- Close action -->
            <?php if (! $closed && ! $isFuture && ! empty($canClose)): ?>
                <form method="post" action="<?= site_url('inventory/closing/close') ?>" data-no-validate data-confirm="Workers will not be able to add or edit entries for <?= esc($date) ?> until it is reopened." data-confirm-title="Close inventory for the day?" data-confirm-btn="Yes, close" data-confirm-icon="warning">
                    <?= csrf_field() ?>
                    <input type="hidden" name="date" value="<?= esc($date, 'attr') ?>">
                    <input type="text" name="notes" class="form-control mb-2" placeholder="Closing note (optional)">
                    <button class="inv-save day w-100"><i class="bi bi-lock me-2"></i>Close <?= $isToday ? "Today's" : "This Day's" ?> Inventory</button>
                </form>
            <?php elseif ($isFuture): ?>
                <div class="inv-verify-hint"><i class="bi bi-info-circle"></i> You can only close today or a past date.</div>
            <?php endif; ?>

            <!-- Exports -->
            <div class="inv-close-exports">
                <span class="lbl">Daily Closing Report:</span>
                <a class="btn btn-sm btn-light border" href="<?= site_url('inventory/closing/report/pdf') . $qs ?>"><i class="bi bi-file-earmark-pdf me-1"></i>PDF</a>
                <a class="btn btn-sm btn-light border" href="<?= site_url('inventory/closing/report/xlsx') . $qs ?>"><i class="bi bi-file-earmark-excel me-1"></i>Excel</a>
                <a class="btn btn-sm btn-light border" href="<?= site_url('inventory/closing/report/csv') . $qs ?>"><i class="bi bi-filetype-csv me-1"></i>CSV</a>
                <a class="btn btn-sm btn-light border" href="<?= site_url('inventory/closing/print') . $qs ?>" target="_blank"><i class="bi bi-printer me-1"></i>Print</a>
            </div>

            <!-- History -->
            <?php if (! empty($history)): ?>
                <div>
                    <h3 class="inv-close-h3"><i class="bi bi-clock-history me-1"></i>Recent Closings</h3>
                    <div class="inv-close-history">
                        <?php foreach ($history as $h): ?>
                            <a class="inv-close-hrow <?= $h['status'] === 'closed' ? 'closed' : 'reopened' ?>" href="<?= site_url('inventory/closing?date=' . $h['closing_date']) ?>">
                                <span class="d"><?= esc(date('d M Y', strtotime($h['closing_date']))) ?></span>
                                <span class="n"><i class="bi bi-box-arrow-in-down"></i><?= $fmt($h['received_bags']) ?> <i class="bi bi-box-arrow-up ms-2"></i><?= $fmt($h['dispatched_bags']) ?></span>
                                <span class="c"><?= $fmt($h['closing_bags']) ?> bags</span>
                                <span class="s"><?= $h['status'] === 'closed' ? 'Closed' : 'Reopened' ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
