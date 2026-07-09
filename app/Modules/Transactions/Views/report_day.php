<?php
/**
 * Rokad Parcha — daily two-column cash register (Jama / Naam).
 * Rendered in layout.php. All labels are English so the in-app language
 * switcher (Google-translate engine) renders them in the selected language.
 */
$fmt = fn ($n) => number_format((float) $n, 2);
$srcBadge = static function (?string $s): string {
    return $s === 'app'
        ? '<span class="rp-badge rp-badge-app"><i class="bi bi-phone"></i> App</span>'
        : '<span class="rp-badge rp-badge-web"><i class="bi bi-display"></i> Web</span>';
};
$dmy = fn ($d) => date('d-m-Y', strtotime($d));

$jama = array_filter($rows, fn ($r) => $r['type'] === 'jama');
$naam = array_filter($rows, fn ($r) => $r['type'] === 'naam');
$closing = $carry;
// The opening balance (b/d) sits on the Jama (receipts) side, so the Jama
// column total includes it and the columns reconcile to the closing balance.
$jamaColTotal = $opening + $totalJama;
?>
<div class="rp-wrap">
    <!-- Header -->
    <div class="rp-head">
        <h1 class="rp-title">Rokadh Parcha <span class="rp-sub">Daily cash register &mdash; Jama / Naam</span></h1>
        <form method="get" class="rp-datebox">
            <input type="hidden" name="period" value="day">
            <div>
                <label class="form-label d-block">Date</label>
                <input type="date" name="date" value="<?= esc($period->from) ?>" class="form-control">
            </div>
            <a href="<?= site_url('transactions/report') . '?period=day&date=' . $prevDate ?>" class="btn btn-outline-secondary"><i class="bi bi-chevron-left"></i> Prev</a>
            <button class="btn btn-success"><i class="bi bi-search"></i> Search</button>
            <a href="<?= site_url('transactions/report') . '?period=day&date=' . $nextDate ?>" class="btn btn-outline-secondary">Next <i class="bi bi-chevron-right"></i></a>
            <a href="<?= site_url('transactions/report/print') . '?period=day&date=' . $period->from ?>" target="_blank" class="btn btn-primary"><i class="bi bi-printer"></i> PDF / Print</a>
            <a href="<?= site_url('transactions/report/deleted') . '?date=' . $period->from ?>" class="btn btn-outline-secondary"><i class="bi bi-trash"></i> Deleted Entries</a>
        </form>
    </div>

    <!-- Legend + quick actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="rp-legend">
            <span>Source:</span> <?= $srcBadge('web') ?> <span>entered from web panel</span>
            <?= $srcBadge('app') ?> <span>entered from mobile app &middot; click any entry to view full details.</span>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= site_url('transactions/list') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-ul"></i> Ledger</a>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url('transactions/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Entry</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Day card -->
    <div class="rp-card mt-2">
        <div class="rp-date-h">Date: <?= esc($dmy($period->from)) ?></div>

        <!-- Shri Rokad Nagad — opening cash for this financial year -->
        <div class="rp-shri">
            <div class="rp-shri-info">
                <i class="bi bi-cash-stack"></i>
                <div>
                    <div class="rp-shri-lbl"><?= esc($shriLabel) ?> &middot; FY <?= esc($fyLabel) ?> <small class="text-secondary">(opening cash on 1 Apr<?= $shriAuto ? ' · auto-carried from previous year' : '' ?>)</small></div>
                    <div class="rp-shri-val">&#8377; <?= $fmt($shri) ?></div>
                </div>
            </div>
            <?php if (can($moduleCode, 'edit')): ?>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#shriEdit"><i class="bi bi-pencil"></i> Set / Edit</button>
                    <a href="<?= site_url('transactions/opening') ?>" class="btn btn-sm btn-outline-secondary" title="All financial years"><i class="bi bi-gear"></i></a>
                </div>
            <?php endif; ?>
        </div>
        <?php if (can($moduleCode, 'edit')): ?>
            <div class="collapse" id="shriEdit">
                <form action="<?= site_url('transactions/opening') ?>" method="post" class="rp-shri-form">
                    <?= csrf_field() ?>
                    <input type="hidden" name="fy" value="<?= (int) $fyStart ?>">
                    <input type="hidden" name="return" value="<?= esc(site_url('transactions/report') . '?period=day&date=' . $period->from, 'attr') ?>">
                    <label class="form-label mb-0 small"><?= esc($shriLabel) ?> for FY <?= esc($fyLabel) ?></label>
                    <div class="input-group input-group-sm" style="max-width:280px">
                        <span class="input-group-text">&#8377;</span>
                        <input type="number" step="0.01" name="amount" class="form-control" value="<?= $shri != 0.0 ? esc(rtrim(rtrim(number_format($shri, 2, '.', ''), '0'), '.')) : '' ?>" placeholder="0.00" autofocus>
                        <button class="btn btn-primary"><i class="bi bi-save"></i> Save</button>
                    </div>
                    <small class="text-secondary">Carries forward to every month &amp; day of FY <?= esc($fyLabel) ?>.</small>
                </form>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <!-- Jama -->
            <div class="col-md-6">
                <div class="rp-col rp-col-jama">
                    <div class="rp-col-title"><i class="bi bi-arrow-down"></i> Jama (In)</div>

                    <!-- Opening balance (b/d) — the Shri Rokad Nagad carried into this date -->
                    <div class="rp-entry rp-entry-open">
                        <div class="rp-amt"><?= $fmt($opening) ?></div>
                        <div class="rp-mid">
                            <div class="rp-party"><?= esc($shriLabel) ?> <span class="rp-id">(opening b/d)</span></div>
                            <div class="rp-meta"><i class="bi bi-arrow-return-right"></i> carried into <?= esc($dmy($period->from)) ?></div>
                        </div>
                    </div>

                    <?php foreach ($jama as $r): ?>
                        <div class="rp-entry">
                            <div class="rp-amt"><?= $fmt($r['amount']) ?></div>
                            <div class="rp-mid">
                                <div class="rp-party"><?= esc($r['name']) ?> <span class="rp-id">(ID-<?= hid($r['id']) ?>)</span></div>
                                <div class="rp-meta"><?= $srcBadge($r['source'] ?? 'web') ?> <?php if (! empty($r['notes'])): ?><span><?= esc(character_limiter($r['notes'], 30)) ?></span><?php endif; ?></div>
                            </div>
                            <div class="rp-acts">
                                <button type="button" class="rp-act rp-view" title="View" data-tx-view data-id="<?= hid($r['id']) ?>"><i class="bi bi-eye"></i></button>
                                <?php if (can($moduleCode, 'edit')): ?><a class="rp-act rp-edit" href="<?= site_url('transactions/edit/' . hid($r['id'])) ?>" title="Edit"><i class="bi bi-pencil"></i></a><?php endif; ?>
                                <?php if (can($moduleCode, 'delete')): ?>
                                    <button type="button" class="rp-act rp-del" title="Delete" data-tx-delete data-action="<?= site_url('transactions/delete/' . hid($r['id'])) ?>" data-label="<?= esc($r['txn_no'], 'attr') ?>"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="rp-total">
                        <div class="rp-total-lbl">Total Jama <small class="text-secondary">(incl. opening)</small></div>
                        <div class="rp-total-val"><?= $fmt($jamaColTotal) ?></div>
                    </div>
                </div>
            </div>

            <!-- Naam -->
            <div class="col-md-6">
                <div class="rp-col rp-col-naam">
                    <div class="rp-col-title"><i class="bi bi-arrow-up"></i> Naam (Out)</div>
                    <?php if (empty($naam)): ?>
                        <div class="rp-empty"><i class="bi bi-inbox d-block fs-3 opacity-50 mb-1"></i>No Naam entries</div>
                    <?php else: foreach ($naam as $r): ?>
                        <div class="rp-entry">
                            <div class="rp-amt"><?= $fmt($r['amount']) ?></div>
                            <div class="rp-mid">
                                <div class="rp-party"><?= esc($r['name']) ?> <span class="rp-id">(ID-<?= hid($r['id']) ?>)</span></div>
                                <div class="rp-meta"><?= $srcBadge($r['source'] ?? 'web') ?> <?php if (! empty($r['notes'])): ?><span><?= esc(character_limiter($r['notes'], 30)) ?></span><?php endif; ?></div>
                            </div>
                            <div class="rp-acts">
                                <button type="button" class="rp-act rp-view" title="View" data-tx-view data-id="<?= hid($r['id']) ?>"><i class="bi bi-eye"></i></button>
                                <?php if (can($moduleCode, 'edit')): ?><a class="rp-act rp-edit" href="<?= site_url('transactions/edit/' . hid($r['id'])) ?>" title="Edit"><i class="bi bi-pencil"></i></a><?php endif; ?>
                                <?php if (can($moduleCode, 'delete')): ?>
                                    <button type="button" class="rp-act rp-del" title="Delete" data-tx-delete data-action="<?= site_url('transactions/delete/' . hid($r['id'])) ?>" data-label="<?= esc($r['txn_no'], 'attr') ?>"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                    <div class="rp-total">
                        <div class="rp-total-lbl">Total Naam</div>
                        <div class="rp-total-val"><?= $fmt($totalNaam) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Closing / carry forward -->
        <div class="rp-balance <?= $closing < 0 ? 'is-negative' : '' ?>">
            <div class="rp-balance-title">Closing Balance : &#8377; <?= $fmt($closing) ?></div>
            <div class="rp-balance-sub">Carried forward to the next date</div>
        </div>

        <div class="text-center mt-3">
            <a href="<?= site_url('transactions/report') . '?period=month' ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-table"></i> Period report (month / quarter / FY / custom)</a>
        </div>
    </div>
</div>

<?= view('Modules\Transactions\Views\_modals') ?>
