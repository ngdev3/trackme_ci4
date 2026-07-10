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
    $sign = $net < 0 ? '-' : '';
    $desc = 'Bal ' . $sign . '₹' . $fmt(abs($net))
        . ' · ' . (int) $p['count'] . ' ' . ((int) $p['count'] === 1 ? 'entry' : 'entries')
        . (! empty($p['last_date']) ? ' · last ' . date('d M y', strtotime($p['last_date'])) : '');
    return ['name' => (string) $p['name'], 'code' => $partyCode((string) $p['name']), 'desc' => $desc];
}, $parties ?? []);
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
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('transactions/list') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-ul"></i> Ledger</a>
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
                <div class="rp-col rp-col-jama" data-side="jama">
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
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="rpAddForm" data-no-validate autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="type" id="rpAddType" value="jama">
            <input type="hidden" name="txn_date" value="<?= esc($period->from, 'attr') ?>">
            <div class="modal-header">
                <h5 class="modal-title d-flex align-items-center gap-2"><span class="rp-add-dot" id="rpAddDot"></span><span id="rpAddTitle">Add Deposit (Jama)</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
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
                <div class="mb-1">
                    <label class="form-label">Notes <span class="text-muted small">(optional)</span></label>
                    <input type="text" name="notes" id="rpAddNotes" class="form-control" placeholder="Any remark">
                </div>
                <div class="form-text">Adding to <strong><?= esc($dmy($period->from)) ?></strong>.</div>
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
    #rpAddModal .modal-body > .mb-3 { margin-bottom: 1.35rem !important; }

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

    function el(html) { var t = document.createElement('template'); t.innerHTML = html.trim(); return t.content.firstChild; }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

    function entryHtml(e, perms) {
        var acts = '<button type="button" class="rp-act rp-view" title="View" data-tx-view data-id="' + e.hid + '"><i class="bi bi-eye"></i></button>';
        if (perms.edit)   { acts += '<a class="rp-act rp-edit" href="' + e.editUrl + '" title="Edit"><i class="bi bi-pencil"></i></a>'; }
        if (perms.delete) { acts += '<button type="button" class="rp-act rp-del" title="Delete" data-tx-delete data-action="' + e.delUrl + '" data-label="' + esc(e.txn_no) + '"><i class="bi bi-trash"></i></button>'; }
        var meta = '<span class="rp-badge rp-badge-web"><i class="bi bi-display"></i> Web</span>';
        if (e.notes) { meta += ' <span>' + esc(e.notes) + '</span>'; }
        return '<div class="rp-entry rp-entry-new">'
            + '<div class="rp-amt">' + esc(e.amount) + '</div>'
            + '<div class="rp-mid"><div class="rp-party">' + esc(e.name) + ' <span class="rp-id">(ID-' + e.hid + ')</span></div>'
            + '<div class="rp-meta">' + meta + '</div></div>'
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
        col.insertBefore(el(entryHtml(entry, perms || { edit:false, delete:false })), col.querySelector('.rp-total'));
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
    var DATE    = form.querySelector('input[name="txn_date"]').value;

    function modal() { return bootstrap.Modal.getOrCreateInstance(modalEl); }
    function tokenField() { return form.querySelector('input[name="' + TOKEN_NAME + '"]'); }

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
            nameEl.value = ''; amtEl.value = ''; notesEl.value = ''; closeMenu();
            modal().show();
            setTimeout(function () { nameEl.focus(); }, 250);
        });
    });

    // ---- Searchable party (account) dropdown ----
    var menu = document.getElementById('rpPartyMenu');
    var active = -1, shown = [];

    function closeMenu() { menu.hidden = true; nameEl.setAttribute('aria-expanded', 'false'); active = -1; }

    function renderMenu(q) {
        q = q.trim().toLowerCase();
        shown = PARTIES.filter(function (p) {
            return !q || (p.name + ' ' + p.code).toLowerCase().indexOf(q) !== -1;
        }).slice(0, 12);
        active = -1;
        var html = shown.map(function (p, i) {
            return '<div class="rp-opt" role="option" data-i="' + i + '">'
                + '<span class="rp-opt-main"><span class="rp-opt-name"></span><span class="rp-opt-desc"></span></span>'
                + '<span class="rp-opt-code"></span></div>';
        }).join('');
        if (q && !shown.some(function (p) { return p.name.toLowerCase() === q; })) {
            html += '<div class="rp-opt-new">“<b class="rp-new-q"></b>” — will be added as a <b>new account</b>.</div>';
        }
        if (!shown.length && !q) { html = '<div class="rp-opt-new">No saved accounts yet — just type a name.</div>'; }
        menu.innerHTML = html;
        shown.forEach(function (p, i) {
            var opt = menu.querySelector('.rp-opt[data-i="' + i + '"]');
            opt.querySelector('.rp-opt-name').textContent = p.name;
            opt.querySelector('.rp-opt-desc').textContent = p.desc;
            opt.querySelector('.rp-opt-code').textContent = p.code;
        });
        var nq = menu.querySelector('.rp-new-q'); if (nq) { nq.textContent = nameEl.value.trim(); }
        menu.hidden = false; nameEl.setAttribute('aria-expanded', 'true');
    }

    function pick(i) { if (shown[i]) { nameEl.value = shown[i].name; closeMenu(); amtEl.focus(); } }
    function highlight() { menu.querySelectorAll('.rp-opt').forEach(function (o, i) { o.classList.toggle('active', i === active); }); }

    nameEl.addEventListener('input', function () { renderMenu(nameEl.value); });
    nameEl.addEventListener('focus', function () { renderMenu(nameEl.value); });
    menu.addEventListener('mousedown', function (e) { var o = e.target.closest('.rp-opt'); if (o) { e.preventDefault(); pick(+o.getAttribute('data-i')); } });
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
                nameEl.value = ''; amtEl.value = ''; notesEl.value = ''; closeMenu();
                if (keepOpen) { nameEl.focus(); } else { modal().hide(); }
            })
            .catch(function () { buttons.forEach(function (b) { b.disabled = false; }); flash('Network error — please try again.', false); });
    });
})();
</script>
