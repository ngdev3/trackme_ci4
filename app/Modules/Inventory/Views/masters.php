<?php
/**
 * Inventory Setup — a guided wizard. Only one step's fields show at a time
 * (Products → Godowns → Parties → Go Live) with Next / Previous navigation and a
 * live completion score. The active step survives form submits (localStorage) so
 * adding an item keeps you on the same step. Owner/admin only.
 */
$sc      = $setup ?? ['score' => 0, 'done' => 0, 'total' => 0, 'complete' => false, 'steps' => [], 'next' => null];
$score   = (int) $sc['score'];
$circ    = 2 * M_PI * 26;
$off     = $circ * (1 - $score / 100);
$ring    = $score >= 100 ? '#16a34a' : ($score >= 60 ? '#2563eb' : ($score >= 30 ? '#f59e0b' : '#dc2626'));

// Index the score steps by key for per-panel tips.
$by = [];
foreach ($sc['steps'] as $s) { $by[$s['key']] = $s; }
$doneOf = static fn ($k) => ! empty($by[$k]['done']);
$tip    = static fn ($k) => $by[$k] ?? null;

// Wizard steps (each maps to one form/panel).
$wiz = [
    ['key' => 'products', 'label' => 'Products', 'icon' => 'bi-box',      'done' => $doneOf('products')],
    ['key' => 'godowns',  'label' => 'Godowns',  'icon' => 'bi-buildings','done' => $doneOf('godowns')],
    ['key' => 'parties',  'label' => 'Parties',  'icon' => 'bi-people',   'done' => $doneOf('parties')],
    ['key' => 'golive',   'label' => 'Go Live',  'icon' => 'bi-rocket-takeoff', 'done' => $doneOf('stock')],
];

// Validation feedback (only for the form that actually failed).
$errors  = $errors ?? [];
$errForm = $errForm ?? null;
$fval = static fn ($form, $field) => $errForm === $form ? (string) old($field, '') : '';
$ferr = static function ($form, $field) use ($errForm, $errors) {
    return ($errForm === $form && isset($errors[$field]))
        ? '<div class="invalid-feedback d-block">' . esc($errors[$field]) . '</div>' : '';
};
$fcls = static fn ($form, $field) => ($errForm === $form && isset($errors[$field])) ? ' is-invalid' : '';

// Which step to open first: the failed form, else the "next step" to complete.
$startMap = ['products' => 0, 'weights' => 0, 'rates' => 0, 'lowstock' => 0, 'godowns' => 1, 'capacity' => 1, 'parties' => 2, 'stock' => 3];
$errStep  = ['product' => 0, 'warehouse' => 1, 'party' => 2];
$startStep = $errForm !== null && isset($errStep[$errForm])
    ? $errStep[$errForm]
    : ($sc['next'] ? ($startMap[$sc['next']['key']] ?? 0) : 0);
$active    = static fn ($i) => $i === $startStep; // initial (server-side) visible step

/** Small tip pill for a sub-requirement (weights / rates / capacity / low-stock). */
$tipPill = static function (?array $t) {
    if (! $t) { return ''; }
    $cls = $t['done'] ? 'ok' : 'todo';
    $ic  = $t['done'] ? 'bi-check-circle-fill' : 'bi-info-circle';
    return '<span class="inv-tip ' . $cls . '"><i class="bi ' . $ic . '"></i>' . esc($t['label']) . ' — ' . esc($t['detail']) . '</span>';
};
?>
<div class="inv-masters">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="bi bi-gear me-2"></i>Inventory Setup</h2>
        <a href="<?= site_url('inventory') ?>" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Back</a>
    </div>

    <div class="inv-wiz" id="invWiz" data-start="<?= (int) $startStep ?>" data-steps="<?= count($wiz) ?>" data-force="<?= $errForm !== null ? '1' : '0' ?>">

        <!-- ===== Step tabs + completion ring ===== -->
        <div class="inv-wiz-head">
            <div class="inv-wiz-tabs" role="tablist">
                <?php foreach ($wiz as $i => $w): ?>
                    <button type="button" class="inv-wiz-tab<?= $w['done'] ? ' done' : '' ?><?= $active($i) ? ' active' : '' ?>" data-step="<?= $i ?>" role="tab" aria-controls="wiz-panel-<?= $i ?>">
                        <span class="num"><span class="n"><?= $i + 1 ?></span><i class="bi bi-check-lg"></i></span>
                        <span class="lbl"><i class="bi <?= esc($w['icon']) ?> me-1"></i><?= esc($w['label']) ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <div class="inv-wiz-score" title="Setup completion">
                <svg viewBox="0 0 64 64" width="60" height="60" aria-hidden="true">
                    <circle cx="32" cy="32" r="26" fill="none" stroke="var(--bs-tertiary-bg,#e9ecef)" stroke-width="7"></circle>
                    <circle cx="32" cy="32" r="26" fill="none" stroke="<?= $ring ?>" stroke-width="7" stroke-linecap="round"
                            stroke-dasharray="<?= round($circ, 1) ?>" stroke-dashoffset="<?= round($off, 1) ?>" transform="rotate(-90 32 32)"></circle>
                    <text x="32" y="37" text-anchor="middle" font-size="16" font-weight="800" fill="<?= $ring ?>"><?= $score ?>%</text>
                </svg>
                <span class="inv-wiz-score-l"><?= (int) $sc['done'] ?>/<?= (int) $sc['total'] ?><br>done</span>
            </div>
        </div>

        <!-- ===== Panels (only the active one shows) ===== -->
        <div class="inv-wiz-panels">

            <!-- Step 1: Products -->
            <section class="inv-wiz-panel" id="wiz-panel-0" data-step="0" role="tabpanel" <?= $active(0) ? "" : "hidden" ?>>
                <div class="inv-wiz-panel-head"><h3><i class="bi bi-box me-1"></i>Products</h3><p>The goods you store. Set weight &amp; rate so stock auto-fills and values itself.</p></div>
                <form action="<?= site_url('inventory/masters/product') ?>" method="post" class="row g-2 inv-wiz-form" data-addnext="1">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label>Product name <span class="req">*</span></label>
                        <input name="name" class="form-control form-control-lg<?= $fcls('product', 'name') ?>" placeholder="e.g. Potato" maxlength="150" required value="<?= esc($fval('product', 'name'), 'attr') ?>">
                        <?= $ferr('product', 'name') ?>
                    </div>
                    <div class="col-md-4 inv-wiz-field">
                        <label>Weight of one bag (kg) <span class="opt">optional</span></label>
                        <input name="avg_weight" type="number" step="0.01" min="0" max="100000" class="form-control<?= $fcls('product', 'avg_weight') ?>" placeholder="e.g. 50" value="<?= esc($fval('product', 'avg_weight'), 'attr') ?>">
                        <small class="inv-help">Kg one bag weighs — auto-fills total weight from the bag count.</small>
                        <?= $ferr('product', 'avg_weight') ?>
                    </div>
                    <div class="col-md-4 inv-wiz-field">
                        <label>Price per bag (₹) <span class="opt">optional</span></label>
                        <input name="rate" type="number" step="0.01" min="0" class="form-control<?= $fcls('product', 'rate') ?>" placeholder="e.g. 2000" value="<?= esc($fval('product', 'rate'), 'attr') ?>">
                        <small class="inv-help">Value of one bag — shows your total inventory value.</small>
                        <?= $ferr('product', 'rate') ?>
                    </div>
                    <div class="col-md-4 inv-wiz-field">
                        <label>Low-stock alert (bags) <span class="opt">optional</span></label>
                        <input name="low_stock" type="number" min="0" step="1" class="form-control<?= $fcls('product', 'low_stock') ?>" placeholder="e.g. 20" value="<?= esc($fval('product', 'low_stock'), 'attr') ?>">
                        <small class="inv-help">We warn you when stock drops to this many bags.</small>
                        <?= $ferr('product', 'low_stock') ?>
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Product</button></div>
                </form>
                <div class="inv-wiz-tips"><?= $tipPill($tip('weights')) . $tipPill($tip('rates')) . $tipPill($tip('lowstock')) ?></div>
                <?php if (empty($products)): ?><p class="text-muted small mb-0">No products yet — add your first above.</p>
                <?php else: ?>
                    <div class="inv-wiz-count"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?></div>
                    <ul class="inv-master-list">
                        <?php foreach ($products as $p): ?>
                            <li><span><?= esc($p['name']) ?><small><?= $p['avg_weight'] > 0 ? esc($p['avg_weight']) . ' kg/bag' : '' ?><?= ! empty($p['rate']) && $p['rate'] > 0 ? ' · ₹' . esc(number_format((float) $p['rate'], 0)) . '/bag' : '' ?></small></span>
                                <?php if (! empty($canDelete)): ?><?= view('Modules\Inventory\Views\_del', ['type' => 'product', 'id' => $p['id']]) ?><?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <!-- Step 2: Godowns -->
            <section class="inv-wiz-panel" id="wiz-panel-1" data-step="1" role="tabpanel" <?= $active(1) ? "" : "hidden" ?>>
                <div class="inv-wiz-panel-head"><h3><i class="bi bi-buildings me-1"></i>Godowns / Warehouses</h3><p>Where stock is kept. Add capacity to track utilisation.</p></div>
                <form action="<?= site_url('inventory/masters/warehouse') ?>" method="post" class="row g-2 inv-wiz-form">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label>Godown name <span class="req">*</span></label>
                        <input name="name" class="form-control form-control-lg<?= $fcls('warehouse', 'name') ?>" placeholder="e.g. Main Godown" maxlength="150" required value="<?= esc($fval('warehouse', 'name'), 'attr') ?>">
                        <?= $ferr('warehouse', 'name') ?>
                    </div>
                    <div class="col-md-7 inv-wiz-field">
                        <label>Location <span class="opt">optional</span></label>
                        <input name="location" class="form-control<?= $fcls('warehouse', 'location') ?>" placeholder="e.g. Gate 1" maxlength="191" value="<?= esc($fval('warehouse', 'location'), 'attr') ?>">
                        <small class="inv-help">Where this godown is, to tell it apart from others.</small>
                        <?= $ferr('warehouse', 'location') ?>
                    </div>
                    <div class="col-md-5 inv-wiz-field">
                        <label>Capacity (bags) <span class="opt">optional</span></label>
                        <input name="capacity" type="number" min="0" step="1" class="form-control<?= $fcls('warehouse', 'capacity') ?>" placeholder="e.g. 5000" value="<?= esc($fval('warehouse', 'capacity'), 'attr') ?>">
                        <small class="inv-help">Most bags it can hold — powers the utilisation gauge.</small>
                        <?= $ferr('warehouse', 'capacity') ?>
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Godown</button></div>
                </form>
                <div class="inv-wiz-tips"><?= $tipPill($tip('capacity')) ?></div>
                <?php if (empty($warehouses)): ?><p class="text-muted small mb-0">No godowns yet — add your first above.</p>
                <?php else: ?>
                    <div class="inv-wiz-count"><?= count($warehouses) ?> godown<?= count($warehouses) === 1 ? '' : 's' ?></div>
                    <ul class="inv-master-list">
                        <?php foreach ($warehouses as $w): ?>
                            <li><span><?= esc($w['name']) ?><small><?= esc($w['location'] ?? '') ?><?= (int) $w['capacity'] > 0 ? ' · ' . esc(number_format((float) $w['capacity'], 0)) . ' bags' : '' ?></small></span>
                                <?php if (! empty($canDelete)): ?><?= view('Modules\Inventory\Views\_del', ['type' => 'warehouse', 'id' => $w['id']]) ?><?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <!-- Step 3: Parties -->
            <section class="inv-wiz-panel" id="wiz-panel-2" data-step="2" role="tabpanel" <?= $active(2) ? "" : "hidden" ?>>
                <div class="inv-wiz-panel-head"><h3><i class="bi bi-people me-1"></i>Parties</h3><p>Who you buy from and sell to. Add at least one supplier and one customer.</p></div>
                <?php $ptype = $fval('party', 'type'); ?>
                <form action="<?= site_url('inventory/masters/party') ?>" method="post" class="row g-2 inv-wiz-form">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <label>Party name <span class="req">*</span></label>
                        <input name="name" class="form-control form-control-lg<?= $fcls('party', 'name') ?>" placeholder="e.g. Sharma Traders" maxlength="150" required value="<?= esc($fval('party', 'name'), 'attr') ?>">
                        <?= $ferr('party', 'name') ?>
                    </div>
                    <div class="col-md-6">
                        <label>Type</label>
                        <select name="type" class="form-select">
                            <option value="both"<?= $ptype === 'both' ? ' selected' : '' ?>>Supplier &amp; Customer</option>
                            <option value="supplier"<?= $ptype === 'supplier' ? ' selected' : '' ?>>Supplier / Farmer</option>
                            <option value="customer"<?= $ptype === 'customer' ? ' selected' : '' ?>>Customer</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label>Phone</label>
                        <input name="phone" type="tel" inputmode="tel" class="form-control<?= $fcls('party', 'phone') ?>" placeholder="Phone" maxlength="20" value="<?= esc($fval('party', 'phone'), 'attr') ?>">
                        <?= $ferr('party', 'phone') ?>
                    </div>
                    <div class="col-12"><button class="btn btn-primary"><i class="bi bi-plus-circle me-1"></i>Add Party</button></div>
                </form>
                <div class="inv-wiz-tips"><?= $tipPill($tip('parties')) ?></div>
                <?php if (empty($parties)): ?><p class="text-muted small mb-0">No parties yet — add your first above.</p>
                <?php else: ?>
                    <div class="inv-wiz-count"><?= count($parties) ?> part<?= count($parties) === 1 ? 'y' : 'ies' ?></div>
                    <ul class="inv-master-list">
                        <?php foreach ($parties as $pt): ?>
                            <li><span><?= esc($pt['name']) ?><small><?= esc(ucfirst($pt['type'])) ?><?= ! empty($pt['phone']) ? ' · ' . esc($pt['phone']) : '' ?></small></span>
                                <?php if (! empty($canDelete)): ?><?= view('Modules\Inventory\Views\_del', ['type' => 'party', 'id' => $pt['id']]) ?><?php endif; ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <!-- Step 4: Go Live -->
            <section class="inv-wiz-panel" id="wiz-panel-3" data-step="3" role="tabpanel" <?= $active(3) ? "" : "hidden" ?>>
                <div class="inv-wiz-panel-head"><h3><i class="bi bi-rocket-takeoff me-1"></i>Go Live</h3><p>You’re ready — record your first stock to start tracking inventory.</p></div>
                <div class="inv-wiz-summary">
                    <div class="s"><span class="v"><?= count($products) ?></span><span class="l">Products</span></div>
                    <div class="s"><span class="v"><?= count($warehouses) ?></span><span class="l">Godowns</span></div>
                    <div class="s"><span class="v"><?= count($parties) ?></span><span class="l">Parties</span></div>
                    <div class="s <?= $doneOf('stock') ? 'ok' : '' ?>"><span class="v"><?= $doneOf('stock') ? '✓' : '—' ?></span><span class="l">First stock</span></div>
                </div>
                <?php if ($sc['complete']): ?>
                    <div class="inv-wiz-live done"><i class="bi bi-check-circle-fill me-2"></i>Setup complete — your inventory is live!</div>
                <?php else: ?>
                    <div class="inv-wiz-live"><i class="bi bi-info-circle me-2"></i>Setup is <?= $score ?>% complete. Finish the highlighted steps for the best experience.</div>
                <?php endif; ?>
                <div class="d-flex gap-2 flex-wrap mt-2">
                    <a href="<?= site_url('inventory/inward') ?>" class="inv-save in" style="text-decoration:none;padding:.8rem 1.4rem;"><i class="bi bi-box-arrow-in-down me-1"></i>Record Stock Inward</a>
                    <a href="<?= site_url('inventory') ?>" class="btn btn-light border btn-lg"><i class="bi bi-house me-1"></i>Inventory Home</a>
                </div>
            </section>
        </div>

        <!-- ===== Prev / Next navigation ===== -->
        <div class="inv-wiz-nav">
            <button type="button" class="btn btn-light border" id="wizPrev"><i class="bi bi-chevron-left me-1"></i>Previous</button>
            <span class="inv-wiz-dots" id="wizDots"></span>
            <button type="button" class="btn btn-primary" id="wizNext">Next<i class="bi bi-chevron-right ms-1"></i></button>
        </div>
    </div>
</div>

<script>
(function () {
    var wiz = document.getElementById('invWiz');
    if (!wiz) { return; }
    var total = parseInt(wiz.dataset.steps, 10) || 1;
    var tabs  = Array.prototype.slice.call(wiz.querySelectorAll('.inv-wiz-tab'));
    var panels= Array.prototype.slice.call(wiz.querySelectorAll('.inv-wiz-panel'));
    var prev  = document.getElementById('wizPrev');
    var next  = document.getElementById('wizNext');
    var dots  = document.getElementById('wizDots');
    var KEY   = 'invSetupStep';

    // Build progress dots.
    for (var i = 0; i < total; i++) {
        var d = document.createElement('span'); d.className = 'inv-wiz-dot'; dots.appendChild(d);
    }
    var dotEls = Array.prototype.slice.call(dots.children);

    // A validation error forces its step open (overriding the saved step);
    // otherwise restore the last step (localStorage), else the suggested start.
    var start = parseInt(wiz.dataset.start, 10) || 0;
    var cur;
    if (wiz.dataset.force === '1') {
        cur = start;
        try { localStorage.setItem(KEY, String(cur)); } catch (e) {}
    } else {
        cur = parseInt(localStorage.getItem(KEY), 10);
        if (isNaN(cur) || cur < 0 || cur >= total) { cur = start; }
    }

    // The form (if any) inside the currently open step.
    function currentForm() {
        var p = panels[cur];
        return p ? p.querySelector('.inv-wiz-form') : null;
    }
    // "Add mode" = the open step is the Products master (data-addnext) and its name
    // field is filled but not yet saved — so the primary button adds it instead of
    // skipping past unsaved data. Only the product step opts in.
    function isAddMode() {
        var f = currentForm();
        if (!f || f.dataset.addnext !== '1') { return false; }
        var n = f.querySelector('input[name="name"]');
        return !!(n && n.value.trim() !== '');
    }

    // Refresh the primary button's label between "Add", "Next" and "Finish".
    function updateNext() {
        if (isAddMode()) {
            next.classList.add('is-add');
            next.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Add';
        } else {
            next.classList.remove('is-add');
            next.innerHTML = cur === total - 1 ? 'Finish<i class="bi bi-check-lg ms-1"></i>' : 'Next<i class="bi bi-chevron-right ms-1"></i>';
        }
    }

    function show(n, save) {
        cur = Math.max(0, Math.min(total - 1, n));
        panels.forEach(function (p) { p.hidden = parseInt(p.dataset.step, 10) !== cur; });
        tabs.forEach(function (t) { t.classList.toggle('active', parseInt(t.dataset.step, 10) === cur); });
        dotEls.forEach(function (d, i) { d.classList.toggle('active', i === cur); d.classList.toggle('past', i < cur); });
        prev.disabled = cur === 0;
        updateNext();
        if (save !== false) { try { localStorage.setItem(KEY, String(cur)); } catch (e) {} }
    }

    tabs.forEach(function (t) { t.addEventListener('click', function () { show(parseInt(t.dataset.step, 10)); }); });
    prev.addEventListener('click', function () { show(cur - 1); wiz.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
    next.addEventListener('click', function () {
        // Unsaved data in this step → add it (validated) instead of moving on.
        if (isAddMode()) {
            var f = currentForm();
            if (f.requestSubmit) { f.requestSubmit(); } else { f.submit(); }
            return;
        }
        if (cur === total - 1) { window.location.href = '<?= site_url('inventory') ?>'; return; }
        show(cur + 1); wiz.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // Keep the user on the same step after a form submit, and toggle Add/Next live
    // as they type the name.
    wiz.querySelectorAll('.inv-wiz-form').forEach(function (f) {
        f.addEventListener('submit', function () { try { localStorage.setItem(KEY, String(cur)); } catch (e) {} });
        var n = f.querySelector('input[name="name"]');
        if (n) { n.addEventListener('input', updateNext); }
    });

    show(cur, false);

    // On a validation error, focus the first invalid field in the open step.
    if (wiz.dataset.force === '1') {
        var bad = panels[cur] && panels[cur].querySelector('.is-invalid');
        if (bad) { setTimeout(function () { bad.focus(); }, 250); }
    }
})();
</script>
