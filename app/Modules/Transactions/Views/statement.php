<?php
/**
 * Account (party) statement — search or browse an account and see its full
 * running-balance ledger with opening/closing. Rendered inside layout.php.
 */
use App\Models\TransactionModel;

$typeBadge = static fn (string $t): string => $t === 'naam'
    ? '<span class="tx-type tx-naam"><i class="bi bi-arrow-down-left"></i>Naam</span>'
    : '<span class="tx-type tx-jama"><i class="bi bi-arrow-up-right"></i>Jama</span>';

$stmtQs = array_filter(['party' => $party, 'from' => $from, 'to' => $to]);
?>

<!-- ===== Search / pick an account ===== -->
<div class="card tm-table-card tx-search-card mb-3">
    <div class="tm-table-head">
        <h3 class="tm-table-title"><i class="bi bi-person-vcard"></i> Account Statement</h3>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('transactions/list') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-table"></i> Rokad Vahi</a>
            <?php if (! empty($hasParty)): ?>
                <?php if (sub_is_pro()): ?>
                    <a href="<?= site_url('transactions/statement/print') . '?' . http_build_query($stmtQs) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</a>
                    <a href="<?= site_url('transactions/statement/pdf') . '?' . http_build_query($stmtQs + ['per' => 500]) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                <?php else: ?>
                    <a href="<?= site_url('subscription') ?>" class="btn btn-sm btn-outline-primary" title="Available on the paid plan"><i class="bi bi-lock"></i> Download statement</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form method="get" action="<?= site_url('transactions/statement') ?>" class="row g-2 align-items-end">
            <div class="col-12 col-md-5">
                <label class="tx-flabel">Account (party)</label>
                <div class="tx-combo" data-tx-combo>
                    <div class="tx-search">
                        <i class="bi bi-search"></i>
                        <input type="text" name="party" value="<?= esc($party, 'attr') ?>"
                               class="form-control form-control-sm tx-combo-input"
                               placeholder="Search account by name..." autocomplete="off" required
                               role="combobox" aria-autocomplete="list" aria-expanded="false">
                        <i class="bi bi-chevron-down tx-combo-caret" data-tx-combo-toggle></i>
                    </div>
                    <div class="tx-combo-menu" role="listbox" hidden>
                        <div class="tx-combo-tools" role="group" aria-label="Account filters">
                            <button type="button" class="tx-combo-filter active" data-filter="all">All</button>
                            <button type="button" class="tx-combo-filter tx-combo-filter-jama" data-filter="jama">Jama</button>
                            <button type="button" class="tx-combo-filter tx-combo-filter-naam" data-filter="naam">Naam</button>
                        </div>
                        <?php if (empty($parties)): ?>
                            <div class="tx-combo-empty">No accounts yet.</div>
                        <?php else: foreach ($parties as $p):
                            $net  = (float) $p['net'];
                            $meta = number_format($p['count']) . ' txn' . ((int) $p['count'] === 1 ? '' : 's')
                                  . ' · ' . ($p['last_date'] ? esc(fmt_date($p['last_date'])) : 'no activity');
                        ?>
                            <?php $tone = $net > 0 ? 'jama' : ($net < 0 ? 'naam' : 'neutral'); ?>
                            <button type="button" class="tx-combo-item tx-combo-item-<?= esc($tone, 'attr') ?>" role="option"
                                     data-name="<?= esc($p['name'], 'attr') ?>"
                                     data-tone="<?= esc($tone, 'attr') ?>"
                                     data-search="<?= esc(mb_strtolower($p['name']), 'attr') ?>">
                                <span class="tx-combo-avatar"><?= esc(mb_strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
                                <span class="tx-combo-text">
                                    <span class="tx-combo-name"><?= esc($p['name']) ?></span>
                                    <span class="tx-combo-meta"><?= $meta ?></span>
                                </span>
                                <span class="tx-combo-net <?= $net < 0 ? 'tx-amt-naam' : 'tx-amt-jama' ?>"><?= money($net) ?></span>
                            </button>
                        <?php endforeach; endif; ?>
                        <div class="tx-combo-empty tx-combo-noresult" hidden>No matching account.</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <label class="tx-flabel">From <span class="text-secondary">(optional)</span></label>
                <input type="date" name="from" value="<?= esc($from, 'attr') ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label class="tx-flabel">To <span class="text-secondary">(optional)</span></label>
                <input type="date" name="to" value="<?= esc($to, 'attr') ?>" class="form-control form-control-sm">
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="bi bi-file-earmark-text"></i> Statement</button>
                <?php if (! empty($hasParty)): ?>
                    <a href="<?= site_url('transactions/statement') ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (empty($hasParty)): ?>
    <!-- ===== Browse accounts (no party chosen yet) ===== -->
    <div class="card tm-table-card">
        <div class="tm-table-head">
            <h3 class="tm-table-title"><i class="bi bi-people"></i> All Accounts</h3>
            <span class="tx-mode"><i class="bi bi-info-circle"></i> Click an account to open its statement</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 tm-table">
                    <thead><tr>
                        <th>Account</th><th class="text-end">Txns</th>
                        <th class="text-end">Total Jama</th><th class="text-end">Total Naam</th>
                        <th class="text-end">Net</th><th>Last Activity</th><th></th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($parties)): ?>
                        <tr><td colspan="7" class="text-center text-secondary py-5"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>No accounts yet.</td></tr>
                    <?php else: foreach ($parties as $p): $url = site_url('transactions/statement') . '?party=' . urlencode($p['name']); ?>
                        <tr class="tx-row-link" data-href="<?= esc($url, 'attr') ?>" title="Open <?= esc($p['name'], 'attr') ?>'s statement">
                            <td class="fw-semibold"><a href="<?= $url ?>" class="text-decoration-none"><i class="bi bi-person-circle me-1 text-secondary"></i><?= esc($p['name']) ?></a></td>
                            <td class="text-end"><?= number_format($p['count']) ?></td>
                            <td class="text-end tx-amt-jama"><?= money($p['jama']) ?></td>
                            <td class="text-end tx-amt-naam"><?= money($p['naam']) ?></td>
                            <td class="text-end fw-semibold <?= $p['net'] < 0 ? 'tx-amt-naam' : 'tx-amt-jama' ?>"><?= money($p['net']) ?></td>
                            <td class="text-secondary small"><?= $p['last_date'] ? esc(fmt_date($p['last_date'])) : '—' ?></td>
                            <td class="text-end"><a href="<?= $url ?>" class="tx-act tx-view" title="Open statement"><i class="bi bi-chevron-right"></i></a></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <!-- ===== Statement summary ===== -->
    <?php
        $net       = (float) $totalJama - (float) $totalNaam;
        $rangeText = ($from ?: '—') . ' → ' . ($to ?: 'latest');
        $rangeText = $from || $to ? (($from ? esc(fmt_date($from)) : 'start') . ' → ' . ($to ? esc(fmt_date($to)) : 'latest')) : 'All time';
    ?>
    <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3"><div class="tx-summary tx-sum-opening"><div class="tx-sum-ic"><i class="bi bi-box-arrow-in-down"></i></div><div><div class="tx-sum-lbl">Opening</div><div class="tx-sum-val tx-bal"><?= money($opening) ?></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="tx-summary tx-sum-jama"><div class="tx-sum-ic"><i class="bi bi-arrow-up-right"></i></div><div><div class="tx-sum-lbl">Total Jama</div><div class="tx-sum-val tx-amt-jama"><?= money($totalJama) ?></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="tx-summary tx-sum-naam"><div class="tx-sum-ic"><i class="bi bi-arrow-down-left"></i></div><div><div class="tx-sum-lbl">Total Naam</div><div class="tx-sum-val tx-amt-naam"><?= money($totalNaam) ?></div></div></div></div>
        <div class="col-6 col-lg-3"><div class="tx-summary tx-sum-closing"><div class="tx-sum-ic"><i class="bi bi-safe2"></i></div><div><div class="tx-sum-lbl">Closing Balance</div><div class="tx-sum-val tx-closing"><?= money($closing) ?></div></div></div></div>
    </div>

    <!-- ===== Statement table ===== -->
    <div class="card tm-table-card">
        <div class="tm-table-head">
            <h3 class="tm-table-title"><i class="bi bi-person-vcard"></i> <?= esc($party) ?>
                <span class="rp-sub"><?= $rangeText ?> &middot; <?= number_format($count) ?> entr<?= $count === 1 ? 'y' : 'ies' ?></span>
            </h3>
            <span class="tx-mode <?= $closing < 0 ? 'text-danger' : 'text-success' ?>">
                <i class="bi bi-wallet2"></i> Net balance: <?= money($closing) ?>
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 tm-table">
                    <thead><tr>
                        <th>#</th><th>Date</th><th>Txn No</th><th>Type</th><th>Mode</th><th>Notes</th>
                        <th class="text-end">Jama (In)</th><th class="text-end">Naam (Out)</th><th class="text-end">Balance</th>
                    </tr></thead>
                    <tbody>
                        <tr class="table-light fw-semibold">
                            <td></td><td colspan="5">Opening Balance</td>
                            <td class="text-end">—</td><td class="text-end">—</td>
                            <td class="text-end tx-bal"><?= money($opening) ?></td>
                        </tr>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="9" class="text-center text-secondary py-4">No transactions for this account in the selected range.</td></tr>
                        <?php else: $i = 1; foreach ($rows as $r): ?>
                            <tr class="tx-row-view" data-tx-view data-id="<?= hid($r['id']) ?>" title="Click to view full details">
                                <td><?= $i++ ?></td>
                                <td class="text-nowrap"><?= esc(fmt_date($r['txn_date'])) ?></td>
                                <td><span class="tx-no"><?= esc($r['txn_no'] ?: '—') ?></span></td>
                                <td><?= $typeBadge($r['type']) ?></td>
                                <td class="text-capitalize small text-secondary"><?= esc(TransactionModel::MODE_LABELS[$r['payment_mode']] ?? $r['payment_mode']) ?></td>
                                <td><?php if (! empty($r['notes'])): ?><small class="text-muted"><?= esc(character_limiter($r['notes'], 50)) ?></small><?php else: ?><span class="text-secondary">—</span><?php endif; ?></td>
                                <td class="text-end tx-amt-jama"><?= $r['type'] === 'jama' ? money($r['amount']) : '' ?></td>
                                <td class="text-end tx-amt-naam"><?= $r['type'] === 'naam' ? money($r['amount']) : '' ?></td>
                                <td class="text-end fw-semibold tx-bal"><?= money($r['balance']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light fw-bold">
                            <td></td><td colspan="5">Closing Balance</td>
                            <td class="text-end tx-amt-jama"><?= money($totalJama) ?></td>
                            <td class="text-end tx-amt-naam"><?= money($totalNaam) ?></td>
                            <td class="text-end tx-closing"><?= money($closing) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?= view('Modules\Transactions\Views\_modals') ?>

<style>
    .tx-row-view { cursor: pointer; }
    .tx-row-link { cursor: pointer; }
    .tx-row-link:hover { background: var(--bs-primary-bg-subtle, rgba(13,110,253,.08)); }
</style>
<script>
(function () {
    // Whole account row opens its statement; the name link and chevron still work on their own.
    document.querySelectorAll('.tx-row-link').forEach(function (row) {
        row.addEventListener('click', function (e) {
            if (e.target.closest('a, button')) { return; }
            var href = row.getAttribute('data-href');
            if (href) { window.location.href = href; }
        });
    });
})();
(function () {
    var combo = document.querySelector('[data-tx-combo]');
    if (!combo) { return; }
    var input   = combo.querySelector('.tx-combo-input');
    var menu    = combo.querySelector('.tx-combo-menu');
    var caret   = combo.querySelector('[data-tx-combo-toggle]');
    var items   = Array.prototype.slice.call(combo.querySelectorAll('.tx-combo-item'));
    var noResult = combo.querySelector('.tx-combo-noresult');
    var filters = Array.prototype.slice.call(combo.querySelectorAll('[data-filter]'));
    var active  = -1;
    var toneFilter = 'all';

    function open()  { if (menu.hidden) { menu.hidden = false; input.setAttribute('aria-expanded', 'true'); filter(); } }
    function close() { menu.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; paintActive(); }

    function visibleItems() { return items.filter(function (it) { return !it.hidden; }); }
    function filterLabel() {
        if (toneFilter === 'jama') { return 'Jama balance'; }
        if (toneFilter === 'naam') { return 'Naam balance'; }
        return 'account';
    }

    function filter() {
        var q = input.value.trim().toLowerCase();
        var shown = 0;
        items.forEach(function (it) {
            var textMatch = q === '' || it.getAttribute('data-search').indexOf(q) !== -1;
            var toneMatch = toneFilter === 'all' || it.getAttribute('data-tone') === toneFilter;
            var match = textMatch && toneMatch;
            it.hidden = !match;
            if (match) { shown++; }
        });
        if (noResult) {
            noResult.textContent = q ? 'No matching ' + filterLabel() + '.' : 'No ' + filterLabel() + ' found.';
            noResult.hidden = items.length === 0 || shown !== 0;
        }
        active = -1; paintActive();
    }

    function paintActive() {
        var vis = visibleItems();
        items.forEach(function (it) { it.classList.remove('is-active'); });
        if (active >= 0 && active < vis.length) {
            vis[active].classList.add('is-active');
            vis[active].scrollIntoView({ block: 'nearest' });
        }
    }

    function choose(it) {
        input.value = it.getAttribute('data-name');
        close();
        // Load the statement straight away (guard-validated because the value is set).
        var form = input.form;
        if (form) { form.requestSubmit ? form.requestSubmit() : form.submit(); }
    }

    input.addEventListener('focus', open);
    input.addEventListener('input', function () { open(); filter(); });
    if (caret) { caret.addEventListener('mousedown', function (e) { e.preventDefault(); menu.hidden ? (input.focus(), open()) : close(); }); }
    filters.forEach(function (btn) {
        btn.addEventListener('mousedown', function (e) {
            e.preventDefault();
            toneFilter = btn.getAttribute('data-filter') || 'all';
            filters.forEach(function (b) { b.classList.toggle('active', b === btn); });
            open();
            filter();
        });
    });

    input.addEventListener('keydown', function (e) {
        var vis = visibleItems();
        if (e.key === 'ArrowDown') { e.preventDefault(); open(); active = Math.min(active + 1, vis.length - 1); paintActive(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); paintActive(); }
        else if (e.key === 'Enter') { if (!menu.hidden && active >= 0 && vis[active]) { e.preventDefault(); choose(vis[active]); } }
        else if (e.key === 'Escape') { close(); }
    });

    items.forEach(function (it) {
        it.addEventListener('mousedown', function (e) { e.preventDefault(); choose(it); });
    });

    document.addEventListener('click', function (e) {
        if (!combo.contains(e.target)) { close(); }
    });
})();
</script>
