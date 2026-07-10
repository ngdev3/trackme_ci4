<?php
/** Add / Edit Jama-Naam transaction. Rendered inside layout.php. */
use App\Models\TransactionModel;

$err       = static function ($k) use ($errors) {
    if (! isset($errors[$k])) {
        return '';
    }
    $message = is_array($errors[$k]) ? implode(' ', $errors[$k]) : $errors[$k];
    return '<div class="invalid-feedback d-block">' . esc($message) . '</div>';
};
$action    = $mode === 'edit' ? site_url('transactions/update/' . hid($row['id'])) : site_url('transactions/store');
$type      = isset($row['type']) ? $row['type'] : old('type', isset($presetType) ? $presetType : 'jama');
$curMode   = isset($row['payment_mode']) ? $row['payment_mode'] : old('payment_mode', 'cash');
$curStat   = isset($row['status']) ? $row['status'] : old('status', 'paid');
$curPtype  = isset($row['party_type']) ? $row['party_type'] : old('party_type', '');
$amount    = isset($row['amount']) ? $row['amount'] : old('amount');
$partyName = isset($row['name']) ? $row['name'] : old('name');
$txnDate   = isset($row['txn_date']) ? $row['txn_date'] : old('txn_date', date('Y-m-d'));
$notes     = isset($row['notes']) ? $row['notes'] : old('notes');
$partyTypes = isset($partyTypes) ? $partyTypes : TransactionModel::PARTY_TYPES;
$isNaam     = $type === 'naam';
$modeIcon = [
    'cash' => 'bi-cash-coin', 'bank' => 'bi-bank', 'upi' => 'bi-phone',
    'cheque' => 'bi-receipt', 'card' => 'bi-credit-card', 'other' => 'bi-three-dots',
];
$statusMeta = [
    'paid' => ['Paid', 'bi-check-circle'], 'pending' => ['Pending', 'bi-hourglass-split'],
    'overdue' => ['Overdue', 'bi-exclamation-circle'], 'draft' => ['Draft', 'bi-pencil-square'],
    'cancelled' => ['Cancelled', 'bi-x-circle'],
];
$kindIcon = ['image' => 'bi-file-image', 'pdf' => 'bi-file-earmark-pdf', 'audio' => 'bi-file-music', 'video' => 'bi-file-play', 'doc' => 'bi-file-earmark-word', 'sheet' => 'bi-file-earmark-excel', 'file' => 'bi-file-earmark'];
$modeLabels = TransactionModel::MODE_LABELS;
$curModeLabel = isset($modeLabels[$curMode]) ? $modeLabels[$curMode] : ucfirst($curMode);
$curStatusLabel = isset($statusMeta[$curStat][0]) ? $statusMeta[$curStat][0] : ucfirst($curStat);
?>

<form action="<?= $action ?>" method="post" enctype="multipart/form-data" class="tx-entry tx-form <?= $isNaam ? 'is-naam' : 'is-jama' ?>">
    <?= csrf_field() ?>

    <div class="tx-entry-shell">
        <header class="tx-entry-hero">
            <div class="tx-entry-title">
                <span class="tx-entry-kicker"><i class="bi bi-journal-plus"></i> Hisaab Kitaab Vahi</span>
                <h1><?= esc($title) ?></h1>
                <p><?= $isNaam ? 'Record money paid out with clean details for your Rokadh Parcha.' : 'Record money received with clean details for your Rokadh Parcha.' ?></p>
            </div>
            <div class="tx-entry-actions">
                <a href="<?= site_url('transactions/list') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Ledger</a>
                <button class="btn btn-primary tx-save"><i class="bi bi-check2-circle"></i> Save Entry</button>
            </div>
        </header>

        <div class="tx-entry-grid">
            <main class="tx-entry-main">
                <section class="tx-panel tx-priority-panel">
                    <div class="tx-flow-row">
                        <label class="tx-flow-option <?= $type === 'jama' ? 'active-jama' : '' ?>">
                            <input type="radio" name="type" value="jama" <?= $type === 'jama' ? 'checked' : '' ?>>
                            <span class="tx-flow-icon"><i class="bi bi-arrow-down-left-circle"></i></span>
                            <span><strong>Jama</strong><small>Money received</small></span>
                        </label>
                        <label class="tx-flow-option <?= $type === 'naam' ? 'active-naam' : '' ?>">
                            <input type="radio" name="type" value="naam" <?= $type === 'naam' ? 'checked' : '' ?>>
                            <span class="tx-flow-icon"><i class="bi bi-arrow-up-right-circle"></i></span>
                            <span><strong>Naam</strong><small>Money paid</small></span>
                        </label>
                    </div>

                    <div class="tx-money-line">
                        <label for="txAmount">Amount</label>
                        <div class="tx-money-input">
                            <span>&#8377;</span>
                            <input id="txAmount" type="number" step="0.01" min="0.01" max="9999999999.99" name="amount" required
                                   inputmode="decimal" value="<?= esc($amount) ?>" placeholder="0.00" autofocus data-review-amount>
                            <em data-amount-tag><?= $isNaam ? 'PAID' : 'RECEIVED' ?></em>
                        </div>
                        <?= $err('amount') ?>
                    </div>
                </section>

                <section class="tx-panel">
                    <div class="tx-panel-head">
                        <span><i class="bi bi-person-lines-fill"></i></span>
                        <div>
                            <h2>Party & Date</h2>
                            <p>Pick the account, classify it, and set the ledger date.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-7">
                            <label class="form-label" for="txPartyName">Party Name <span class="text-danger">*</span></label>
                            <div class="tx-combo" id="txPartyCombo" data-search-url="<?= site_url('transactions/accounts/search') ?>">
                                <div class="input-group tx-input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input id="txPartyName" type="text" name="name" class="form-control" required maxlength="191"
                                           value="<?= esc($partyName) ?>" placeholder="Search an account or type a new one..."
                                           autocomplete="off" role="combobox" aria-expanded="false" aria-controls="txPartyMenu" aria-autocomplete="list" data-review-party>
                                </div>
                                <div class="tx-combo-menu" id="txPartyMenu" role="listbox" hidden></div>
                            </div>
                            <small class="text-secondary">Pick an existing account (shows its balance) or type a new name.</small>
                            <?= $err('name') ?>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="txDate">Date <span class="text-danger">*</span></label>
                            <div class="input-group tx-input-group">
                                <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                <input id="txDate" type="date" name="txn_date" class="form-control" required value="<?= esc($txnDate) ?>" data-review-date>
                            </div>
                            <?= $err('txn_date') ?>
                        </div>
                        <div class="col-12">
                            <label class="form-label" for="txPartyType">Party Type <small class="text-muted fw-normal">(optional)</small></label>
                            <div class="tx-chips tx-party-chips" data-tx-typechips>
                                <?php foreach ($partyTypes as $pt): ?>
                                    <button type="button" class="tx-chip-btn <?= strcasecmp((string) $curPtype, (string) $pt) === 0 ? 'active' : '' ?>" data-val="<?= esc($pt, 'attr') ?>"><?= esc($pt) ?></button>
                                <?php endforeach; ?>
                            </div>
                            <input type="text" name="party_type" id="txPartyType" class="form-control" maxlength="32"
                                   value="<?= esc($curPtype, 'attr') ?>" placeholder="Farmer, Firm, Trader..." autocomplete="off" data-review-party-type>
                        </div>
                    </div>
                </section>

                <section class="tx-panel">
                    <div class="tx-panel-head">
                        <span><i class="bi bi-wallet2"></i></span>
                        <div>
                            <h2>Payment & Status</h2>
                            <p>Mode and collection state for follow-up clarity.</p>
                        </div>
                    </div>
                    <div class="tx-modepick">
                        <?php foreach ($modeLabels as $val => $lbl): ?>
                            <label class="tx-modeopt <?= $curMode === $val ? 'active' : '' ?>">
                                <input type="radio" name="payment_mode" value="<?= $val ?>" <?= $curMode === $val ? 'checked' : '' ?> data-label="<?= esc($lbl, 'attr') ?>">
                                <i class="bi <?= isset($modeIcon[$val]) ? $modeIcon[$val] : 'bi-wallet' ?>"></i> <?= esc($lbl) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="tx-statuspick mt-3">
                        <?php foreach ($statusMeta as $val => $meta): $lbl = $meta[0]; $ic = $meta[1]; ?>
                            <label class="tx-statusopt tx-st-<?= $val ?> <?= $curStat === $val ? 'active' : '' ?>">
                                <input type="radio" name="status" value="<?= $val ?>" <?= $curStat === $val ? 'checked' : '' ?> data-label="<?= esc($lbl, 'attr') ?>">
                                <i class="bi <?= $ic ?>"></i> <?= esc($lbl) ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </section>

                <?php if ($mode === 'edit'): ?>
                <section class="tx-panel">
                    <div class="tx-panel-head">
                        <span><i class="bi bi-sticky"></i></span>
                        <div>
                            <h2>Notes & Reminder</h2>
                            <p>Add context now so the entry is searchable later.</p>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-lg-7">
                            <label class="form-label" for="txNotes">Remarks <small class="text-muted fw-normal">(optional)</small></label>
                            <textarea id="txNotes" name="notes" class="form-control" rows="4" maxlength="255" placeholder="Description, bill number, purpose..."><?= esc($notes) ?></textarea>
                        </div>
                        <div class="col-lg-5">
                            <label class="form-label" for="txReminder"><i class="bi bi-alarm me-1"></i>Set a reminder <small class="text-muted fw-normal">(optional)</small></label>
                            <input id="txReminder" type="datetime-local" name="remind_at" class="form-control" value="<?= esc(old('remind_at')) ?>">
                            <div class="tx-soft-note">When due, it appears in Reminders and Notifications.</div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>

                <section class="tx-panel">
                    <div class="tx-panel-head">
                        <span><i class="bi bi-paperclip"></i></span>
                        <div>
                            <h2>Attachments</h2>
                            <p>Photos, camera, audio/video, PDF, Word, Excel. Max 25 MB each.</p>
                        </div>
                    </div>
                    <label class="tx-drop" id="txDrop">
                        <i class="bi bi-cloud-arrow-up"></i>
                        <span><strong>Drop files here</strong><small>or click to choose from your device</small></span>
                        <input type="file" name="attachments[]" id="txFiles" multiple class="d-none"
                               accept="image/*,audio/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
                    </label>
                    <div class="tx-capture-row">
                        <label class="btn btn-sm btn-outline-secondary mb-0"><i class="bi bi-camera"></i> Camera
                            <input type="file" name="attachments[]" accept="image/*" capture="environment" class="d-none tx-extra">
                        </label>
                        <label class="btn btn-sm btn-outline-secondary mb-0"><i class="bi bi-mic"></i> Audio
                            <input type="file" name="attachments[]" accept="audio/*" capture class="d-none tx-extra">
                        </label>
                        <label class="btn btn-sm btn-outline-secondary mb-0"><i class="bi bi-camera-video"></i> Video
                            <input type="file" name="attachments[]" accept="video/*" capture="environment" class="d-none tx-extra">
                        </label>
                    </div>
                    <ul id="txFileList" class="tx-file-list"></ul>

                    <?php if (! empty($attachments)): ?>
                        <div class="tx-att-grid mt-3">
                            <?php foreach ($attachments as $a): ?>
                                <div class="tx-att">
                                    <div class="tx-att-thumb">
                                        <?php if ($a['kind'] === 'image'): ?>
                                            <img src="<?= site_url('transactions/file/' . hid($a['id']) . '/preview') ?>" alt="">
                                        <?php else: ?>
                                            <i class="bi <?= isset($kindIcon[$a['kind']]) ? $kindIcon[$a['kind']] : 'bi-file-earmark' ?>"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="tx-att-body"><div class="tx-att-name"><?= esc($a['original_name']) ?></div></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted d-block mt-2">Existing attachments can be managed on the detail page after saving.</small>
                    <?php endif; ?>
                </section>
            </main>

            <aside class="tx-entry-side">
                <div class="tx-review">
                    <div class="tx-review-top">
                        <span class="tx-review-badge" data-review-type><?= $isNaam ? 'Naam' : 'Jama' ?></span>
                        <span class="tx-no-badge"><i class="bi bi-hash"></i><?= esc(ltrim($nextNo, '#')) ?></span>
                    </div>
                    <div class="tx-review-amount" data-review-amount-text><?= $amount !== null && $amount !== '' ? '&#8377; ' . esc(number_format((float) $amount, 2)) : '&#8377; 0.00' ?></div>
                    <div class="tx-review-party" data-review-party-text><?= $partyName !== null && $partyName !== '' ? esc($partyName) : 'Party name' ?></div>
                    <dl class="tx-review-list">
                        <div><dt>Date</dt><dd data-review-date-text><?= esc(date('d M Y', strtotime($txnDate))) ?></dd></div>
                        <div><dt>Mode</dt><dd data-review-mode-text><?= esc($curModeLabel) ?></dd></div>
                        <div><dt>Status</dt><dd data-review-status-text><?= esc($curStatusLabel) ?></dd></div>
                        <div><dt>Party Type</dt><dd data-review-party-type-text><?= esc($curPtype ?: 'Not set') ?></dd></div>
                    </dl>
                    <button class="btn btn-primary w-100 tx-save"><i class="bi bi-check2-circle"></i> Save Entry</button>
                    <a href="<?= site_url('transactions/report') ?>" class="btn btn-outline-secondary w-100 mt-2"><i class="bi bi-journal-text"></i> Rokadh Parcha</a>
                </div>
            </aside>
        </div>
    </div>
</form>

<script>
(function () {
    var form = document.querySelector('.tx-entry');
    if (!form) return;

    function money(v) {
        var n = parseFloat(v || 0);
        if (!isFinite(n)) n = 0;
        return String.fromCharCode(8377) + ' ' + n.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function prettyDate(v) {
        if (!v) return 'Not set';
        var d = new Date(v + 'T00:00:00');
        return isNaN(d.getTime()) ? v : d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    function text(el, fallback) {
        return el && el.value.trim() ? el.value.trim() : fallback;
    }
    function setText(sel, value) {
        var el = document.querySelector(sel);
        if (el) el.textContent = value;
    }
    function paintType(v) {
        document.querySelectorAll('.tx-flow-option').forEach(function (l) { l.classList.remove('active-jama', 'active-naam'); });
        var opt = document.querySelector('.tx-flow-option input[value="' + v + '"]');
        if (opt) opt.closest('.tx-flow-option').classList.add(v === 'jama' ? 'active-jama' : 'active-naam');
        form.classList.toggle('is-jama', v === 'jama');
        form.classList.toggle('is-naam', v === 'naam');
        setText('[data-amount-tag]', v === 'naam' ? 'PAID' : 'RECEIVED');
        setText('[data-review-type]', v === 'naam' ? 'Naam' : 'Jama');
    }
    function refreshReview() {
        var amount = document.querySelector('[data-review-amount]');
        var party = document.querySelector('[data-review-party]');
        var date = document.querySelector('[data-review-date]');
        var partyType = document.querySelector('[data-review-party-type]');
        var mode = document.querySelector('input[name="payment_mode"]:checked');
        var status = document.querySelector('input[name="status"]:checked');
        setText('[data-review-amount-text]', money(amount ? amount.value : 0));
        setText('[data-review-party-text]', text(party, 'Party name'));
        setText('[data-review-date-text]', prettyDate(date ? date.value : ''));
        setText('[data-review-party-type-text]', text(partyType, 'Not set'));
        setText('[data-review-mode-text]', mode ? mode.getAttribute('data-label') : 'Cash');
        setText('[data-review-status-text]', status ? status.getAttribute('data-label') : 'Paid');
    }

    document.querySelectorAll('.tx-flow-option input').forEach(function (i) {
        i.addEventListener('change', function () { paintType(i.value); refreshReview(); });
    });
    ['input', 'change'].forEach(function (ev) {
        document.querySelectorAll('[data-review-amount], [data-review-party], [data-review-date], [data-review-party-type]').forEach(function (el) {
            el.addEventListener(ev, refreshReview);
        });
    });

    ['.tx-modeopt', '.tx-statusopt'].forEach(function (sel) {
        document.querySelectorAll(sel + ' input').forEach(function (i) {
            i.addEventListener('change', function () {
                document.querySelectorAll(sel).forEach(function (l) { l.classList.remove('active'); });
                i.closest(sel).classList.add('active');
                refreshReview();
            });
        });
    });

    var ptInput = document.getElementById('txPartyType');
    var ptBtns  = document.querySelectorAll('[data-tx-typechips] .tx-chip-btn');
    function syncPartyChips() {
        var v = ptInput.value.trim().toLowerCase();
        ptBtns.forEach(function (b) { b.classList.toggle('active', b.getAttribute('data-val').toLowerCase() === v); });
        refreshReview();
    }
    ptBtns.forEach(function (b) {
        b.addEventListener('click', function () {
            var v = b.getAttribute('data-val');
            ptInput.value = ptInput.value.trim().toLowerCase() === v.toLowerCase() ? '' : v;
            syncPartyChips();
        });
    });
    ptInput.addEventListener('input', syncPartyChips);

    var drop = document.getElementById('txDrop');
    var input = document.getElementById('txFiles');
    var list = document.getElementById('txFileList');
    function human(b) { if (b < 1024) return b + ' B'; if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'; return (b / 1048576).toFixed(1) + ' MB'; }
    function render() {
        list.innerHTML = '';
        document.querySelectorAll('input[type=file][name="attachments[]"]').forEach(function (inp) {
            Array.prototype.forEach.call(inp.files, function (f) {
                var li = document.createElement('li');
                li.innerHTML = '<i class="bi bi-paperclip"></i><span>' + f.name + '</span><em>' + human(f.size) + '</em>';
                list.appendChild(li);
            });
        });
    }
    input.addEventListener('change', render);
    document.querySelectorAll('.tx-extra').forEach(function (e) { e.addEventListener('change', render); });
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('dragover'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('dragover'); }); });
    drop.addEventListener('drop', function (e) { input.files = e.dataTransfer.files; render(); });

    var checked = document.querySelector('.tx-flow-option input:checked');
    paintType(checked ? checked.value : 'jama');
    refreshReview();
})();

/* Account name — server-side type-ahead. The page never embeds the account list,
   so this scales to thousands of parties; matches are fetched as the user types. */
(function () {
    var combo = document.getElementById('txPartyCombo');
    if (!combo) { return; }
    var input = document.getElementById('txPartyName');
    var menu  = document.getElementById('txPartyMenu');
    var url   = combo.getAttribute('data-search-url');
    var items = [], shown = [], active = -1, timer = null, lastQ = null, seq = 0, filter = 'all';

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }
    function closeMenu() { menu.hidden = true; input.setAttribute('aria-expanded', 'false'); active = -1; }
    function money(v) {
        var n = parseFloat(v || 0);
        if (!isFinite(n)) { n = 0; }
        var sign = n < 0 ? '-' : '';
        return sign + String.fromCharCode(8377) + ' ' + Math.abs(n).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function accountInitial(name) {
        return (name || '?').trim().charAt(0).toUpperCase() || '?';
    }
    function filterLabel() {
        if (filter === 'jama') { return 'Jama balance'; }
        if (filter === 'naam') { return 'Naam balance'; }
        return 'All accounts';
    }
    function filteredItems() {
        return items.filter(function (p) {
            return filter === 'all' || (p.tone || 'neutral') === filter;
        });
    }

    function paint() {
        if (!items.length) {
            menu.innerHTML = '<div class="tx-combo-empty">' + (input.value.trim() ? 'No match — this will be saved as a new account.' : 'Start typing to search your accounts.') + '</div>';
            menu.hidden = false; input.setAttribute('aria-expanded', 'true');
            return;
        }
        menu.innerHTML = items.map(function (p, i) {
            return '<div class="tx-combo-item' + (i === active ? ' active' : '') + '" role="option" data-i="' + i + '">'
                + '<span class="tx-combo-name"></span><span class="tx-combo-desc"></span></div>';
        }).join('');
        items.forEach(function (p, i) {
            var el = menu.querySelector('.tx-combo-item[data-i="' + i + '"]');
            el.querySelector('.tx-combo-name').textContent = p.name;
            el.querySelector('.tx-combo-desc').textContent = p.desc;
        });
        menu.hidden = false; input.setAttribute('aria-expanded', 'true');
    }

    function paint() {
        shown = filteredItems();
        var toolbar = '<div class="tx-combo-tools" role="group" aria-label="Account filters">'
            + '<button type="button" class="tx-combo-filter' + (filter === 'all' ? ' active' : '') + '" data-filter="all">All</button>'
            + '<button type="button" class="tx-combo-filter tx-combo-filter-jama' + (filter === 'jama' ? ' active' : '') + '" data-filter="jama">Jama</button>'
            + '<button type="button" class="tx-combo-filter tx-combo-filter-naam' + (filter === 'naam' ? ' active' : '') + '" data-filter="naam">Naam</button>'
            + '</div>';

        if (!shown.length) {
            var msg = items.length
                ? 'No ' + filterLabel().toLowerCase() + ' found in these search results.'
                : (input.value.trim() ? 'No match. Press Enter or save to create a new account.' : 'Start typing to search your accounts.');
            menu.innerHTML = toolbar + '<div class="tx-combo-empty"><i class="bi bi-search"></i><span></span></div>';
            menu.querySelector('.tx-combo-empty span').textContent = msg;
            menu.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            return;
        }

        menu.innerHTML = toolbar + shown.map(function (p, i) {
            var tone = p.tone || 'neutral';
            return '<div class="tx-combo-item tx-combo-item-' + tone + (i === active ? ' active is-active' : '') + '" role="option" data-i="' + i + '">'
                + '<span class="tx-combo-avatar"></span>'
                + '<span class="tx-combo-text"><span class="tx-combo-name"></span><span class="tx-combo-meta"></span></span>'
                + '<span class="tx-combo-net"></span>'
                + '</div>';
        }).join('');

        shown.forEach(function (p, i) {
            var el = menu.querySelector('.tx-combo-item[data-i="' + i + '"]');
            var tone = p.tone || 'neutral';
            el.querySelector('.tx-combo-avatar').textContent = accountInitial(p.name);
            el.querySelector('.tx-combo-name').textContent = p.name || '';
            el.querySelector('.tx-combo-meta').textContent = p.desc || '';
            el.querySelector('.tx-combo-net').textContent = money(p.net);
            el.querySelector('.tx-combo-net').classList.add(tone === 'naam' ? 'tx-amt-naam' : (tone === 'jama' ? 'tx-amt-jama' : 'tx-bal'));
        });
        menu.hidden = false;
        input.setAttribute('aria-expanded', 'true');
    }

    function fetchList() {
        var q = input.value.trim();
        if (q === lastQ) { paint(); return; }
        lastQ = q;
        var mine = ++seq;
        fetch(url + '?q=' + encodeURIComponent(q), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (mine !== seq) { return; } // ignore out-of-order responses
                items = (res && res.items) || [];
                active = -1;
                paint();
            })
            .catch(function () { if (mine === seq) { items = []; paint(); } });
    }

    function debouncedFetch() { clearTimeout(timer); timer = setTimeout(fetchList, 180); }

    function pick(i) {
        if (shown[i]) {
            input.value = shown[i].name;
            input.dispatchEvent(new Event('change', { bubbles: true })); // refresh review panel
        }
        closeMenu();
    }

    input.addEventListener('input', debouncedFetch);
    input.addEventListener('focus', function () { if (menu.hidden) { fetchList(); } });
    menu.addEventListener('mousedown', function (e) {
        var filterBtn = e.target.closest('[data-filter]');
        if (filterBtn) {
            e.preventDefault();
            filter = filterBtn.getAttribute('data-filter') || 'all';
            active = -1;
            paint();
            return;
        }
        var it = e.target.closest('.tx-combo-item');
        if (it) { e.preventDefault(); pick(+it.getAttribute('data-i')); }
    });
    input.addEventListener('keydown', function (e) {
        if (menu.hidden || !shown.length) { return; }
        if (e.key === 'ArrowDown') { e.preventDefault(); active = Math.min(active + 1, shown.length - 1); paint(); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); active = Math.max(active - 1, 0); paint(); }
        else if (e.key === 'Enter' && active >= 0) { e.preventDefault(); pick(active); }
        else if (e.key === 'Escape') { closeMenu(); }
    });
    document.addEventListener('click', function (e) { if (!e.target.closest('#txPartyCombo')) { closeMenu(); } });
})();
</script>
