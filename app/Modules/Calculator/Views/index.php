<?php /** Sliced content — rendered inside app/Views/layout.php. */ ?>
<div class="row g-4">
    <!-- Calculator -->
    <div class="col-lg-5">
        <div class="card erp-panel">
            <div class="card-header erp-panel-title">
                <span class="panel-icon"><i class="bi bi-calculator"></i></span>
                <h3 class="card-title mb-0">Calculator</h3>
            </div>
            <div class="card-body">
                <input type="text" id="calcExpr" class="form-control text-end mb-1 fs-5" value="" placeholder="0" aria-label="expression" readonly>
                <div class="text-end text-secondary mb-3" id="calcResult" style="min-height:1.4rem;font-weight:600">&nbsp;</div>

                <div class="calc-keys">
                    <div class="row g-2">
                        <div class="col-3"><button type="button" class="btn btn-outline-danger w-100" data-action="clear">C</button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-secondary w-100" data-action="back"><i class="bi bi-backspace"></i></button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-secondary w-100" data-op="(">(</button></div>
                        <div class="col-3"><button type="button" class="btn btn-outline-secondary w-100" data-op=")">)</button></div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="7">7</button></div>
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="8">8</button></div>
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="9">9</button></div>
                        <div class="col-3"><button type="button" class="btn btn-primary w-100" data-op="÷">÷</button></div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="4">4</button></div>
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="5">5</button></div>
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="6">6</button></div>
                        <div class="col-3"><button type="button" class="btn btn-primary w-100" data-op="×">×</button></div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="1">1</button></div>
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="2">2</button></div>
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="3">3</button></div>
                        <div class="col-3"><button type="button" class="btn btn-primary w-100" data-op="−">−</button></div>
                    </div>
                    <div class="row g-2 mt-1">
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num="0">0</button></div>
                        <div class="col-3"><button type="button" class="btn btn-light w-100" data-num=".">.</button></div>
                        <div class="col-3"><button type="button" class="btn btn-success w-100" data-action="equals">=</button></div>
                        <div class="col-3"><button type="button" class="btn btn-primary w-100" data-op="+">+</button></div>
                    </div>
                </div>

                <hr>
                <div class="input-group">
                    <input type="text" id="calcTitle" class="form-control" maxlength="150"
                           placeholder="Title (optional — auto-numbered if blank)">
                    <button type="button" class="btn btn-primary" id="calcSave"><i class="bi bi-save me-1"></i> Save</button>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2">
                    <small class="text-secondary">Blank title auto-assigns an ID like <code>Calculation #12</code>.</small>
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="calcAutoSave">
                        <label class="form-check-label small" for="calcAutoSave">Auto-save on <b>=</b></label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Saved history -->
    <div class="col-lg-7">
        <div class="card erp-panel">
            <div class="card-header erp-panel-title">
                <span class="panel-icon"><i class="bi bi-clock-history"></i></span>
                <h3 class="card-title mb-0">Saved History</h3>
            </div>
            <div class="card-body">
                <div class="erp-tbl-wrap">
                    <table class="erp-tbl auto" id="histTable">
                        <thead>
                            <tr><th class="text-start">Title</th><th class="text-start">Calculation</th><th class="text-start">Result</th><th class="text-start">Saved</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody id="histBody">
                            <?php if (empty($rows)): ?>
                                <tr id="histEmpty"><td colspan="5" class="erp-empty"><i class="bi bi-clock-history"></i><div>No saved calculations yet.</div></td></tr>
                            <?php else: foreach ($rows as $r): ?>
                                <tr data-id="<?= (int) $r['id'] ?>">
                                    <td class="text-start"><?= erp_cell_name((string) ($r['title'] ?: ('Calculation #' . $r['id'])), [
                                        'type' => 'Calculation', 'icon' => 'calculator',
                                        'rows' => [
                                            ['ic' => 'input-cursor-text', 'l' => 'Expression', 'v' => (string) $r['expression']],
                                            ['ic' => 'check2', 'l' => 'Result', 'v' => (string) $r['result']],
                                        ],
                                        'foot' => 'Saved ' . date('d M Y, H:i', strtotime($r['created_at'] ?? 'now')),
                                    ]) ?></td>
                                    <td class="text-start"><code class="calc-expr" role="button" title="Load into calculator"><?= esc($r['expression']) ?></code></td>
                                    <td class="text-start fw-bold"><?= esc($r['result']) ?></td>
                                    <td class="text-start"><span class="erp-muted"><?= esc(date('d M Y, H:i', strtotime($r['created_at'] ?? 'now'))) ?></span></td>
                                    <td class="text-end">
                                        <div class="erp-actions">
                                            <button class="erp-act slate hist-load" title="Load"><i class="bi bi-arrow-up-left-square"></i></button>
                                            <button class="erp-act red hist-del" title="Delete"><i class="bi bi-trash"></i></button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var BASE = window.APP_BASE_URL || '';
    var exprEl = document.getElementById('calcExpr');
    var resEl  = document.getElementById('calcResult');
    var expr = '';

    // Track the CSRF token; it rotates on every request (regenerate = true),
    // so refresh it from each JSON response for the next call.
    var metaCsrf = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = metaCsrf ? metaCsrf.getAttribute('content') : '';
    function csrfHeader() { return { 'X-CSRF-TOKEN': csrfToken }; }
    function refreshCsrf(d) {
        if (d && d.csrf) { csrfToken = d.csrf; if (metaCsrf) { metaCsrf.setAttribute('content', d.csrf); } }
    }
    function render() { exprEl.value = expr || ''; }

    // Convert the display expression to a JS-evaluable one and compute safely.
    function evaluate(display) {
        var js = String(display).replace(/×/g, '*').replace(/÷/g, '/').replace(/−/g, '-');
        if (!/^[0-9+\-*/().\s]*$/.test(js)) { throw new Error('Invalid'); }
        // eslint-disable-next-line no-new-func
        var val = Function('"use strict"; return (' + (js || '0') + ')')();
        if (typeof val !== 'number' || !isFinite(val)) { throw new Error('Invalid'); }
        return Math.round(val * 1e10) / 1e10;
    }
    function tryPreview() {
        try { resEl.textContent = expr ? '= ' + evaluate(expr) : ' '; }
        catch (e) { resEl.textContent = ' '; }
    }

    // Auto-save preference (remembered per browser).
    var autoEl = document.getElementById('calcAutoSave');
    autoEl.checked = localStorage.getItem('erp-calc-autosave') === '1';
    autoEl.addEventListener('change', function () {
        localStorage.setItem('erp-calc-autosave', autoEl.checked ? '1' : '0');
    });
    // Compute the current expression, show it, and auto-save when enabled.
    function doEquals() {
        var original = expr, v;
        try { v = evaluate(expr); }
        catch (err) { erpNotify && erpNotify('error', 'Invalid expression'); return false; }
        resEl.textContent = '= ' + v;
        // Only auto-save a real computation (not a bare number / repeated "=").
        if (autoEl.checked && original.replace(/\s/g, '') !== String(v)) {
            saveCalc(original, String(v), document.getElementById('calcTitle').value.trim(), true);
        }
        expr = String(v);
        return true;
    }

    document.querySelector('.calc-keys').addEventListener('click', function (e) {
        var b = e.target.closest('button'); if (!b) { return; }
        if (b.dataset.num !== undefined) { expr += b.dataset.num; }
        else if (b.dataset.op !== undefined) { expr += b.dataset.op; }
        else if (b.dataset.action === 'clear') { expr = ''; resEl.textContent = ' '; }
        else if (b.dataset.action === 'back') { expr = expr.slice(0, -1); }
        else if (b.dataset.action === 'equals') { if (doEquals()) { render(); } return; }
        render(); tryPreview();
    });

    // Keyboard support.
    document.addEventListener('keydown', function (e) {
        if (document.activeElement === document.getElementById('calcTitle')) { return; }
        if (/[0-9]/.test(e.key)) { expr += e.key; }
        else if (e.key === '.') { expr += '.'; }
        else if (e.key === '+') { expr += '+'; }
        else if (e.key === '-') { expr += '−'; }
        else if (e.key === '*') { expr += '×'; }
        else if (e.key === '/') { expr += '÷'; }
        else if (e.key === '(' || e.key === ')') { expr += e.key; }
        else if (e.key === 'Enter' || e.key === '=') { e.preventDefault(); if (doEquals()) { render(); } return; }
        else if (e.key === 'Backspace') { expr = expr.slice(0, -1); }
        else if (e.key === 'Escape') { expr = ''; resEl.textContent = ' '; }
        else { return; }
        render(); tryPreview();
    });

    // Save.
    document.getElementById('calcSave').addEventListener('click', function () {
        var result;
        try { result = evaluate(expr); } catch (e) { erpNotify && erpNotify('warning', 'Enter a valid calculation first.'); return; }
        saveCalc(expr, String(result), document.getElementById('calcTitle').value.trim(), false);
    });

    // Shared save routine used by the Save button and by auto-save on "=".
    function saveCalc(expression, result, title, isAuto) {
        var form = new URLSearchParams();
        form.set('expression', expression); form.set('result', result); form.set('title', title || '');
        fetch(BASE + '/calculator/save', {
            method: 'POST', credentials: 'same-origin',
            headers: Object.assign({ 'Content-Type': 'application/x-www-form-urlencoded' }, csrfHeader()),
            body: form.toString()
        }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, d: d }; }); })
          .then(function (res) {
              refreshCsrf(res.d);
              if (!res.ok || res.d.status !== 'success') { erpNotify && erpNotify('error', res.d.message || 'Could not save.'); return; }
              prependRow(res.d.entry);
              document.getElementById('calcTitle').value = '';
              erpNotify && erpNotify(isAuto ? 'info' : 'success', (isAuto ? 'Auto-saved: ' : 'Saved: ') + res.d.entry.title);
          }).catch(function () { erpNotify && erpNotify('error', 'Network error while saving.'); });
    }

    function prependRow(en) {
        var empty = document.getElementById('histEmpty'); if (empty) { empty.remove(); }
        var tr = document.createElement('tr');
        tr.setAttribute('data-id', en.id);
        tr.innerHTML =
            '<td class="fw-semibold"></td>' +
            '<td><code class="calc-expr" role="button" title="Load into calculator"></code></td>' +
            '<td class="fw-bold"></td>' +
            '<td class="text-secondary small"></td>' +
            '<td class="text-end">' +
              '<button class="btn btn-sm btn-outline-secondary hist-load" title="Load"><i class="bi bi-arrow-up-left-square"></i></button> ' +
              '<button class="btn btn-sm btn-outline-danger hist-del" title="Delete"><i class="bi bi-trash"></i></button>' +
            '</td>';
        tr.children[0].textContent = en.title;
        tr.children[1].firstChild.textContent = en.expression;
        tr.children[2].textContent = en.result;
        tr.children[3].textContent = en.time;
        document.getElementById('histBody').prepend(tr);
    }

    // History interactions: load + delete.
    document.getElementById('histBody').addEventListener('click', function (e) {
        var loadBtn = e.target.closest('.hist-load, .calc-expr');
        var delBtn  = e.target.closest('.hist-del');
        if (loadBtn) {
            var row = loadBtn.closest('tr');
            expr = row.querySelector('.calc-expr').textContent; render(); tryPreview();
            return;
        }
        if (delBtn) {
            var tr = delBtn.closest('tr'); var id = tr.getAttribute('data-id');
            var run = function () {
                fetch(BASE + '/calculator/delete/' + id, { method: 'POST', credentials: 'same-origin', headers: csrfHeader() })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        refreshCsrf(d);
                        if (d.status === 'success') {
                            tr.remove();
                            if (!document.querySelector('#histBody tr')) {
                                document.getElementById('histBody').innerHTML = '<tr id="histEmpty"><td colspan="5" class="text-center text-secondary py-4">No saved calculations yet.</td></tr>';
                            }
                            erpNotify && erpNotify('info', 'Deleted.');
                        } else { erpNotify && erpNotify('error', d.message || 'Could not delete.'); }
                    }).catch(function () { erpNotify && erpNotify('error', 'Network error.'); });
            };
            if (window.erpConfirm) { erpConfirm({ title: 'Delete this calculation?', icon: 'warning', confirmText: 'Delete', onConfirm: run }); }
            else if (confirm('Delete this calculation?')) { run(); }
        }
    });
})();
</script>
