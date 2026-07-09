<?php
/**
 * Task 5 — Voice-Based Entry. The worker taps the mic and says a sentence like
 * "Received 100 bags of potatoes from Sharma Traders". The transcript is parsed
 * on the server, the form below is pre-filled, and the worker only reviews and
 * confirms before saving. Falls back to typing the sentence when the browser
 * has no speech recognition.
 */
$parties = array_merge($suppliers ?? [], $customers ?? []);
?>
<div class="inv-form-wrap">
    <div class="inv-form-card">
        <div class="inv-form-head voice">
            <a href="<?= site_url('inventory') ?>" class="inv-back"><i class="bi bi-arrow-left"></i></a>
            <div><h2><i class="bi bi-mic me-2"></i>Voice Entry</h2><p>Just speak — we’ll fill the form. You only review &amp; confirm.</p></div>
        </div>

        <!-- ===== Speak ===== -->
        <div class="inv-voice-stage" id="voiceStage">
            <button type="button" class="inv-voice-mic" id="voiceMic" aria-label="Tap and speak">
                <i class="bi bi-mic-fill"></i>
                <span class="inv-voice-ring"></span>
            </button>
            <p class="inv-voice-hint" id="voiceHint">Tap the mic and speak.</p>

            <div class="inv-voice-examples">
                <span>Try saying:</span>
                <em>“Received 100 bags of potatoes from Sharma Traders”</em>
                <em>“Dispatched 50 bags of rice to Gupta Traders”</em>
            </div>

            <div class="inv-voice-manual">
                <label for="voiceText">…or type what you would say</label>
                <div class="inv-voice-manual-row">
                    <input type="text" id="voiceText" class="form-control form-control-lg"
                           placeholder="e.g. Received 100 bags of potatoes from Sharma Traders">
                    <button type="button" class="btn inv-btn-in" id="voiceParseBtn"><i class="bi bi-magic me-1"></i>Read it</button>
                </div>
            </div>
            <div class="inv-voice-error" id="voiceError" hidden></div>
        </div>

        <!-- ===== Review & confirm (hidden until parsed) ===== -->
        <form action="<?= site_url('inventory/inward') ?>" method="post" enctype="multipart/form-data"
              autocomplete="off" class="inv-form inv-voice-review" id="voiceForm" hidden>
            <?= csrf_field() ?>
            <input type="hidden" name="entry_source" value="voice">

            <div class="inv-voice-heard" id="voiceHeard"></div>

            <div class="inv-voice-confidence" id="voiceConf"></div>

            <!-- Direction toggle -->
            <div class="inv-field">
                <label>What happened? <span class="req">*</span></label>
                <div class="inv-dir-toggle" id="dirToggle">
                    <button type="button" class="inv-dir in"  data-dir="inward"><i class="bi bi-box-arrow-in-down"></i> Received (In)</button>
                    <button type="button" class="inv-dir out" data-dir="outward"><i class="bi bi-box-arrow-up"></i> Dispatched (Out)</button>
                </div>
            </div>

            <div class="inv-field" id="partyField">
                <label id="partyLabel">Supplier / Farmer <span class="opt">(optional)</span></label>
                <input type="text" id="partyInput" class="form-control form-control-lg" list="voicePartyList" placeholder="Type or pick a name">
                <input type="hidden" name="supplier_name" id="supplierField">
                <input type="hidden" name="customer_name" id="customerField">
                <datalist id="voicePartyList">
                    <?php foreach ($parties as $pt): ?><option value="<?= esc($pt['name'], 'attr') ?>"></option><?php endforeach; ?>
                </datalist>
                <div class="inv-voice-newparty" id="partyNew" hidden><i class="bi bi-plus-circle"></i> New party — will be added.</div>
            </div>

            <div class="inv-field">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" id="voiceProduct" class="form-select form-select-lg" required>
                    <option value="">— Choose product —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= esc($p['id']) ?>" data-avg="<?= esc($p['avg_weight'], 'attr') ?>"><?= esc($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="inv-row2">
                <div class="inv-field">
                    <label>Number of Bags <span class="req">*</span></label>
                    <div class="inv-stepper">
                        <button type="button" class="inv-step" data-step="-1">−</button>
                        <input type="number" name="bags" id="voiceBags" class="form-control form-control-lg text-center" inputmode="numeric" min="1" step="1" required placeholder="0">
                        <button type="button" class="inv-step" data-step="1">+</button>
                    </div>
                </div>
                <div class="inv-field">
                    <label>Approx. Weight (kg) <span class="opt">(auto)</span></label>
                    <input type="number" name="weight" id="voiceWeight" class="form-control form-control-lg" inputmode="decimal" min="0" step="0.01" placeholder="Auto from bags">
                </div>
            </div>

            <div class="inv-field">
                <label>Godown / Warehouse <span class="req">*</span></label>
                <select name="warehouse_id" id="voiceWarehouse" class="form-select form-select-lg" required>
                    <option value="">— Choose godown —</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= esc($w['id']) ?>"><?= esc($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="inv-avail" id="voiceAvail" hidden><i class="bi bi-boxes"></i> <span id="voiceAvailVal">0</span> bags available</div>
            </div>

            <div class="inv-field">
                <label>Proof photo / file <span class="opt">(optional)</span></label>
                <label class="inv-photo">
                    <input type="file" name="attachments[]" id="voicePhoto" accept="image/*,application/pdf,video/*,audio/*" multiple hidden>
                    <i class="bi bi-camera"></i><span id="voicePhotoLabel">Add photo, bill or challan</span>
                </label>
            </div>

            <div class="inv-field">
                <label>Notes <span class="opt">(optional)</span></label>
                <input type="text" name="notes" id="voiceNotes" class="form-control" placeholder="Anything to remember">
            </div>

            <div class="inv-voice-actions">
                <button type="button" class="btn btn-light border btn-lg" id="voiceRedo"><i class="bi bi-mic me-1"></i>Speak again</button>
                <button type="submit" class="inv-save in" id="voiceSave"><i class="bi bi-check2-circle me-2"></i>Confirm &amp; Save</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var BASE = window.ERP_BASE || '<?= rtrim(site_url(), '/') ?>/';
    var URL_IN  = '<?= site_url('inventory/inward') ?>';
    var URL_OUT = '<?= site_url('inventory/outward') ?>';

    var stage = document.getElementById('voiceStage');
    var form  = document.getElementById('voiceForm');
    var mic   = document.getElementById('voiceMic');
    var hint  = document.getElementById('voiceHint');
    var textInput = document.getElementById('voiceText');
    var errBox = document.getElementById('voiceError');

    var elProduct = document.getElementById('voiceProduct');
    var elBags    = document.getElementById('voiceBags');
    var elWeight  = document.getElementById('voiceWeight');
    var elWh      = document.getElementById('voiceWarehouse');
    var elParty   = document.getElementById('partyInput');
    var elSupplier= document.getElementById('supplierField');
    var elCustomer= document.getElementById('customerField');
    var elHeard   = document.getElementById('voiceHeard');
    var elConf    = document.getElementById('voiceConf');
    var elPartyLabel = document.getElementById('partyLabel');
    var elPartyNew   = document.getElementById('partyNew');
    var elAvail   = document.getElementById('voiceAvail');
    var elAvailVal= document.getElementById('voiceAvailVal');
    var saveBtn   = document.getElementById('voiceSave');
    var dirBtns   = Array.prototype.slice.call(document.querySelectorAll('.inv-dir'));
    var direction = 'inward';

    // ---------- helpers ----------
    function showError(msg) { errBox.textContent = msg; errBox.hidden = false; }
    function clearError() { errBox.hidden = true; }

    function setDirection(dir) {
        direction = (dir === 'outward') ? 'outward' : 'inward';
        dirBtns.forEach(function (b) { b.classList.toggle('active', b.dataset.dir === direction); });
        form.setAttribute('action', direction === 'outward' ? URL_OUT : URL_IN);
        elPartyLabel.innerHTML = (direction === 'outward' ? 'Customer' : 'Supplier / Farmer') + ' <span class="opt">(optional)</span>';
        saveBtn.className = 'inv-save ' + (direction === 'outward' ? 'out' : 'in');
        showAvail();
    }
    dirBtns.forEach(function (b) { b.addEventListener('click', function () { setDirection(b.dataset.dir); }); });

    function autoWeight() {
        var opt = elProduct.options[elProduct.selectedIndex];
        var avg = opt ? parseFloat(opt.dataset.avg || '0') : 0;
        if (avg > 0 && elBags.value) { elWeight.value = (avg * parseFloat(elBags.value)).toFixed(2); }
    }
    document.querySelectorAll('.inv-step').forEach(function (b) {
        b.addEventListener('click', function () {
            var v = parseInt(elBags.value || '0', 10) + parseInt(b.dataset.step, 10);
            elBags.value = v < 0 ? 0 : v; autoWeight();
        });
    });
    elBags.addEventListener('input', autoWeight);
    elProduct.addEventListener('change', function () { autoWeight(); showAvail(); });

    // Live availability (fetched with the parse; shown for the chosen product+godown).
    var AVAIL = null;
    function showAvail() {
        if (direction === 'outward' && AVAIL !== null && elProduct.value && elWh.value) {
            elAvailVal.textContent = new Intl.NumberFormat('en-IN').format(AVAIL);
            elAvail.hidden = false;
            elAvail.classList.toggle('zero', AVAIL <= 0);
        } else { elAvail.hidden = true; }
    }
    elWh.addEventListener('change', showAvail);

    var photo = document.getElementById('voicePhoto');
    if (photo) photo.addEventListener('change', function () {
        var n = photo.files.length;
        document.getElementById('voicePhotoLabel').textContent = n ? (n + ' file' + (n > 1 ? 's' : '') + ' selected') : 'Add photo, bill or challan';
    });

    // ---------- fill the review form from a parse ----------
    function applyParse(p) {
        elHeard.innerHTML = '<i class="bi bi-quote"></i> ' + escapeHtml(p.transcript || '');

        setDirection(p.direction || 'inward');
        if (p.product_id)   { elProduct.value = String(p.product_id); }
        if (p.warehouse_id) { elWh.value = String(p.warehouse_id); }
        elBags.value   = p.bags ? p.bags : '';
        elWeight.value = p.weight ? p.weight : '';
        autoWeight();

        elParty.value = p.party_name || '';
        elPartyNew.hidden = !p.party_is_new;

        AVAIL = (p.available === undefined ? null : p.available);
        showAvail();

        // Confidence + what to check.
        var miss = p.missing || [];
        var label = { direction: 'what happened', product: 'product', bags: 'number of bags', warehouse: 'godown' };
        if (miss.length) {
            elConf.className = 'inv-voice-confidence warn';
            elConf.innerHTML = '<i class="bi bi-exclamation-triangle"></i> Please check: ' +
                miss.map(function (k) { return label[k] || k; }).join(', ') + '.';
        } else if (p.confidence === 'high') {
            elConf.className = 'inv-voice-confidence ok';
            elConf.innerHTML = '<i class="bi bi-check-circle"></i> Got it — please review and save.';
        } else {
            elConf.className = 'inv-voice-confidence';
            elConf.innerHTML = '<i class="bi bi-eye"></i> Please review the details below.';
        }

        stage.hidden = true;
        form.hidden = false;
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function escapeHtml(s) {
        return String(s).replace(/[&<>"']/g, function (c) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
        });
    }

    // ---------- send transcript to the server parser ----------
    function parseTranscript(transcript) {
        transcript = (transcript || '').trim();
        if (!transcript) { showError('Nothing was heard. Please try again.'); return; }
        clearError();
        hint.textContent = 'Understanding…';

        // Refresh CSRF first so a long-open page never 403s, then post.
        fetch(BASE + 'csrf-token', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (tok) {
                var body = new URLSearchParams();
                body.append('transcript', transcript);
                if (tok && tok.token) { body.append(tok.name || 'csrf_test_name', tok.token); }
                return fetch('<?= site_url('inventory/voice/parse') ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body.toString()
                });
            })
            .then(function (r) { return r.json(); })
            .then(function (j) {
                hint.textContent = 'Tap the mic and speak.';
                if (j.status === 'ok' && j.parsed) { applyParse(j.parsed); }
                else { showError(j.message || 'Could not understand that. Please try again.'); }
            })
            .catch(function () { hint.textContent = 'Tap the mic and speak.'; showError('Something went wrong. Please try again.'); });
    }

    document.getElementById('voiceParseBtn').addEventListener('click', function () { parseTranscript(textInput.value); });
    textInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); parseTranscript(textInput.value); } });

    // ---------- speech recognition ----------
    var SR = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SR) {
        mic.classList.add('disabled');
        hint.textContent = 'Voice not supported here — type the sentence below.';
        mic.addEventListener('click', function () { textInput.focus(); });
    } else {
        mic.addEventListener('click', function () {
            clearError();
            var rec = new SR();
            rec.lang = document.documentElement.lang || 'en-IN';
            rec.interimResults = true; rec.maxAlternatives = 1;
            mic.classList.add('listening');
            hint.textContent = 'Listening… speak now.';
            var finalText = '';
            rec.onresult = function (e) {
                var interim = '';
                for (var i = e.resultIndex; i < e.results.length; i++) {
                    if (e.results[i].isFinal) { finalText += e.results[i][0].transcript; }
                    else { interim += e.results[i][0].transcript; }
                }
                textInput.value = (finalText || interim).trim();
            };
            rec.onerror = function (ev) {
                mic.classList.remove('listening');
                hint.textContent = 'Tap the mic and speak.';
                if (ev.error === 'not-allowed' || ev.error === 'service-not-allowed') {
                    showError('Microphone permission is blocked. Allow it, or type the sentence below.');
                }
            };
            rec.onend = function () {
                mic.classList.remove('listening');
                hint.textContent = 'Tap the mic and speak.';
                if (textInput.value.trim()) { parseTranscript(textInput.value); }
            };
            rec.start();
        });
    }

    // Speak again → back to the mic stage.
    document.getElementById('voiceRedo').addEventListener('click', function () {
        form.hidden = true; stage.hidden = false; textInput.value = '';
        stage.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    // On submit, map the party name into the correct field and refresh CSRF.
    form.addEventListener('submit', function (e) {
        elSupplier.value = (direction === 'inward')  ? elParty.value.trim() : '';
        elCustomer.value = (direction === 'outward') ? elParty.value.trim() : '';
        e.preventDefault();
        if (window.erpFreshSubmit) { window.erpFreshSubmit(form); } else { form.submit(); }
    });
})();
</script>
