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
$firm = function_exists('current_company') ? current_company() : null;

// Existing parties (accounts) for the searchable "Party" dropdown in the add
// popup: a stable short code (ID), and a one-line balance/activity description
// so similarly-named accounts are easy to tell apart.
$partyCode = static function (string $name): string {
    $ini = '';
    foreach (preg_split('/\s+/', trim($name)) as $w) { if ($w !== '') { $ini .= strtoupper($w[0]); } }
    $ini = substr($ini !== '' ? $ini : 'P', 0, 3);
    return $ini . '-' . strtoupper(substr(md5($name), 0, 4));
};
$partyOptions = array_map(static function ($p) use ($partyCode, $fmt) {
    $net  = (float) $p['net'];
    $tone = $net > 0 ? 'jama' : ($net < 0 ? 'naam' : 'neutral');
    $sign = $net < 0 ? '-' : '';
    $desc = 'Bal ' . $sign . '₹' . $fmt(abs($net))
        . ' · ' . (int) $p['count'] . ' ' . ((int) $p['count'] === 1 ? 'entry' : 'entries')
        . (! empty($p['last_date']) ? ' · last ' . date('d M y', strtotime($p['last_date'])) : '');
    return ['name' => (string) $p['name'], 'code' => $partyCode((string) $p['name']), 'desc' => $desc, 'net' => $net, 'tone' => $tone];
}, $parties ?? []);

// Classification: how the money moved (payment mode) and who the party is (party type).
$partyTypes = $partyTypes ?? \App\Models\TransactionModel::PARTY_TYPES;

$modeIcons = ['cash' => 'bi-cash-coin', 'upi' => 'bi-phone', 'bank' => 'bi-bank', 'cheque' => 'bi-journal-text', 'card' => 'bi-credit-card', 'other' => 'bi-three-dots'];
$rowChips  = static function (array $r) use ($modeIcons): string {
    $m   = ($r['payment_mode'] ?? '') ?: 'cash';
    $lbl = \App\Models\TransactionModel::MODE_LABELS[$m] ?? ucfirst((string) $m);
    $out = '<span class="rp-chip rp-mode-' . esc($m, 'attr') . '"><i class="bi ' . ($modeIcons[$m] ?? 'bi-three-dots') . '"></i>' . esc($lbl) . '</span>';

    if (! empty($r['party_type'])) {
        $out .= '<span class="rp-chip rp-chip-party"><i class="bi bi-person"></i>' . esc($r['party_type']) . '</span>';
    }

    return $out;
};

// Provenance line: who added the entry, when, and fresh vs restored (with count).
$authors  = $authors ?? [];
$rowInfo  = static function (array $r) use ($authors): string {
    $who  = $authors[(int) ($r['user_id'] ?? 0)] ?? 'Unknown';
    $when = ! empty($r['created_at']) ? date('d M Y, h:i A', strtotime($r['created_at'])) : '';
    $rc   = (int) ($r['restore_count'] ?? 0);
    $tag  = $rc > 0
        ? '<span class="rp-info-restored" title="Deleted &amp; restored ' . $rc . ' time' . ($rc === 1 ? '' : 's')
            . (! empty($r['restored_at']) ? ', last on ' . esc(date('d M Y, h:i A', strtotime($r['restored_at'])), 'attr') : '')
            . (! empty($r['delete_reason']) ? ' — reason: ' . esc($r['delete_reason'], 'attr') : '')
            . '"><i class="bi bi-arrow-counterclockwise"></i> Restored ' . $rc . '&times;</span>'
        : '<span class="rp-info-fresh"><i class="bi bi-stars"></i> Fresh</span>';

    return '<i class="bi bi-person-circle"></i> <b>' . esc($who) . '</b>'
        . ($when ? ' &middot; ' . esc($when) : '')
        . ' &middot; ' . $tag;
};

// Rich, styled hover tooltip content (HTML) shown by the custom tooltip engine.
$viewTip = static function (array $r) use ($authors, $fmt): string {
    $who    = $authors[(int) ($r['user_id'] ?? 0)] ?? 'Unknown';
    $isJama = $r['type'] === 'jama';
    $rc     = (int) ($r['restore_count'] ?? 0);

    $h  = '<div class="t-top"><b>' . esc($r['name']) . '</b><span class="t-no">#' . esc($r['txn_no']) . '</span></div>';
    $h .= '<div class="t-amt ' . ($isJama ? 't-jama' : 't-naam') . '">' . ($isJama ? '+' : '−') . ' &#8377; ' . $fmt($r['amount'])
        . ' <span class="t-dir">' . ($isJama ? 'Jama · In' : 'Naam · Out') . '</span></div>';
    $h .= '<div class="t-hr"></div>';

    $chips = '<span class="t-chip">' . esc(\App\Models\TransactionModel::MODE_LABELS[$r['payment_mode']] ?? ucfirst((string) $r['payment_mode'])) . '</span>';
    if (! empty($r['party_type'])) { $chips .= '<span class="t-chip">' . esc($r['party_type']) . '</span>'; }
    $chips .= '<span class="t-chip">' . (($r['source'] ?? 'web') === 'app' ? 'App' : 'Web') . '</span>';
    $h .= '<div class="t-chips">' . $chips . '</div>';

    $h .= '<div class="t-row"><i class="bi bi-person-circle"></i> ' . esc($who)
        . ' · ' . esc(date('d M Y', strtotime($r['txn_date'])))
        . (! empty($r['created_at']) ? ', ' . esc(date('h:i A', strtotime($r['created_at']))) : '') . '</div>';
    $h .= '<div class="t-row">' . ($rc > 0
        ? '<span class="t-restored"><i class="bi bi-arrow-counterclockwise"></i> Restored ' . $rc . '× (was deleted)</span>'
        : '<span class="t-fresh"><i class="bi bi-stars"></i> Fresh entry</span>') . '</div>';
    if (! empty($r['notes'])) { $h .= '<div class="t-note">“' . esc(character_limiter((string) $r['notes'], 70)) . '”</div>'; }
    $h .= '<div class="t-foot">Click the row to open full details</div>';

    return $h;
};
?>
<div class="rp-wrap">
    <!-- Firm / company name -->
    <?php if (! empty($firm['name'])): ?>
        <div class="rp-firm">
            <i class="bi bi-shop"></i>
            <span class="rp-firm-name"><?= esc($firm['name']) ?></span>
            <?php if (! empty($firm['state'])): ?><span class="rp-firm-meta"><?= esc($firm['state']) ?></span><?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- First-time nudge: how to set the opening balance -->
    <?php if (! empty($shriAuto) && can($moduleCode, 'edit')): ?>
        <div class="alert alert-info d-flex align-items-start gap-2 rp-openbal-tip" role="alert" data-openbal-tip>
            <i class="bi bi-info-circle-fill mt-1"></i>
            <div class="flex-grow-1">
                <strong>Set your Opening Balance.</strong>
                You haven't set an opening cash balance for FY <?= esc($fyLabel) ?> yet. You can set it anytime from
                <a href="<?= site_url('transactions/opening') ?>" class="alert-link">HissabKitaab Vahi &rsaquo; Opening Balance</a> in the menu.
            </div>
            <button type="button" class="btn-close" data-openbal-dismiss aria-label="Dismiss"></button>
        </div>
    <?php endif; ?>

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
            <?php if (sub_is_pro()): ?>
                <a href="<?= site_url('transactions/report/print') . '?period=day&date=' . $period->from ?>" target="_blank" class="btn btn-primary"><i class="bi bi-printer"></i> PDF / Print</a>
            <?php else: ?>
                <a href="<?= site_url('subscription') ?>" class="btn btn-outline-primary" title="Available on the paid plan"><i class="bi bi-lock"></i> PDF / Print</a>
            <?php endif; ?>
            <a href="<?= site_url('transactions/report/deleted') . '?date=' . $period->from ?>" class="btn btn-outline-secondary"><i class="bi bi-trash"></i> Deleted Entries</a>
        </form>
    </div>

    <!-- Legend + quick actions -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
        <div class="rp-legend">
            <span>Source:</span> <?= $srcBadge('web') ?> <span>entered from web panel</span>
            <?= $srcBadge('app') ?> <span>entered from mobile app &middot; click any entry to view full details.</span>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('transactions/list') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-ul"></i> Ledger</a>
            <?php if (sub_is_pro()): ?>
                <a href="<?= site_url('transactions/report/breakdown') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pie-chart"></i> Report</a>
            <?php else: ?>
                <a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-outline-primary" title="Available on the paid plan"><i class="bi bi-lock"></i> Report</a>
            <?php endif; ?>
            <?php if (can($moduleCode, 'add')): ?>
                <button type="button" class="btn btn-sm btn-success" data-rp-add="jama"><i class="bi bi-plus-lg"></i> Add Deposit (Jama)</button>
                <button type="button" class="btn btn-sm btn-danger" data-rp-add="naam"><i class="bi bi-dash-lg"></i> Add Expense (Naam)</button>
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
            <?php if (can($moduleCode, 'edit') && sub_is_pro()): ?>
                <div class="d-flex gap-2">
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#shriEdit"><i class="bi bi-pencil"></i> Set / Edit</button>
                    <a href="<?= site_url('transactions/opening') ?>" class="btn btn-sm btn-outline-secondary" title="All financial years"><i class="bi bi-gear"></i></a>
                </div>
            <?php elseif (can($moduleCode, 'edit')): ?>
                <a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-outline-primary" title="Available on the paid plan"><i class="bi bi-lock"></i> Set opening balance</a>
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
                <div class="rp-col rp-col-jama" data-side="jama">
                    <div class="rp-col-title"><i class="bi bi-arrow-down"></i> Jama (In)</div>

                    <!-- Opening balance (b/d) — the Shri Rokad Nagad carried into this date -->
                    <div class="rp-entry rp-entry-open">
                        <div class="rp-amt"><?= $fmt($opening) ?></div>
                        <div class="rp-mid">
                            <div class="rp-party">
                            <span class="rp-avatar rp-avatar-open"><i class="bi bi-cash-stack"></i></span>
                            <span class="rp-party-name"><?= esc($shriLabel) ?></span>
                            <span class="rp-id">opening b/d</span>
                        </div>
                            <div class="rp-meta"><i class="bi bi-arrow-return-right"></i> carried into <?= esc($dmy($period->from)) ?></div>
                        </div>
                    </div>

                    <?php foreach ($jama as $r): ?>
                        <div class="rp-entry rp-entry-open-view" data-tx-view data-id="<?= hid($r['id']) ?>" data-rp-tip="<?= esc($viewTip($r), 'attr') ?>">
                            <div class="rp-amt"><?= $fmt($r['amount']) ?></div>
                            <div class="rp-mid">
                                <div class="rp-party">
                                    <span class="rp-avatar" style="--rp-hue: <?= crc32((string) $r['name']) % 360 ?>"><?= esc(mb_strtoupper(mb_substr(trim((string) $r['name']), 0, 1)) ?: '?') ?></span>
                                    <span class="rp-party-name" title="<?= esc($r['name'], 'attr') ?>"><?= esc($r['name']) ?></span>
                                    <span class="rp-id">ID-<?= hid($r['id']) ?></span>
                                </div>
                                <div class="rp-meta"><?= $srcBadge($r['source'] ?? 'web') ?><?= $rowChips($r) ?><?php if (! empty($r['notes'])): ?><span><?= esc(character_limiter($r['notes'], 30)) ?></span><?php endif; ?></div>
                                <div class="rp-info"><?= $rowInfo($r) ?></div>
                            </div>
                            <div class="rp-acts">
                                <button type="button" class="rp-act rp-view" title="View details" data-tx-view data-id="<?= hid($r['id']) ?>"><i class="bi bi-eye"></i></button>
                                <?php if (can($moduleCode, 'edit')): ?><a class="rp-act rp-edit" href="<?= site_url('transactions/edit/' . hid($r['id'])) ?>" title="Edit"><i class="bi bi-pencil"></i></a><?php endif; ?>
                                <?php if (can($moduleCode, 'delete')): ?>
                                    <button type="button" class="rp-act rp-del" title="Delete" data-tx-delete data-action="<?= site_url('transactions/delete/' . hid($r['id'])) ?>" data-label="<?= esc($r['txn_no'], 'attr') ?>"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="rp-total">
                        <div class="rp-total-lbl">Total Jama <small class="text-secondary">(incl. opening)</small></div>
                        <div class="rp-total-val" data-jama-total><?= $fmt($jamaColTotal) ?></div>
                    </div>
                </div>
            </div>

            <!-- Naam -->
            <div class="col-md-6">
                <div class="rp-col rp-col-naam" data-side="naam">
                    <div class="rp-col-title"><i class="bi bi-arrow-up"></i> Naam (Out)</div>

                    <div class="rp-empty" data-naam-empty <?= empty($naam) ? '' : 'hidden' ?>><i class="bi bi-inbox d-block fs-3 opacity-50 mb-1"></i>No Naam entries</div>
                    <?php foreach ($naam as $r): ?>
                        <div class="rp-entry rp-entry-open-view" data-tx-view data-id="<?= hid($r['id']) ?>" data-rp-tip="<?= esc($viewTip($r), 'attr') ?>">
                            <div class="rp-amt"><?= $fmt($r['amount']) ?></div>
                            <div class="rp-mid">
                                <div class="rp-party">
                                    <span class="rp-avatar" style="--rp-hue: <?= crc32((string) $r['name']) % 360 ?>"><?= esc(mb_strtoupper(mb_substr(trim((string) $r['name']), 0, 1)) ?: '?') ?></span>
                                    <span class="rp-party-name" title="<?= esc($r['name'], 'attr') ?>"><?= esc($r['name']) ?></span>
                                    <span class="rp-id">ID-<?= hid($r['id']) ?></span>
                                </div>
                                <div class="rp-meta"><?= $srcBadge($r['source'] ?? 'web') ?><?= $rowChips($r) ?><?php if (! empty($r['notes'])): ?><span><?= esc(character_limiter($r['notes'], 30)) ?></span><?php endif; ?></div>
                                <div class="rp-info"><?= $rowInfo($r) ?></div>
                            </div>
                            <div class="rp-acts">
                                <button type="button" class="rp-act rp-view" title="View details" data-tx-view data-id="<?= hid($r['id']) ?>"><i class="bi bi-eye"></i></button>
                                <?php if (can($moduleCode, 'edit')): ?><a class="rp-act rp-edit" href="<?= site_url('transactions/edit/' . hid($r['id'])) ?>" title="Edit"><i class="bi bi-pencil"></i></a><?php endif; ?>
                                <?php if (can($moduleCode, 'delete')): ?>
                                    <button type="button" class="rp-act rp-del" title="Delete" data-tx-delete data-action="<?= site_url('transactions/delete/' . hid($r['id'])) ?>" data-label="<?= esc($r['txn_no'], 'attr') ?>"><i class="bi bi-trash"></i></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="rp-total">
                        <div class="rp-total-lbl">Total Naam</div>
                        <div class="rp-total-val" data-naam-total><?= $fmt($totalNaam) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Closing / carry forward -->
        <div class="rp-balance <?= $closing < 0 ? 'is-negative' : '' ?>" data-rp-balance>
            <div class="rp-balance-title">Closing Balance : &#8377; <span data-closing-val><?= $fmt($closing) ?></span></div>
            <div class="rp-balance-sub">Carried forward to the next date</div>
        </div>

        <div class="text-center mt-3">
            <a href="<?= site_url('transactions/report') . '?period=month' ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-table"></i> Period report (month / quarter / FY / custom)</a>
        </div>
    </div>
</div>

<?= view('Modules\Transactions\Views\_modals') ?>

<?php if (can($moduleCode, 'add')): ?>
<!-- Add Deposit (Jama) / Add Expense (Naam) — opened from the buttons above -->
<div class="modal fade" id="rpAddModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <form class="modal-content" id="rpAddForm" data-no-validate autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="type" id="rpAddType" value="jama">
            <input type="hidden" name="txn_date" value="<?= esc($period->from, 'attr') ?>">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2"><span class="rp-add-dot" id="rpAddDot"></span><span id="rpAddTitle">Add Deposit (Jama)</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Two columns: what you type on the left, how you classify it on the right. -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="mb-3 rp-combo" id="rpPartyCombo">
                            <label class="form-label">Party / account <span class="text-danger">*</span></label>
                            <div class="rp-combo-field">
                                <input type="text" name="name" id="rpAddName" class="form-control form-control-lg" placeholder="Search an account or type a new one…"
                                       autocomplete="off" role="combobox" aria-expanded="false" aria-controls="rpPartyMenu" aria-autocomplete="list">
                                <div class="rp-combo-menu" id="rpPartyMenu" role="listbox" hidden></div>
                            </div>
                            <div class="form-text">Pick an existing account (shows its code &amp; balance) or type a new name.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Amount (&#8377;) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="rpAddAmount" class="form-control form-control-lg" placeholder="0.00">
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Notes <span class="text-muted small">(optional)</span></label>
                            <input type="text" name="notes" id="rpAddNotes" class="form-control" placeholder="Any remark">
                        </div>
                    </div>

                    <div class="col-lg-6 rp-col-classify">
                        <div class="mb-3">
                            <label class="form-label">Paid by</label>
                            <div class="rp-modes" data-rp-modes role="radiogroup" aria-label="Payment mode">
                                <?php
                                $modeBtns = ['cash' => 'bi-cash-coin', 'upi' => 'bi-phone', 'bank' => 'bi-bank', 'cheque' => 'bi-journal-text', 'card' => 'bi-credit-card', 'other' => 'bi-three-dots'];
                                foreach ($modeBtns as $m => $icon): ?>
                                    <button type="button" class="rp-mode<?= $m === 'cash' ? ' active' : '' ?>" data-val="<?= esc($m, 'attr') ?>" role="radio" aria-checked="<?= $m === 'cash' ? 'true' : 'false' ?>">
                                        <i class="bi <?= $icon ?>"></i><?= esc(\App\Models\TransactionModel::MODE_LABELS[$m]) ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="payment_mode" id="rpAddMode" value="cash">
                        </div>

                        <div class="mb-0">
                            <label class="form-label">Party type <span class="text-muted small">(optional)</span></label>
                            <div class="rp-chips" data-rp-typechips>
                                <?php foreach ($partyTypes as $t): ?>
                                    <button type="button" class="rp-chip-btn" data-val="<?= esc($t, 'attr') ?>"><?= esc($t) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <input type="text" name="party_type" id="rpAddPartyType" class="form-control" maxlength="32"
                                   placeholder="Farmer, Firm, Trader…" autocomplete="off">
                            <div class="form-text">Click a chip, or type your own to classify who this account is.</div>
                        </div>
                    </div>
                </div>
                <div class="form-text mt-3">Adding to <strong><?= esc($dmy($period->from)) ?></strong>.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn rp-add-btn" data-more="1" data-outline="1"><i class="bi bi-plus-lg me-1"></i>Save &amp; add another</button>
                <button type="submit" class="btn rp-add-btn"><i class="bi bi-check2-circle me-1"></i>Save</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<style>
    .rp-firm { display:flex; align-items:center; gap:.5rem; margin-bottom:.6rem; font-weight:700; font-size:1.05rem; }
    .rp-firm .bi { color: var(--bs-primary, #0d6efd); }
    .rp-firm-meta { font-weight:500; font-size:.8rem; color: var(--bs-secondary-color, #6c757d); background: var(--bs-secondary-bg, #eef0f3); padding:.1rem .5rem; border-radius:999px; }
    .rp-add-dot { width:.7rem; height:.7rem; border-radius:50%; display:inline-block; background:var(--bs-success,#198754); }
    .rp-entry.rp-entry-new { animation: rpFlash 1.2s ease-out; }
    @keyframes rpFlash { 0% { background: var(--bs-warning-bg-subtle, #fff3cd); } 100% { background: transparent; } }

    /* Add entry modal — breathing room */
    #rpAddModal .modal-content { border-radius: 16px; }
    #rpAddModal .modal-header { padding: 1.15rem 1.4rem; }
    #rpAddModal .modal-body { padding: 1.4rem; }
    #rpAddModal .modal-footer { padding: 1rem 1.4rem; gap: .5rem; }
    #rpAddModal .form-label { margin-bottom: .4rem; font-weight: 600; }
    #rpAddModal .form-control { padding-top: .6rem; padding-bottom: .6rem; }
    #rpAddModal .form-text { margin-top: .45rem; }
    /* A divider between the entry column and the classification column, on wide screens only. */
    @media (min-width: 992px) {
        #rpAddModal .rp-col-classify { border-left: 1px solid var(--bs-border-color, #e9edf5); }
    }

    /* Searchable party (account) dropdown */
    .rp-combo-field { position: relative; }
    .rp-combo-menu {
        position: absolute; left: 0; right: 0; top: calc(100% + .3rem); z-index: 20;
        background: var(--bs-body-bg, #fff); border: 1px solid var(--bs-border-color, rgba(0,0,0,.15));
        border-radius: 12px; box-shadow: 0 12px 30px rgba(15,23,42,.18); padding: .35rem; max-height: 260px; overflow-y: auto;
    }
    .rp-opt { display: flex; align-items: center; gap: .6rem; padding: .55rem .6rem; border-radius: 8px; cursor: pointer; }
    .rp-opt:hover, .rp-opt.active { background: var(--bs-primary-bg-subtle, #e7f1ff); }
    .rp-opt-main { flex: 1 1 auto; min-width: 0; }
    .rp-opt-name { display: block; font-weight: 600; font-size: .92rem; line-height: 1.2; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rp-opt-desc { display: block; margin-top: .1rem; font-size: .76rem; line-height: 1.2; color: var(--bs-secondary-color, #6c757d);
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .rp-opt-code { flex: 0 0 auto; font-size: .7rem; font-weight: 600; letter-spacing: .3px; color: var(--bs-primary-text-emphasis, #0a58ca);
        background: var(--bs-primary-bg-subtle, #e7f1ff); border: 1px solid var(--bs-primary-border-subtle, #b6d4fe); padding: .1rem .4rem; border-radius: 6px; }
    .rp-opt-new { color: var(--bs-secondary-color, #6c757d); font-size: .85rem; padding: .55rem .6rem; }
    .rp-opt + .rp-opt-new { margin-top: .25rem; border-top: 1px solid var(--bs-border-color, rgba(0,0,0,.12)); padding-top: .6rem; }
    .rp-opt-new b { color: var(--bs-body-color); }
    .rp-combo-tools {
        position: sticky; top: 0; z-index: 2; display: grid; grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .35rem; margin-bottom: .4rem; padding-bottom: .4rem; background: var(--bs-body-bg, #fff);
        border-bottom: 1px solid var(--bs-border-color, rgba(0,0,0,.12));
    }
    .rp-combo-filter {
        border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 8px; padding: .35rem .5rem;
        background: var(--bs-body-bg, #fff); color: var(--bs-secondary-color, #6c757d);
        font-size: .76rem; font-weight: 800; cursor: pointer;
    }
    .rp-combo-filter.active { color: var(--bs-primary, #0d6efd); border-color: var(--bs-primary, #0d6efd); background: var(--bs-primary-bg-subtle, #e7f1ff); }
    .rp-combo-filter-jama.active { color: #15803d; border-color: #22c55e; background: rgba(34,197,94,.12); }
    .rp-combo-filter-naam.active { color: #b91c1c; border-color: #ef4444; background: rgba(239,68,68,.12); }
    .rp-opt { display: grid; grid-template-columns: 34px minmax(0, 1fr) auto; border: 1px solid transparent; }
    .rp-opt:hover, .rp-opt.active { border-color: var(--bs-primary-border-subtle, #b6d4fe); }
    .rp-opt-jama:hover, .rp-opt-jama.active { background: rgba(34,197,94,.08); border-color: rgba(34,197,94,.34); }
    .rp-opt-naam:hover, .rp-opt-naam.active { background: rgba(239,68,68,.08); border-color: rgba(239,68,68,.34); }
    .rp-opt-avatar {
        width: 34px; height: 34px; border-radius: 8px; display: inline-flex; align-items: center; justify-content: center;
        color: #fff; font-weight: 900; background: linear-gradient(135deg, #3b82f6, #2563eb); text-transform: uppercase;
    }
    .rp-opt-jama .rp-opt-avatar { background: linear-gradient(135deg, #22c55e, #15803d); }
    .rp-opt-naam .rp-opt-avatar { background: linear-gradient(135deg, #ef4444, #b91c1c); }
    .rp-opt-bal {
        flex: 0 0 auto; padding: .2rem .5rem; border-radius: 999px; font-size: .76rem; font-weight: 900;
        white-space: nowrap; background: rgba(37,99,235,.10); font-variant-numeric: tabular-nums;
    }
    .rp-opt-bal.tx-amt-jama { background: rgba(34,197,94,.12); }
    .rp-opt-bal.tx-amt-naam { background: rgba(239,68,68,.12); }

    /* Payment mode — segmented pills, one always selected */
    .rp-modes { display: flex; flex-wrap: wrap; gap: .4rem; }
    .rp-mode {
        display: inline-flex; align-items: center; gap: .35rem; padding: .45rem .8rem; font-size: .85rem; font-weight: 600; cursor: pointer;
        border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 999px; background: var(--bs-body-bg, #fff); color: var(--bs-body-color);
    }
    .rp-mode:hover { border-color: var(--bs-primary, #0d6efd); }
    .rp-mode.active { color: #fff; background: var(--bs-primary, #0d6efd); border-color: var(--bs-primary, #0d6efd); }

    /* Party type — one-click suggestions above a free-text box */
    .rp-chips { display: flex; flex-wrap: wrap; gap: .35rem; margin-bottom: .5rem; }
    .rp-chip-btn {
        padding: .3rem .7rem; font-size: .78rem; font-weight: 600; cursor: pointer; border-radius: 999px;
        border: 1px dashed var(--bs-border-color, #dee2e6); background: transparent; color: var(--bs-secondary-color, #6c757d);
    }
    .rp-chip-btn:hover { color: var(--bs-primary, #0d6efd); border-color: var(--bs-primary, #0d6efd); }
    .rp-chip-btn.active { color: #fff; background: var(--bs-primary, #0d6efd); border-style: solid; border-color: var(--bs-primary, #0d6efd); }
</style>
<script>
(function () {
    var TOKEN_NAME = '<?= csrf_token() ?>';
    var URL = '<?= site_url('transactions/quick-store') ?>';
    var PARTIES = <?= json_encode(array_values($partyOptions), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    // Opening-balance tip dismissal (remembered per browser).
    var tip = document.querySelector('[data-openbal-tip]');
    if (tip) {
        try { if (localStorage.getItem('rpOpenBalTipDismissed') === '1') { tip.remove(); } } catch (e) {}
        var x = tip.querySelector('[data-openbal-dismiss]');
        if (x) { x.addEventListener('click', function () { try { localStorage.setItem('rpOpenBalTipDismissed', '1'); } catch (e) {} tip.remove(); }); }
    }

    var MODE_ICONS = { cash: 'bi-cash-coin', upi: 'bi-phone', bank: 'bi-bank', cheque: 'bi-journal-text', card: 'bi-credit-card', other: 'bi-three-dots' };

    function el(html) { var t = document.createElement('template'); t.innerHTML = html.trim(); return t.content.firstChild; }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    /** Mode / party-type chips — mirrors the $rowChips() helper used for server-rendered rows. */
    function chipsHtml(e) {
        var mode = e.mode || 'cash';
        var html = '<span class="rp-chip rp-mode-' + esc(mode) + '"><i class="bi ' + (MODE_ICONS[mode] || 'bi-three-dots') + '"></i>' + esc(e.modeLabel || mode) + '</span>';
        if (e.partyType) { html += '<span class="rp-chip rp-chip-party"><i class="bi bi-person"></i>' + esc(e.partyType) + '</span>'; }
        return html;
    }

    // A stable hue from the name so a just-added row gets the same avatar colour on reload.
    function hueOf(s) {
        s = String(s || ''); var h = 0;
        for (var i = 0; i < s.length; i++) { h = (h * 31 + s.charCodeAt(i)) % 360; }
        return h;
    }

    function tipHtml(e) {
        var jama = e.type === 'jama';
        var chips = '<span class="t-chip">' + esc(e.modeLabel || e.mode || 'Cash') + '</span>';
        if (e.partyType) { chips += '<span class="t-chip">' + esc(e.partyType) + '</span>'; }
        chips += '<span class="t-chip">Web</span>';
        return '<div class="t-top"><b>' + esc(e.name) + '</b><span class="t-no">#' + esc(e.txn_no) + '</span></div>'
            + '<div class="t-amt ' + (jama ? 't-jama' : 't-naam') + '">' + (jama ? '+' : '−') + ' ₹ ' + esc(e.amount) + ' <span class="t-dir">' + (jama ? 'Jama · In' : 'Naam · Out') + '</span></div>'
            + '<div class="t-hr"></div><div class="t-chips">' + chips + '</div>'
            + '<div class="t-row"><i class="bi bi-person-circle"></i> ' + esc(e.addedBy || 'You') + ' · just now</div>'
            + '<div class="t-row"><span class="t-fresh"><i class="bi bi-stars"></i> Fresh entry</span></div>'
            + '<div class="t-foot">Click the row to open full details</div>';
    }

    function entryHtml(e, perms) {
        var acts = '<button type="button" class="rp-act rp-view" title="View details" data-tx-view data-id="' + e.hid + '"><i class="bi bi-eye"></i></button>';
        if (perms.edit)   { acts += '<a class="rp-act rp-edit" href="' + e.editUrl + '" title="Edit"><i class="bi bi-pencil"></i></a>'; }
        if (perms.delete) { acts += '<button type="button" class="rp-act rp-del" title="Delete" data-tx-delete data-action="' + e.delUrl + '" data-label="' + esc(e.txn_no) + '"><i class="bi bi-trash"></i></button>'; }
        var meta = '<span class="rp-badge rp-badge-web"><i class="bi bi-display"></i> Web</span>' + chipsHtml(e);
        if (e.notes) { meta += ' <span>' + esc(e.notes) + '</span>'; }
        // A just-added entry is always fresh, by the current user, right now.
        var info = '<i class="bi bi-person-circle"></i> <b>' + esc(e.addedBy || 'You') + '</b> · just now · '
            + '<span class="rp-info-fresh"><i class="bi bi-stars"></i> Fresh</span>';
        return '<div class="rp-entry rp-entry-new rp-entry-open-view" data-tx-view data-id="' + e.hid + '">'
            + '<div class="rp-amt">' + esc(e.amount) + '</div>'
            + '<div class="rp-mid"><div class="rp-party">'
            + '<span class="rp-avatar" style="--rp-hue: ' + (e.hue != null ? e.hue : hueOf(e.name)) + '">' + esc((e.name || '?').trim().charAt(0).toUpperCase() || '?') + '</span>'
            + '<span class="rp-party-name" title="' + esc(e.name) + '">' + esc(e.name) + '</span>'
            + '<span class="rp-id">ID-' + e.hid + '</span></div>'
            + '<div class="rp-meta">' + meta + '</div>'
            + '<div class="rp-info">' + info + '</div></div>'
            + '<div class="rp-acts">' + acts + '</div></div>';
    }

    function flash(msg, ok) {
        // Prefer the app-wide notifier (Toastr / SweetAlert) for a consistent look.
        if (window.erpNotify) { window.erpNotify(ok ? 'success' : 'error', msg); return; }
        var n = el('<div class="alert alert-' + (ok ? 'success' : 'danger') + ' rp-toast" style="position:fixed;top:1rem;right:1rem;z-index:1090;box-shadow:0 8px 24px rgba(0,0,0,.15)">' + esc(msg) + '</div>');
        document.body.appendChild(n);
        setTimeout(function () { n.remove(); }, 2600);
    }

    function syncTokens(csrf) {
        if (!csrf || !csrf.hash) { return; }
        document.querySelectorAll('input[name="' + TOKEN_NAME + '"]').forEach(function (i) { i.value = csrf.hash; });
    }

    function applyTotals(t, side, entry, perms) {
        var col = document.querySelector('.rp-col-' + side);
        var node = el(entryHtml(entry, perms || { edit:false, delete:false }));
        node.setAttribute('data-rp-tip', tipHtml(entry)); // hover tooltip, same as server rows
        col.insertBefore(node, col.querySelector('.rp-total'));
        if (side === 'naam') { var em = document.querySelector('[data-naam-empty]'); if (em) { em.setAttribute('hidden', 'hidden'); } }
        var jt = document.querySelector('[data-jama-total]'); if (jt) { jt.textContent = t.jamaColTotal; }
        var nt = document.querySelector('[data-naam-total]'); if (nt) { nt.textContent = t.totalNaam; }
        var cv = document.querySelector('[data-closing-val]'); if (cv) { cv.textContent = t.closing; }
        var bal = document.querySelector('[data-rp-balance]'); if (bal) { bal.classList.toggle('is-negative', !!t.closingNeg); }
    }

    // ---- Add Deposit / Add Expense popup ----
    var form = document.getElementById('rpAddForm');
    if (!form) { return; }
    var modalEl = document.getElementById('rpAddModal');
    var nameEl  = document.getElementById('rpAddName');
    var amtEl   = document.getElementById('rpAddAmount');
    var notesEl = document.getElementById('rpAddNotes');
    var typeEl  = document.getElementById('rpAddType');
    var titleEl = document.getElementById('rpAddTitle');
    var dotEl   = document.getElementById('rpAddDot');
    var modeEl  = document.getElementById('rpAddMode');
    var ptEl    = document.getElementById('rpAddPartyType');
    var DATE    = form.querySelector('input[name="txn_date"]').value;

    function modal() { return bootstrap.Modal.getOrCreateInstance(modalEl); }
    function tokenField() { return form.querySelector('input[name="' + TOKEN_NAME + '"]'); }

    /** Clear the party/amount/notes trio — everything you retype for the next entry. */
    function clearEntryFields() {
        nameEl.value = ''; amtEl.value = ''; notesEl.value = '';
        closeMenu();
    }

    /** Full reset, used when the popup is opened fresh. */
    function resetForm() {
        clearEntryFields();
        partyFilter = 'all';
        setMode('cash');
        ptEl.value = ''; syncPartyChips();
    }

    document.querySelectorAll('[data-rp-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var side = btn.getAttribute('data-rp-add');
            var isJama = side === 'jama';
            typeEl.value = side;
            titleEl.textContent = isJama ? 'Add Deposit (Jama)' : 'Add Expense (Naam)';
            dotEl.style.background = isJama ? 'var(--bs-success,#198754)' : 'var(--bs-danger,#dc3545)';
            var solid = isJama ? 'btn-success' : 'btn-danger';
            var outline = isJama ? 'btn-outline-success' : 'btn-outline-danger';
            form.querySelectorAll('.rp-add-btn').forEach(function (b) {
                b.classList.remove('btn-success', 'btn-danger', 'btn-outline-success', 'btn-outline-danger');
                b.classList.add(b.hasAttribute('data-outline') ? outline : solid);
            });
            resetForm();
            modal().show();
            setTimeout(function () { nameEl.focus(); }, 250);
        });
    });

    // ---- Payment mode (Cash / UPI / Bank / …) — exactly one is always selected ----
    var modeBtns = form.querySelectorAll('[data-rp-modes] .rp-mode');

    function setMode(v) {
        modeEl.value = v;
        modeBtns.forEach(function (b) {
            var on = b.getAttribute('data-val') === v;
            b.classList.toggle('active', on);
            b.setAttribute('aria-checked', on ? 'true' : 'false');
        });
    }
    modeBtns.forEach(function (b) {
        b.addEventListener('click', function () { setMode(b.getAttribute('data-val')); });
    });

    // ---- Party type — chips are shortcuts into a free-text box, so custom values still work ----
    var ptBtns = form.querySelectorAll('[data-rp-typechips] .rp-chip-btn');

    function syncPartyChips() {
        var v = ptEl.value.trim().toLowerCase();
        ptBtns.forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-val').toLowerCase() === v); });
    }
    ptBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            var v = b.getAttribute('data-val');
            // Clicking the selected chip again clears it.
            ptEl.value = ptEl.value.trim().toLowerCase() === v.toLowerCase() ? '' : v;
            syncPartyChips();
        });
    });
    ptEl.addEventListener('input', syncPartyChips);

    // ---- Searchable party (account) dropdown ----
    var menu = document.getElementById('rpPartyMenu');
    var active = -1, shown = [], partyFilter = 'all';

    function closeMenu() { menu.hidden = true; nameEl.setAttribute('aria-expanded', 'false'); active = -1; }
    function partyInitial(name) { return (name || '?').trim().charAt(0).toUpperCase() || '?'; }
    function partyMoney(v) {
        var n = parseFloat(v || 0);
        if (!isFinite(n)) { n = 0; }
        return (n < 0 ? '-' : '') + String.fromCharCode(8377) + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function partyFilterLabel() {
        if (partyFilter === 'jama') { return 'Jama balance'; }
        if (partyFilter === 'naam') { return 'Naam balance'; }
        return 'All accounts';
    }

    function renderMenu(q) {
        q = q.trim().toLowerCase();
        shown = PARTIES.filter(function (p) {
            var match = !q || (p.name + ' ' + p.code).toLowerCase().indexOf(q) !== -1;
            var tone = p.tone || 'neutral';
            return match && (partyFilter === 'all' || tone === partyFilter);
        }).slice(0, 12);
        active = -1;
        var toolbar = '<div class="rp-combo-tools" role="group" aria-label="Account filters">'
            + '<button type="button" class="rp-combo-filter' + (partyFilter === 'all' ? ' active' : '') + '" data-filter="all">All</button>'
            + '<button type="button" class="rp-combo-filter rp-combo-filter-jama' + (partyFilter === 'jama' ? ' active' : '') + '" data-filter="jama">Jama</button>'
            + '<button type="button" class="rp-combo-filter rp-combo-filter-naam' + (partyFilter === 'naam' ? ' active' : '') + '" data-filter="naam">Naam</button>'
            + '</div>';
        var html = shown.map(function (p, i) {
            var tone = p.tone || 'neutral';
            return '<div class="rp-opt rp-opt-' + tone + '" role="option" data-i="' + i + '">'
                + '<span class="rp-opt-avatar"></span>'
                + '<span class="rp-opt-main"><span class="rp-opt-name"></span><span class="rp-opt-desc"></span></span>'
                + '<span class="rp-opt-bal"></span></div>';
        }).join('');
        if (q && !shown.some(function (p) { return p.name.toLowerCase() === q; })) {
            html += '<div class="rp-opt-new">“<b class="rp-new-q"></b>” — will be added as a <b>new account</b>.</div>';
        }
        if (!shown.length && !q) { html = '<div class="rp-opt-new">No saved accounts yet — just type a name.</div>'; }
        menu.innerHTML = toolbar + html;
        shown.forEach(function (p, i) {
            var opt = menu.querySelector('.rp-opt[data-i="' + i + '"]');
            var tone = p.tone || 'neutral';
            opt.querySelector('.rp-opt-avatar').textContent = partyInitial(p.name);
            opt.querySelector('.rp-opt-name').textContent = p.name;
            opt.querySelector('.rp-opt-desc').textContent = p.desc;
            opt.querySelector('.rp-opt-bal').textContent = partyMoney(p.net);
            opt.querySelector('.rp-opt-bal').classList.add(tone === 'naam' ? 'tx-amt-naam' : (tone === 'jama' ? 'tx-amt-jama' : 'tx-bal'));
        });
        var nq = menu.querySelector('.rp-new-q'); if (nq) { nq.textContent = nameEl.value.trim(); }
        menu.hidden = false; nameEl.setAttribute('aria-expanded', 'true');
    }

    function pick(i) { if (shown[i]) { nameEl.value = shown[i].name; closeMenu(); amtEl.focus(); } }
    function highlight() { menu.querySelectorAll('.rp-opt').forEach(function (o, i) { o.classList.toggle('active', i === active); }); }

    nameEl.addEventListener('input', function () { renderMenu(nameEl.value); });
    nameEl.addEventListener('focus', function () { renderMenu(nameEl.value); });
    menu.addEventListener('mousedown', function (e) {
        var filterBtn = e.target.closest('[data-filter]');
        if (filterBtn) {
            e.preventDefault();
            partyFilter = filterBtn.getAttribute('data-filter') || 'all';
            renderMenu(nameEl.value);
            return;
        }
        var o = e.target.closest('.rp-opt'); if (o) { e.preventDefault(); pick(+o.getAttribute('data-i')); }
    });
    nameEl.addEventListener('keydown', function (e) {
        if (menu.hidden) { return; }
        if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, shown.length - 1); highlight(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); highlight(); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); pick(active); }
        else if (e.key === 'Escape') { closeMenu(); }
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('#rpPartyCombo')) { closeMenu(); } });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        e.stopPropagation();
        var keepOpen = !!(e.submitter && e.submitter.hasAttribute('data-more'));
        var name = nameEl.value.trim();
        var amt  = parseFloat(amtEl.value);
        var side = typeEl.value;
        if (!name || !(amt > 0)) { flash('Enter a party/description and an amount greater than 0.', false); return; }

        var buttons = form.querySelectorAll('button[type="submit"]');
        buttons.forEach(function (b) { b.disabled = true; });

        var fd = new FormData();
        fd.append(TOKEN_NAME, tokenField() ? tokenField().value : '');
        fd.append('type', side);
        fd.append('txn_date', DATE);
        fd.append('name', name);
        fd.append('amount', amt);
        fd.append('payment_mode', modeEl.value);
        if (ptEl.value.trim()) { fd.append('party_type', ptEl.value.trim()); }
        if (notesEl.value.trim()) { fd.append('notes', notesEl.value.trim()); }

        fetch(URL, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                buttons.forEach(function (b) { b.disabled = false; });
                syncTokens(res.csrf);
                if (!res.ok) { flash(res.message || 'Could not add the entry.', false); return; }
                applyTotals(res.totals, side, res.entry, res.perms);
                flash(res.message, true);
                if (PARTIES.every(function (p) { return p.name.toLowerCase() !== res.entry.name.toLowerCase(); })) {
                    PARTIES.unshift({ name: res.entry.name, code: 'NEW', desc: 'Added just now' });
                }
                // "Save & add another" keeps mode / party type — the next entry in a run is
                // usually the same kind of thing, and the chips stay visible on screen.
                clearEntryFields();
                if (keepOpen) { nameEl.focus(); } else { modal().hide(); }
            })
            .catch(function () { buttons.forEach(function (b) { b.disabled = false; }); flash('Network error — please try again.', false); });
    });
})();

// ---- Hover tooltip engine: shows the styled detail card for any [data-rp-tip] ----
(function () {
    var tip = null, current = null;
    function ensure() {
        if (!tip) { tip = document.createElement('div'); tip.className = 'rp-tip'; document.body.appendChild(tip); }
        return tip;
    }
    function place(el) {
        var html = el.getAttribute('data-rp-tip');
        if (!html) { return; }
        var t = ensure();
        t.innerHTML = html;
        t.classList.add('show');
        var r = el.getBoundingClientRect();
        var tw = t.offsetWidth, th = t.offsetHeight, m = 8;
        var left = Math.min(Math.max(m, r.left), window.innerWidth - tw - m);
        var top = r.bottom + m;
        if (top + th > window.innerHeight - m) { top = r.top - th - m; } // flip above when no room below
        t.style.left = left + 'px';
        t.style.top = Math.max(m, top) + 'px';
    }
    function hide() { current = null; if (tip) { tip.classList.remove('show'); } }
    document.addEventListener('mouseover', function (e) {
        var el = e.target.closest('[data-rp-tip]');
        if (el && el !== current) { current = el; place(el); }
    });
    document.addEventListener('mouseout', function (e) {
        var el = e.target.closest('[data-rp-tip]');
        if (el && !el.contains(e.relatedTarget)) { hide(); }
    });
    window.addEventListener('scroll', hide, true);
    window.addEventListener('resize', hide);
})();
</script>
