<?php /** Super Admin — all customers. Rendered inside layout.php.
 * Design ported from the TrackmeNew invoice-listing table view: soft-shadowed
 * white panels (radius 8px), a gradient hero, snapshot stat cards, an
 * uppercase-header table with light dividers + hover, and color-coded 32px
 * square action icons. */ ?>

<?php if ($np = session()->getFlashdata('new_password')): ?>
    <div class="alert alert-success alert-dismissible d-flex flex-wrap align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-shield-lock-fill fs-5"></i>
        <div>
            New password for <strong><?= esc(session()->getFlashdata('new_password_for')) ?></strong>:
            <code id="npValue" class="fs-6 user-select-all"><?= esc($np) ?></code>
            <button type="button" class="btn btn-sm btn-outline-success ms-1" data-copy="npValue">
                <i class="bi bi-clipboard"></i> Copy
            </button>
            <div class="small mt-1">
                <?= session()->getFlashdata('new_password_emailed') === '1'
                    ? '<i class="bi bi-envelope-check text-success"></i> Emailed to the customer. '
                    : '' ?>
                Share it privately — it is shown only once and the customer must change it on next login.
            </div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($rl = session()->getFlashdata('reset_link')): ?>
    <div class="alert alert-warning alert-dismissible d-flex flex-wrap align-items-center gap-2 shadow-sm" role="alert">
        <i class="bi bi-link-45deg fs-5"></i>
        <div>
            Reset link for <strong><?= esc(session()->getFlashdata('reset_link_for')) ?></strong>:
            <code id="rlValue" class="user-select-all"><?= esc($rl) ?></code>
            <button type="button" class="btn btn-sm btn-outline-warning ms-1" data-copy="rlValue">
                <i class="bi bi-clipboard"></i> Copy
            </button>
            <div class="small mt-1">Share privately — it expires in 1 hour.</div>
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php
// Toolbar state (with safe defaults). The table + pager live in the
// _customers_table partial (also served on its own for AJAX).
$per  = $per  ?? 25;
$perOpts = [25, 35, 50, 100];
?>
<div class="cust-page">

    <!-- Hero -->
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Customers</h4>
            <p class="cust-subtitle">Manage customer accounts, subscriptions, access and passwords — all in one place.</p>
        </div>
        <div class="cust-hero-actions">
            <a href="<?= site_url('admin/activate') ?>" class="cust-btn cust-btn-primary"><i class="bi bi-gem"></i> Activate Plan</a>
        </div>
    </section>

    <!-- Snapshot stat cards -->
    <section class="cust-snap-grid">
        <div class="cust-snap"><span class="cust-snap-ic ic-blue"><i class="bi bi-people-fill"></i></span>
            <div><p class="cust-snap-label">Total Customers</p><p class="cust-snap-value"><?= number_format((int) ($stats['total'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-green"><i class="bi bi-check-circle-fill"></i></span>
            <div><p class="cust-snap-label">Active</p><p class="cust-snap-value"><?= number_format((int) ($stats['active'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-gray"><i class="bi bi-slash-circle-fill"></i></span>
            <div><p class="cust-snap-label">Inactive</p><p class="cust-snap-value"><?= number_format((int) ($stats['inactive'] ?? 0)) ?></p></div></div>
        <div class="cust-snap"><span class="cust-snap-ic ic-violet"><i class="bi bi-building-fill"></i></span>
            <div><p class="cust-snap-label">Total Firms</p><p class="cust-snap-value"><?= number_format((int) ($stats['firms'] ?? 0)) ?></p></div></div>
    </section>

    <!-- Table panel -->
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Customer Records</h5>
                <p class="cust-table-note">Open a subscription, sign in as the customer, or reset their access from the actions.</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="<?= site_url('admin/customers/trash') ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash3 me-1"></i> Trash</a>
                <span class="cust-total-tag"><i class="bi bi-people"></i> <?= number_format((int) ($stats['total'] ?? 0)) ?> total</span>
            </div>
        </div>

        <!-- DataTables-style controls: page-size (Records) + Search -->
        <div class="cust-tabletools">
            <form method="get" class="cust-len" role="search">
                <?php if ($search !== ''): ?><input type="hidden" name="q" value="<?= esc($search, 'attr') ?>"><?php endif; ?>
                <label>Show</label>
                <select name="per" class="cust-len-select">
                    <?php foreach ($perOpts as $opt): ?>
                        <option value="<?= $opt ?>" <?= ((string) $per === (string) $opt) ? 'selected' : '' ?>><?= $opt ?></option>
                    <?php endforeach; ?>
                    <option value="all" <?= ($per === 'all') ? 'selected' : '' ?>>All</option>
                </select>
                <label>Records</label>
            </form>

            <form method="get" class="cust-find" role="search">
                <?php if ($per !== 25): ?><input type="hidden" name="per" value="<?= esc((string) $per, 'attr') ?>"><?php endif; ?>
                <label for="custSearch">Search:</label>
                <div class="cust-find-box">
                    <i class="bi bi-search"></i>
                    <input type="search" id="custSearch" name="q" value="<?= esc($search) ?>" placeholder="Name or email…" autocomplete="off">
                    <?php if ($search !== ''): ?><a href="<?= site_url('admin/customers') ?>" class="cust-find-clear" title="Clear"><i class="bi bi-x-lg"></i></a><?php endif; ?>
                </div>
            </form>
        </div>

        <!-- AJAX host: table + pager fragment. Live search / page-size / sort /
             pagination swap only this node's HTML, never the whole page. -->
        <div id="custTableHost" class="cust-host">
            <?= view('Modules\SuperAdmin\Views\_customers_table', [
                'rows' => $rows, 'per' => $per, 'sort' => $sort ?? 'id', 'dir' => $dir ?? 'desc',
                'search' => $search, 'offset' => $offset, 'pager' => $pager,
            ]) ?>
        </div>
    </section>
</div>

<!-- Set-password modal (shared; populated by the clicked row's button) -->
<div class="modal fade" id="setPwdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="setPwdForm" method="post" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-lock me-1"></i> Set password for <span id="setPwdName">customer</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary small mb-3">
                    Existing passwords can't be shown — they're stored one-way (bcrypt) and are unrecoverable.
                    Set a new one instead. Leave it blank to auto-generate a strong password. The customer will be
                    asked to change it on their next login.
                </p>
                <label class="form-label">New password <span class="text-muted">(optional — blank = generate)</span></label>
                <div class="input-group">
                    <input type="text" name="new_password" id="setPwdInput" class="form-control" autocomplete="off"
                           minlength="8" placeholder="Leave blank to auto-generate">
                    <button class="btn btn-outline-secondary" type="button" id="setPwdGen" title="Generate"><i class="bi bi-magic"></i></button>
                </div>
                <div class="form-text">At least 8 characters if you type your own.</div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="1" name="email_customer" id="setPwdEmail" checked>
                    <label class="form-check-label" for="setPwdEmail">Email the new password to the customer</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success"><i class="bi bi-check2 me-1"></i> Set password</button>
            </div>
        </form>
    </div>
</div>

<!-- Permanent-delete modal (type-to-confirm; populated by the clicked row's button) -->
<div class="modal fade" id="purgeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="purgeForm" method="post" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header" style="background:#fdecec">
                <h5 class="modal-title" style="color:#c53030"><i class="bi bi-exclamation-octagon-fill me-1"></i> Delete permanently</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">You are about to <strong>permanently delete</strong> <strong id="purgeName">this customer</strong> and <strong>everything</strong> tied to the account. <span style="color:#c53030;font-weight:700">This cannot be undone.</span></p>
                <ul class="small text-secondary mb-3">
                    <li><strong id="purgeFirms">0</strong> firm(s) — with all transactions, rokad, vouchers &amp; ledgers</li>
                    <li>Subscriptions, payment orders &amp; invoices</li>
                    <li>Notes, reminders, saved passwords &amp; calculator history</li>
                    <li>Firm-user accounts, logins, activity logs &amp; devices</li>
                </ul>
                <label class="form-label">Type the account name <code id="purgeExpect" class="text-danger"></code> to confirm</label>
                <input type="text" name="confirm_name" id="purgeConfirm" class="form-control" autocomplete="off" placeholder="Exact account name">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="purgeSubmit" disabled><i class="bi bi-trash3 me-1"></i> Delete permanently</button>
            </div>
        </form>
    </div>
</div>

<style>
/* ---- Customers listing — page-specific rules (shared cust-* components live
   in assets/css/erp-list.css, loaded globally from layout.php) -------------- */
.cust-name{display:flex;align-items:center;gap:10px}
.cust-avatar{display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;
    background:linear-gradient(135deg,#1769c2,#3b82f6);color:#fff;font-size:13px;font-weight:900;flex:0 0 auto}
.cust-name .fw-semibold{color:var(--c-ink)}
/* ---- Compact layout: fixed columns so the narrow Subscription pill frees room
   for Name/Email, which ellipsis-truncate; the full/detailed value for every
   clipped cell lives in its title tooltip (name+firms, plan+dates, email…). ---- */
.cust-table{table-layout:fixed}
.cust-table th,.cust-table td{box-sizing:border-box}
.cust-table th:nth-child(1),.cust-table td:nth-child(1){width:130px}   /* ID (fits CUS-999999 chip, no overflow) */
.cust-table th:nth-child(2),.cust-table td:nth-child(2){width:250px}   /* Name */
.cust-table th:nth-child(3),.cust-table td:nth-child(3){width:290px}   /* Email */
.cust-table .col-sub{width:112px}                                      /* Subscription (plan pill) */
.cust-table th:nth-child(5),.cust-table td:nth-child(5){width:124px}   /* Payment */
.cust-table th:nth-child(6),.cust-table td:nth-child(6){width:112px}   /* Status */
.cust-table th:nth-child(7),.cust-table td:nth-child(7){width:264px}   /* Actions (6 icon buttons) */
/* Name (col 2) + Email (col 3) truncate within their fixed width. */
.cust-name-txt{flex:1 1 auto;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cust-email .cust-muted{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
/* ===== Rich customer hover card (hover a name) — adapted from the TrackMe
   account preview, re-skinned to the ERP blue theme + Bootstrap Icons. ===== */
#custTip{position:fixed;z-index:12000;width:360px;max-width:94vw;pointer-events:none;opacity:0;
    transform:translateY(6px) scale(.98);transition:opacity .16s ease,transform .16s ease;font-family:inherit}
#custTip.show{opacity:1;transform:translateY(0) scale(1)}
.cust-tip-box{background:#fff;border:1px solid #e2e9f2;border-radius:16px;overflow:hidden;
    box-shadow:0 26px 70px rgba(15,30,60,.28),0 4px 12px rgba(15,30,60,.12)}
.cust-tip-head{position:relative;padding:15px 16px 13px;color:#fff;overflow:hidden;
    background:linear-gradient(135deg,#0c315f 0%,#1769c2 62%,#2f8fd6 100%)}
.cust-tip-head::after{content:'';position:absolute;right:-40px;top:-50px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.09)}
.cust-tip-head.is-inactive{background:linear-gradient(135deg,#4a5568 0%,#718096 100%)}
.cust-tip-eyebrow{display:flex;align-items:center;gap:7px;font-size:10.5px;font-weight:900;letter-spacing:.05em;text-transform:uppercase;opacity:.9;position:relative;z-index:1}
.cust-tip-name{margin:5px 0 0;font-size:17px;font-weight:900;line-height:1.2;position:relative;z-index:1;word-break:break-word}
.cust-tip-chips{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px;position:relative;z-index:1}
.cust-tip-chip{display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;font-size:10.5px;font-weight:800;background:rgba(255,255,255,.16);color:#fff}
.cust-tip-chip.ok{background:rgba(255,255,255,.26)}
.cust-tip-chip .bi{font-size:11px}
.cust-tip-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:1px;background:#eef2f7;border-bottom:1px solid #eef2f7}
.cust-tip-stat{background:#fff;padding:11px 6px;text-align:center;min-width:0}
.cust-tip-stat .v{font-size:14px;font-weight:900;color:#18243c;line-height:1.05;font-variant-numeric:tabular-nums;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.cust-tip-stat .l{display:block;margin-top:4px;font-size:9px;font-weight:800;text-transform:uppercase;letter-spacing:.02em;color:#8a97a8;white-space:nowrap}
.cust-tip-body{padding:13px 16px 14px}
.cust-tip-flow{margin-bottom:12px}
.cust-tip-flow-top{display:flex;justify-content:space-between;font-size:11px;font-weight:900;margin-bottom:5px}
.cust-tip-flow-top .dep{color:#1769c2}.cust-tip-flow-top .exp{color:#c0392b}
.cust-tip-bar{height:9px;border-radius:20px;overflow:hidden;display:flex;background:#eef2f7}
.cust-tip-bar .b-dep{background:linear-gradient(90deg,#1769c2,#2f8fd6)}
.cust-tip-bar .b-exp{background:linear-gradient(90deg,#ef4444,#f87171)}
.cust-tip-flow-sub{display:flex;justify-content:space-between;margin-top:5px;font-size:10px;font-weight:800;color:#8a97a8}
.cust-tip-rows{border-top:1px dashed #e6edf5;padding-top:11px}
.cust-tip-row{display:flex;align-items:flex-start;gap:9px;padding:5px 0;font-size:12px}
.cust-tip-row .ic{flex:0 0 auto;width:22px;height:22px;border-radius:7px;display:grid;place-items:center;background:#eef4fc;color:#1769c2;font-size:11px;margin-top:1px}
.cust-tip-row .ct{min-width:0;flex:1}
.cust-tip-row .rl{font-size:9.5px;font-weight:900;text-transform:uppercase;letter-spacing:.03em;color:#a0aec0}
.cust-tip-row .rv{font-weight:800;color:#26374f;word-break:break-word}
.cust-tip-row .rv .muted{color:#b0bac7;font-weight:700}
.cust-tip-foot{padding:9px 16px;background:#f8fafc;border-top:1px solid #eef2f7;font-size:10.5px;font-weight:800;color:#8a97a8;display:flex;align-items:center;gap:6px}
.cust-tip-foot b{color:#516174}
.cust-hover-name{cursor:default;border-bottom:1px dashed transparent}
.cust-hover-name:hover{border-bottom-color:#c7d7ea}
@media (max-width:480px){#custTip{width:300px}.cust-tip-stats{grid-template-columns:repeat(2,1fr)}}
.cust-sub-link{display:inline-flex;flex-direction:column;line-height:1.25;text-decoration:none}
.cust-sub-plan{color:var(--c-ink);font-weight:800;font-size:13px}
.cust-sub-status{color:var(--c-muted);font-size:11px;font-weight:700;text-transform:capitalize}
</style>

<script>
(function () {
    var modal = document.getElementById('setPwdModal');
    if (!modal) return;
    modal.addEventListener('show.bs.modal', function (ev) {
        var btn = ev.relatedTarget;
        if (!btn) return;
        var id = btn.getAttribute('data-id');
        document.getElementById('setPwdName').textContent = btn.getAttribute('data-name') || ('#' + id);
        document.getElementById('setPwdForm').setAttribute('action', '<?= site_url('admin/customers/set-password') ?>/' + id);
        document.getElementById('setPwdInput').value = '';
    });
    document.getElementById('setPwdGen').addEventListener('click', function () {
        var lower = 'abcdefghijkmnpqrstuvwxyz', upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ', digits = '23456789', sym = '@#%&*!?';
        var all = lower + upper + digits + sym, out = [
            lower[Math.floor(Math.random() * lower.length)],
            upper[Math.floor(Math.random() * upper.length)],
            digits[Math.floor(Math.random() * digits.length)],
            sym[Math.floor(Math.random() * sym.length)]
        ];
        while (out.length < 12) out.push(all[Math.floor(Math.random() * all.length)]);
        for (var i = out.length - 1; i > 0; i--) { var j = Math.floor(Math.random() * (i + 1)); var t = out[i]; out[i] = out[j]; out[j] = t; }
        document.getElementById('setPwdInput').value = out.join('');
    });
})();
</script>

<script>
/* Permanent-delete modal: populate from the clicked row + require typing the
   exact account name before the Delete button enables. */
(function () {
    var modal = document.getElementById('purgeModal');
    if (!modal) return;
    var form = document.getElementById('purgeForm');
    var input = document.getElementById('purgeConfirm');
    var submit = document.getElementById('purgeSubmit');
    var expected = '';

    function norm(s) { return (s || '').trim().toLowerCase(); }
    function sync() { submit.disabled = norm(input.value) === '' || norm(input.value) !== norm(expected); }

    modal.addEventListener('show.bs.modal', function (ev) {
        var btn = ev.relatedTarget;
        if (!btn) return;
        var id = btn.getAttribute('data-id');
        expected = btn.getAttribute('data-name') || ('#' + id);
        form.setAttribute('action', '<?= site_url('admin/customers/purge') ?>/' + id);
        document.getElementById('purgeName').textContent = expected;
        document.getElementById('purgeExpect').textContent = expected;
        document.getElementById('purgeFirms').textContent = btn.getAttribute('data-firms') || '0';
        input.value = '';
        sync();
    });
    modal.addEventListener('shown.bs.modal', function () { input.focus(); });
    input.addEventListener('input', sync);
    form.addEventListener('submit', function (e) {
        if (submit.disabled) { e.preventDefault(); return; }
        submit.disabled = true;
        submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting…';
    });
})();
</script>

<script>
/* Live customers table — search / page-size / sort / pagination via AJAX.
   Only #custTableHost is swapped; the whole page never reloads. */
(function () {
    var host   = document.getElementById('custTableHost');
    if (!host) return;
    var lenSel = document.querySelector('.cust-len-select');
    var lenFrm = document.querySelector('.cust-len');
    var search = document.getElementById('custSearch');
    var findFrm = document.querySelector('.cust-find');
    var DATA_PATH = '<?= site_url('admin/customers/data') ?>';
    var PAGE_PATH = '<?= site_url('admin/customers') ?>';

    // Turn a pretty /admin/customers?… URL into its /data AJAX twin.
    function toDataUrl(pretty) {
        var u = new URL(pretty, location.origin);
        return DATA_PATH + u.search;
    }

    function loadUrl(prettyUrl, push) {
        host.classList.add('is-loading');
        fetch(toDataUrl(prettyUrl), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                host.innerHTML = d.html;
                if (push !== false) { history.pushState({ ajax: 1 }, '', prettyUrl); }
                host.classList.remove('is-loading');
                // keep focus in the search box while typing
                if (document.activeElement !== search && push !== false && window.__custKeepFocus) {
                    search.focus();
                }
            })
            .catch(function () {
                host.classList.remove('is-loading');
                if (window.erpNotify) { erpNotify('error', 'Could not load customers.'); }
            });
    }

    // Build a new URL from the current one, applying overrides (null = remove).
    function go(overrides) {
        var p = new URLSearchParams(location.search);
        Object.keys(overrides).forEach(function (k) {
            var v = overrides[k];
            if (v === null || v === '' || v === undefined) { p.delete(k); } else { p.set(k, v); }
        });
        var qs = p.toString();
        loadUrl(PAGE_PATH + (qs ? '?' + qs : ''));
    }

    // Debounced live search across ALL columns.
    var t = null;
    if (search) {
        search.addEventListener('input', function () {
            clearTimeout(t);
            window.__custKeepFocus = true;
            t = setTimeout(function () { go({ q: search.value.trim(), page: null }); }, 300);
        });
    }
    if (findFrm) {
        findFrm.addEventListener('submit', function (e) { e.preventDefault(); clearTimeout(t); go({ q: search.value.trim(), page: null }); });
    }

    // Page-size selector.
    if (lenSel) { lenSel.addEventListener('change', function () { window.__custKeepFocus = false; go({ per: lenSel.value, page: null }); }); }
    if (lenFrm) { lenFrm.addEventListener('submit', function (e) { e.preventDefault(); }); }

    // Delegated: sort headers + pager buttons inside the (replaceable) host.
    host.addEventListener('click', function (e) {
        var a = e.target.closest('.cust-sort, .erp-pager__btn');
        if (!a || !host.contains(a)) { return; }
        if (a.classList.contains('is-active')) { e.preventDefault(); return; }
        e.preventDefault();
        window.__custKeepFocus = false;
        loadUrl(a.getAttribute('href'));
    });

    // Back / forward buttons re-sync the fragment without pushing a new entry.
    window.addEventListener('popstate', function () { loadUrl(location.href, false); });
})();
</script>

<!-- Rich customer hover card (mouse over a name) — data comes from each name
     cell's data-tip JSON; delegated so it keeps working after AJAX table swaps. -->
<div id="custTip" aria-hidden="true"></div>
<script>
(function () {
    var tip = document.getElementById('custTip');
    if (!tip) { return; }
    var hideTimer = null, curEl = null;
    function esc(s) { var d = document.createElement('div'); d.textContent = (s == null ? '' : String(s)); return d.innerHTML; }
    function cap(s) { s = String(s || ''); return s ? s.charAt(0).toUpperCase() + s.slice(1) : ''; }
    function tile(v, l) { return '<div class="cust-tip-stat"><div class="v">' + v + '</div><span class="l">' + l + '</span></div>'; }
    function row(ic, l, v) { return '<div class="cust-tip-row"><span class="ic"><i class="bi bi-' + ic + '"></i></span><div class="ct"><div class="rl">' + l + '</div><div class="rv">' + v + '</div></div></div>'; }

    function build(o) {
        var headCls = o.status === 'inactive' ? 'is-inactive' : '';
        var chips = '<span class="cust-tip-chip ok"><i class="bi bi-' + (o.status === 'active' ? 'check-circle-fill' : 'pause-circle-fill') + '"></i> ' + cap(o.status) + '</span>';
        if (o.plan) { chips += '<span class="cust-tip-chip"><i class="bi bi-gem"></i> ' + esc(o.plan) + '</span>'; }
        chips += '<span class="cust-tip-chip"><i class="bi bi-' + (o.source === 'Google' ? 'google' : 'display') + '"></i> ' + esc(o.source || 'Web') + '</span>';

        var stats = tile(o.firms, 'Firms') + tile(esc(o.plan || '—'), 'Plan')
                  + tile(cap(o.payment || '—'), 'Payment') + tile(o.last_ago ? esc(o.last_ago) : '—', 'Last seen');

        var flow = '';
        if ((o.started || o.expires) && o.valid_pct != null) {
            var pct = Math.max(0, Math.min(100, o.valid_pct));
            var expired = (o.days_left != null && o.days_left < 0);
            var right = o.days_left != null ? (expired ? 'Expired' : o.days_left + ' days left') : '';
            flow = '<div class="cust-tip-flow">'
                 +   '<div class="cust-tip-flow-top"><span class="dep">' + esc(o.plan || 'Subscription') + '</span><span class="' + (expired ? 'exp' : 'dep') + '">' + right + '</span></div>'
                 +   '<div class="cust-tip-bar"><div class="b-' + (expired ? 'exp' : 'dep') + '" style="width:' + pct + '%"></div></div>'
                 +   '<div class="cust-tip-flow-sub"><span>' + esc(o.started || '—') + '</span><span>' + esc(o.expires || '—') + '</span></div>'
                 + '</div>';
        }

        var rows = row('envelope', 'Email', esc(o.email || '—'));
        if (o.mobile) { rows += row('telephone', 'Mobile', esc(o.mobile)); }
        rows += row('gem', 'Plan', esc(o.plan || '—') + (o.plan_status ? ' <span class="muted">· ' + esc(cap(o.plan_status)) + '</span>' : ''));
        if (o.started || o.expires) { rows += row('calendar-range', 'Subscription', esc(o.started || '—') + ' <span class="muted">→</span> ' + esc(o.expires || '—')); }
        rows += row('building', 'Firms owned', o.firms + (String(o.firms) === '1' ? ' firm' : ' firms'));

        var foot = '<div class="cust-tip-foot"><i class="bi bi-clock"></i> Joined <b>' + esc(o.created || '—') + '</b>'
                 + (o.created_ago ? ' <span>(' + esc(o.created_ago) + ')</span>' : '') + '</div>';

        return '<div class="cust-tip-box">'
             +   '<div class="cust-tip-head ' + headCls + '">'
             +     '<div class="cust-tip-eyebrow"><i class="bi bi-person-badge"></i> Customer · #' + esc(o.id) + '</div>'
             +     '<div class="cust-tip-name">' + esc(o.name) + '</div>'
             +     '<div class="cust-tip-chips">' + chips + '</div>'
             +   '</div>'
             +   '<div class="cust-tip-stats">' + stats + '</div>'
             +   '<div class="cust-tip-body">' + flow + '<div class="cust-tip-rows">' + rows + '</div></div>'
             +   foot
             + '</div>';
    }

    function place(el) {
        var r = el.getBoundingClientRect(), tw = tip.offsetWidth, th = tip.offsetHeight, vw = window.innerWidth, vh = window.innerHeight, pad = 10;
        var left = r.left, top = r.bottom + 8;
        if (left + tw > vw - pad) { left = Math.max(pad, vw - tw - pad); }
        if (top + th > vh - pad) { top = r.top - th - 8; }
        if (top < pad) { top = pad; }
        tip.style.left = Math.round(left) + 'px';
        tip.style.top = Math.round(top) + 'px';
    }
    function open(el) {
        var raw = el.getAttribute('data-tip'); if (!raw) { return; }
        var o; try { o = JSON.parse(raw); } catch (e) { return; }
        curEl = el;
        tip.innerHTML = build(o);
        tip.setAttribute('aria-hidden', 'false');
        place(el); place(el); // measure then reposition with height known
        tip.classList.add('show');
    }
    function close() { tip.classList.remove('show'); tip.setAttribute('aria-hidden', 'true'); curEl = null; }

    document.addEventListener('mouseover', function (e) {
        var el = e.target.closest ? e.target.closest('.cust-hover-name') : null;
        if (!el) { return; }
        clearTimeout(hideTimer); open(el);
    });
    document.addEventListener('mouseout', function (e) {
        var el = e.target.closest ? e.target.closest('.cust-hover-name') : null;
        if (!el) { return; }
        hideTimer = setTimeout(close, 120);
    });
    window.addEventListener('scroll', function () { if (curEl) { place(curEl); } }, true);
})();
</script>
