<?php
/**
 * Daily Stock — record IN (Purchase) / OUT (Sale) in one simple form, with the
 * party in a single field. Shows the selected day's entries and totals.
 */
$fmt  = static fn ($n) => number_format((float) $n, 0);
$money = static fn ($n) => (float) $n > 0 ? money($n) : '—';
$old  = $old ?? [];
$type = ($old['type'] ?? 'in') === 'out' ? 'out' : 'in';
$err  = static fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
?>
<div class="sinv">
    <!-- ===== Hero ===== -->
    <div class="inv-hero">
        <div class="inv-hero-main">
            <span class="inv-hero-kicker"><i class="bi bi-boxes"></i> Stock Register</span>
            <h1>Inventory</h1>
            <p class="inv-hero-sub">Record daily Purchase &amp; Sale in seconds — stock position, reports and the Jama/Naam ledger stay in sync automatically.</p>
            <div class="inv-hero-actions">
                <?php if (empty($hasProducts)): ?>
                    <a href="<?= site_url('inventory/products') ?>" class="inv-hero-btn solid"><i class="bi bi-plus-circle"></i> Add Products</a>
                <?php else: ?>
                    <a href="#sinvForm" class="inv-hero-btn solid"><i class="bi bi-plus-circle"></i> New Entry</a>
                    <a href="<?= site_url('inventory/products') ?>" class="inv-hero-btn"><i class="bi bi-box-seam"></i> Products</a>
                <?php endif; ?>
                <a href="<?= site_url('inventory/position') ?>" class="inv-hero-btn"><i class="bi bi-clipboard-data"></i> Stock Position</a>
            </div>
        </div>
        <div class="inv-hero-side">
            <div class="inv-hero-pill in"><span class="v">+<?= $fmt($tot['in_qty']) ?></span><span class="l">Today IN (Qtl)</span></div>
            <div class="inv-hero-pill out"><span class="v">−<?= $fmt($tot['out_qty']) ?></span><span class="l">Today OUT (Qtl)</span></div>
        </div>
    </div>

    <?= view('Modules\Inventory\Views\_nav', ['active' => 'daily']) ?>

    <?php if (empty($hasProducts)): ?>
        <div class="inv-onboard">
            <div class="inv-onboard-ic"><i class="bi bi-box-seam"></i></div>
            <h2>Let's set up your stock book</h2>
            <p>Add the goods you deal in once — then recording daily stock takes just seconds, and every entry keeps your ledger and reports up to date.</p>
            <div class="inv-steps">
                <div class="inv-step-card"><span class="inv-step-num">1</span><i class="bi bi-box-seam"></i><h4>Add products</h4><p>List the items you buy &amp; sell — just a name is enough.</p></div>
                <div class="inv-step-card"><span class="inv-step-num">2</span><i class="bi bi-arrow-down-up"></i><h4>Record daily stock</h4><p>Tap Purchase or Sale, enter qty &amp; rate — done.</p></div>
                <div class="inv-step-card"><span class="inv-step-num">3</span><i class="bi bi-clipboard-data"></i><h4>See your position</h4><p>Live stock by day, month &amp; year, with reports.</p></div>
            </div>
            <div class="inv-feats">
                <span class="inv-feat"><i class="bi bi-mic"></i> Voice entry</span>
                <span class="inv-feat"><i class="bi bi-qr-code-scan"></i> QR search</span>
                <span class="inv-feat"><i class="bi bi-link-45deg"></i> Auto-linked to Jama / Naam</span>
                <span class="inv-feat"><i class="bi bi-file-earmark-bar-graph"></i> Reports &amp; exports</span>
            </div>
            <a href="<?= site_url('inventory/products') ?>" class="btn btn-primary btn-lg"><i class="bi bi-plus-circle me-1"></i>Add Your First Product</a>
        </div>
    <?php else: ?>

    <!-- ===== KPI strip ===== -->
    <div class="inv-kpis">
        <div class="inv-kpi k-prod"><div class="inv-kpi-ic"><i class="bi bi-box-seam"></i></div><div><div class="v"><?= $fmt(count($products)) ?></div><div class="l">Products</div></div></div>
        <div class="inv-kpi k-in"><div class="inv-kpi-ic"><i class="bi bi-box-arrow-in-down"></i></div><div><div class="v">+<?= $fmt($tot['in_qty']) ?> <small class="unit-tag">Qtl</small></div><div class="l">Today Purchase</div></div></div>
        <div class="inv-kpi k-out"><div class="inv-kpi-ic"><i class="bi bi-box-arrow-up"></i></div><div><div class="v">−<?= $fmt($tot['out_qty']) ?> <small class="unit-tag">Qtl</small></div><div class="l">Today Sale</div></div></div>
        <div class="inv-kpi k-net"><div class="inv-kpi-ic"><i class="bi bi-graph-up-arrow"></i></div><div><div class="v"><?= $money((float) $tot['in_amt'] + (float) $tot['out_amt']) ?></div><div class="l">Today Value</div></div></div>
    </div>

    <div class="sinv-grid" id="sinvForm">
        <!-- ===== Entry form ===== -->
        <div class="sinv-card">
            <h3 class="sinv-h"><i class="bi bi-plus-circle me-1"></i>New Entry</h3>
            <form action="<?= site_url('inventory/save') ?>" method="post" class="sinv-form" autocomplete="off">
                <?= csrf_field() ?>

                <div class="sinv-type" id="sinvType">
                    <label class="sinv-type-opt in <?= $type === 'in' ? 'on' : '' ?>">
                        <input type="radio" name="type" value="in" <?= $type === 'in' ? 'checked' : '' ?>>
                        <i class="bi bi-box-arrow-in-down"></i><span>Purchase</span><small>Stock IN</small>
                    </label>
                    <label class="sinv-type-opt out <?= $type === 'out' ? 'on' : '' ?>">
                        <input type="radio" name="type" value="out" <?= $type === 'out' ? 'checked' : '' ?>>
                        <i class="bi bi-box-arrow-up"></i><span>Sale</span><small>Stock OUT</small>
                    </label>
                </div>

                <div class="sinv-row">
                    <div class="sinv-field">
                        <label>Date</label>
                        <input type="date" name="date" value="<?= esc($date, 'attr') ?>" max="<?= date('Y-m-d') ?>" class="form-control form-control-lg">
                        <?= $err('date') ?>
                    </div>
                    <div class="sinv-field">
                        <label>Product <span class="req">*</span></label>
                        <select name="product_id" class="form-select form-select-lg" required>
                            <option value="">— Choose —</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= esc($p['id']) ?>" data-rate="<?= esc($p['rate'] ?? 0, 'attr') ?>" data-stock="<?= esc((string) ($stockMap[$p['id']] ?? 0), 'attr') ?>" data-unit="<?= esc($p['unit'] ?? 'units', 'attr') ?>" <?= (int) ($old['product_id'] ?? 0) === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= $err('product_id') ?>
                    </div>
                </div>

                <div class="sinv-row">
                    <div class="sinv-field">
                        <label>Quantity <span class="unit-tag">Qtl</span> <span class="req">*</span></label>
                        <input type="number" name="qty" id="sinvQty" inputmode="decimal" min="0" step="0.01" required value="<?= esc($old['qty'] ?? '', 'attr') ?>" class="form-control form-control-lg" placeholder="0">
                        <?= $err('qty') ?>
                    </div>
                    <div class="sinv-field">
                        <label>Rate (₹ per Qtl) <span class="opt">optional</span></label>
                        <input type="number" name="rate" id="sinvRate" inputmode="decimal" min="0" step="0.01" value="<?= esc($old['rate'] ?? '', 'attr') ?>" class="form-control form-control-lg" placeholder="0">
                        <?= $err('rate') ?>
                    </div>
                </div>

                <!-- Live stock availability for the chosen product -->
                <div class="sinv-stock" id="sinvStock" hidden>
                    <span class="sinv-stock-avail"><i class="bi bi-box-seam"></i> In stock: <strong id="sinvStockVal">0</strong> <span id="sinvStockUnit">units</span></span>
                    <span class="sinv-stock-after" id="sinvStockAfter"></span>
                    <span class="sinv-stock-warn" id="sinvStockWarn" hidden><i class="bi bi-exclamation-triangle-fill"></i> <span id="sinvStockWarnMsg">Not enough stock</span></span>
                </div>

                <div class="sinv-amount" id="sinvAmount" hidden>Total: <strong>₹ <span id="sinvAmountVal">0</span></strong></div>

                <div class="sinv-field sinv-combo" id="sinvPartyCombo">
                    <label id="sinvPartyLabel">From whom <span class="opt">(supplier)</span></label>
                    <div class="sinv-combo-box">
                        <i class="bi bi-search sinv-combo-ic"></i>
                        <input type="text" name="party" id="sinvParty" value="<?= esc($old['party'] ?? '', 'attr') ?>"
                               class="form-control form-control-lg" placeholder="Search or add an account…"
                               autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="sinvPartyMenu">
                        <button type="button" class="sinv-combo-clear" id="sinvPartyClear" hidden aria-label="Clear"><i class="bi bi-x-lg"></i></button>
                    </div>
                    <div class="sinv-combo-menu" id="sinvPartyMenu" role="listbox" hidden></div>
                    <small class="sinv-ledger-hint"><i class="bi bi-link-45deg"></i> Posts to this account in the <strong>Jama/Naam</strong> ledger when an amount is entered.</small>
                </div>
                <script id="sinvAccountsData" type="application/json"><?= json_encode(array_values($accounts), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>

                <div class="sinv-row">
                    <div class="sinv-field">
                        <label>Payment</label>
                        <?php $pay = $old['payment'] ?? 'cash'; ?>
                        <select name="payment" class="form-select form-select-lg">
                            <?php foreach (['cash' => 'Cash', 'upi' => 'UPI', 'bank' => 'Bank', 'cheque' => 'Cheque', 'credit' => 'Credit / Udhaar'] as $k => $lbl): ?>
                                <option value="<?= $k ?>" <?= $pay === $k ? 'selected' : '' ?>><?= $lbl ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="sinv-field">
                        <label>Note <span class="opt">optional</span></label>
                        <input type="text" name="note" value="<?= esc($old['note'] ?? '', 'attr') ?>" class="form-control form-control-lg" placeholder="Anything to remember">
                    </div>
                </div>

                <button type="submit" class="sinv-save" id="sinvSave"><i class="bi bi-check2-circle me-1"></i>Save Entry</button>
            </form>
        </div>

        <!-- ===== Day book ===== -->
        <div class="sinv-card">
            <div class="sinv-daybar">
                <h3 class="sinv-h mb-0"><i class="bi bi-journal-text me-1"></i><?= $isToday ? "Today" : esc(date('d M Y', strtotime($date))) ?></h3>
                <form method="get" action="<?= site_url('inventory') ?>"><input type="date" name="date" value="<?= esc($date, 'attr') ?>" max="<?= date('Y-m-d') ?>" class="form-control form-control-sm" onchange="this.form.submit()"></form>
            </div>

            <div class="sinv-totals">
                <div class="t in"><span class="l">Purchase (IN)</span><span class="v">+<?= $fmt($tot['in_qty']) ?> <small class="unit-tag">Qtl</small></span><span class="m"><?= $money($tot['in_amt']) ?></span></div>
                <div class="t out"><span class="l">Sale (OUT)</span><span class="v">−<?= $fmt($tot['out_qty']) ?> <small class="unit-tag">Qtl</small></span><span class="m"><?= $money($tot['out_amt']) ?></span></div>
            </div>

            <?php if (empty($rows)): ?>
                <div class="inv-empty-mini"><i class="bi bi-inbox"></i>No entries for this day yet.</div>
            <?php else: ?>
                <ul class="sinv-list">
                    <?php foreach ($rows as $r): $isIn = $r['movement_type'] === 'inward'; ?>
                        <li>
                            <span class="ic <?= $isIn ? 'in' : 'out' ?>"><i class="bi <?= $isIn ? 'bi-arrow-down' : 'bi-arrow-up' ?>"></i></span>
                            <span class="main">
                                <span class="nm"><?= esc($r['product_name']) ?></span>
                                <span class="sub"><?= $isIn ? 'Purchase' : 'Sale' ?><?= ! empty($r['party_name']) ? ' · ' . esc($r['party_name']) : '' ?> · <?= esc(date('H:i', strtotime($r['created_at']))) ?></span>
                            </span>
                            <span class="qty <?= $isIn ? 'in' : 'out' ?>"><?= $isIn ? '+' : '−' ?><?= $fmt($r['bags']) ?> <small class="unit-tag">Qtl</small><?php if ((float) $r['amount'] > 0): ?><small><?= money($r['amount']) ?></small><?php endif; ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var typeWrap = document.getElementById('sinvType');
    if (!typeWrap) { return; }
    var qty = document.getElementById('sinvQty');
    var rate = document.getElementById('sinvRate');
    var amtBox = document.getElementById('sinvAmount');
    var amtVal = document.getElementById('sinvAmountVal');
    var partyLabel = document.getElementById('sinvPartyLabel');
    var save = document.getElementById('sinvSave');
    var prod = document.querySelector('[name="product_id"]');

    function currentType() {
        var c = typeWrap.querySelector('input[name="type"]:checked');
        return c ? c.value : 'in';
    }
    function paint() {
        var t = currentType();
        typeWrap.querySelectorAll('.sinv-type-opt').forEach(function (o) {
            o.classList.toggle('on', o.querySelector('input').checked);
        });
        partyLabel.innerHTML = t === 'out'
            ? 'To whom <span class="opt">(customer)</span>'
            : 'From whom <span class="opt">(supplier)</span>';
        save.className = 'sinv-save ' + (t === 'out' ? 'out' : 'in');
    }
    function calcAmount() {
        var a = (parseFloat(qty.value) || 0) * (parseFloat(rate.value) || 0);
        if (a > 0) { amtVal.textContent = new Intl.NumberFormat('en-IN').format(a.toFixed(2)); amtBox.hidden = false; }
        else { amtBox.hidden = true; }
    }
    // ----- Stock availability check (mainly for Sale) -----
    var stockBox = document.getElementById('sinvStock');
    var stockVal = document.getElementById('sinvStockVal');
    var stockUnit = document.getElementById('sinvStockUnit');
    var stockAfter = document.getElementById('sinvStockAfter');
    var stockWarn = document.getElementById('sinvStockWarn');
    var stockWarnMsg = document.getElementById('sinvStockWarnMsg');
    function nfmt(n) { return new Intl.NumberFormat('en-IN').format(n); }
    function checkStock() {
        if (!stockBox) { return; }
        var opt = prod ? prod.options[prod.selectedIndex] : null;
        if (!opt || !opt.value) { stockBox.hidden = true; save.disabled = false; return; }
        var bags = parseFloat(opt.dataset.stock || '0');
        var unit = 'Qtl';   // stock is standardised to quintals
        var t = currentType();
        var q = parseFloat(qty.value) || 0;

        stockBox.hidden = false;
        stockVal.textContent = nfmt(bags);
        stockUnit.textContent = unit;
        stockBox.classList.remove('over', 'low');
        stockWarn.hidden = true; stockAfter.textContent = ''; save.disabled = false;

        if (t === 'out') {
            if (q > 0) { stockAfter.textContent = '→ after sale: ' + nfmt(bags - q) + ' ' + unit; }
            if (bags <= 0) {
                stockBox.classList.add('over'); stockWarn.hidden = false;
                stockWarnMsg.textContent = 'No stock available to sell.'; save.disabled = true;
            } else if (q > bags) {
                stockBox.classList.add('over'); stockWarn.hidden = false;
                stockWarnMsg.textContent = 'Only ' + nfmt(bags) + ' ' + unit + ' in stock — reduce the quantity.'; save.disabled = true;
            } else if (q > 0 && (bags - q) <= 0) {
                stockBox.classList.add('low');
            }
        } else if (q > 0) {
            stockAfter.textContent = '→ after purchase: ' + nfmt(bags + q) + ' ' + unit;
        }
    }

    typeWrap.querySelectorAll('input[name="type"]').forEach(function (r) { r.addEventListener('change', function () { paint(); checkStock(); }); });
    qty.addEventListener('input', function () { calcAmount(); checkStock(); });
    rate.addEventListener('input', calcAmount);
    // Default the rate from the chosen product + refresh stock availability.
    if (prod) prod.addEventListener('change', function () {
        var opt = prod.options[prod.selectedIndex];
        var r = opt ? parseFloat(opt.dataset.rate || '0') : 0;
        if (r > 0 && !rate.value) { rate.value = r; calcAmount(); }
        checkStock();
    });
    paint(); calcAmount(); checkStock();
})();

/* ===== Party advanced-search combobox ===== */
(function () {
    var input = document.getElementById('sinvParty');
    var menu  = document.getElementById('sinvPartyMenu');
    var clear = document.getElementById('sinvPartyClear');
    var dataEl = document.getElementById('sinvAccountsData');
    if (!input || !menu || !dataEl) { return; }

    var ALL = [];
    try { ALL = JSON.parse(dataEl.textContent) || []; } catch (e) { ALL = []; }
    var active = -1, items = [];

    function esc(s) { return (s == null ? '' : String(s)).replace(/[&<>"']/g, function (c) { return { '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]; }); }
    function hi(name, q) {
        if (!q) { return esc(name); }
        var i = name.toLowerCase().indexOf(q.toLowerCase());
        if (i < 0) { return esc(name); }
        return esc(name.slice(0, i)) + '<mark>' + esc(name.slice(i, i + q.length)) + '</mark>' + esc(name.slice(i + q.length));
    }
    function initial(name) { var t = (name || '?').trim(); return t ? t[0].toUpperCase() : '?'; }

    function filtered(q) {
        q = q.trim().toLowerCase();
        if (!q) { return ALL.slice(0, 50); }
        var starts = [], contains = [];
        ALL.forEach(function (n) {
            var l = n.toLowerCase();
            if (l.startsWith(q)) { starts.push(n); }
            else if (l.indexOf(q) > -1) { contains.push(n); }
        });
        return starts.concat(contains).slice(0, 50);
    }

    function render() {
        var q = input.value.trim();
        var list = filtered(q);
        var exact = ALL.some(function (n) { return n.toLowerCase() === q.toLowerCase(); });
        var html = '';

        // Offer to create a new account when the typed text is new.
        if (q && !exact) {
            html += '<div class="sinv-combo-opt add" data-add="1" data-val="' + esc(q) + '" role="option">' +
                    '<span class="av"><i class="bi bi-plus-lg"></i></span><span class="nm">Add new: <strong>' + esc(q) + '</strong></span></div>';
        }

        html += '<div class="sinv-combo-head"><span>' + (q ? 'Matches' : 'Accounts') + '</span><span>' + list.length + (list.length === 50 ? '+' : '') + '</span></div>';

        if (!list.length && (exact || !q)) {
            html += '<div class="sinv-combo-empty">No accounts yet — type a name to add one.</div>';
        } else {
            list.forEach(function (n) {
                html += '<div class="sinv-combo-opt" data-val="' + esc(n) + '" role="option">' +
                        '<span class="av">' + esc(initial(n)) + '</span><span class="nm">' + hi(n, q) + '</span></div>';
            });
        }
        html += '<div class="sinv-combo-kbd"><span><kbd>&uarr;</kbd><kbd>&darr;</kbd> navigate</span><span><kbd>Enter</kbd> select</span><span><kbd>Esc</kbd> close</span></div>';

        menu.innerHTML = html;
        items = Array.prototype.slice.call(menu.querySelectorAll('.sinv-combo-opt'));
        active = items.length ? 0 : -1;
        paintActive();
    }
    function paintActive() {
        items.forEach(function (el, i) { el.classList.toggle('active', i === active); });
        if (active >= 0 && items[active]) { items[active].scrollIntoView({ block: 'nearest' }); }
    }
    function open() { render(); menu.hidden = false; input.setAttribute('aria-expanded', 'true'); }
    function close() { menu.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; }
    function choose(el) {
        if (!el) { return; }
        input.value = el.getAttribute('data-val');
        toggleClear();
        close();
        input.focus();
    }
    function toggleClear() { clear.hidden = input.value.length === 0; }

    input.addEventListener('focus', open);
    input.addEventListener('input', function () { toggleClear(); open(); });
    input.addEventListener('keydown', function (e) {
        if (menu.hidden && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) { open(); return; }
        if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, items.length - 1); paintActive(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); paintActive(); }
        else if (e.key === 'Enter') { if (!menu.hidden && active >= 0) { e.preventDefault(); choose(items[active]); } }
        else if (e.key === 'Escape') { close(); }
    });
    menu.addEventListener('mousedown', function (e) {
        var opt = e.target.closest('.sinv-combo-opt');
        if (opt) { e.preventDefault(); choose(opt); }
    });
    clear.addEventListener('click', function () { input.value = ''; toggleClear(); open(); input.focus(); });
    document.addEventListener('click', function (e) { if (!e.target.closest('#sinvPartyCombo')) { close(); } });
    toggleClear();
})();
</script>
