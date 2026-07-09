<?php
/** Add / Edit Jama-Naam transaction — sectioned, detailed redesign. In layout.php. */
use App\Models\TransactionModel;

$err     = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc(is_array($errors[$k]) ? implode(' ', $errors[$k]) : $errors[$k]) . '</div>' : '';
$action  = $mode === 'edit' ? site_url('transactions/update/' . hid($row['id'])) : site_url('transactions/store');
$type    = $row['type'] ?? old('type', 'jama');
$curMode = $row['payment_mode'] ?? old('payment_mode', 'cash');
$curStat = $row['status'] ?? old('status', 'paid');
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
?>
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <form action="<?= $action ?>" method="post" enctype="multipart/form-data" class="tx-form tx-form-<?= $type ?>">
            <?= csrf_field() ?>
            <div class="card tm-table-card tx-form-card">
                <div class="tm-table-head tx-form-head">
                    <h3 class="tm-table-title"><i class="bi bi-receipt-cutoff"></i> <?= esc($title) ?></h3>
                    <span class="tx-no-badge"><i class="bi bi-hash"></i><?= esc(ltrim($nextNo, '#')) ?></span>
                </div>

                <div class="card-body">
                    <!-- Section 1 · Type -->
                    <section class="tx-section">
                        <div class="tx-section-head">
                            <span class="tx-step">1</span>
                            <div>
                                <div class="tx-section-title">Transaction Type</div>
                                <div class="tx-section-sub">Is this money received (Jama) or money paid (Naam)?</div>
                            </div>
                        </div>
                        <div class="tx-typepick tx-typepick-lg">
                            <label class="tx-typeopt <?= $type === 'jama' ? 'active-jama' : '' ?>">
                                <input type="radio" name="type" value="jama" <?= $type === 'jama' ? 'checked' : '' ?>>
                                <i class="bi bi-arrow-down-circle-fill"></i>
                                <span>Jama <small>Money Received</small></span>
                            </label>
                            <label class="tx-typeopt <?= $type === 'naam' ? 'active-naam' : '' ?>">
                                <input type="radio" name="type" value="naam" <?= $type === 'naam' ? 'checked' : '' ?>>
                                <i class="bi bi-arrow-up-circle-fill"></i>
                                <span>Naam <small>Money Paid</small></span>
                            </label>
                        </div>
                    </section>

                    <!-- Section 2 · Amount -->
                    <section class="tx-section">
                        <div class="tx-section-head">
                            <span class="tx-step">2</span>
                            <div>
                                <div class="tx-section-title">Amount</div>
                                <div class="tx-section-sub">The value of this entry.</div>
                            </div>
                        </div>
                        <div class="tx-amount-wrap">
                            <div class="tx-amount-field">
                                <span class="tx-amount-cur">&#8377;</span>
                                <input type="number" step="0.01" min="0.01" max="9999999999.99" name="amount" class="tx-amount-input" required
                                       inputmode="decimal" value="<?= esc($row['amount'] ?? old('amount')) ?>" placeholder="0.00" autofocus>
                                <span class="tx-amount-tag" data-amount-tag><?= $type === 'naam' ? 'PAID' : 'RECEIVED' ?></span>
                            </div>
                            <?= $err('amount') ?>
                        </div>
                    </section>

                    <!-- Section 3 · Details -->
                    <section class="tx-section">
                        <div class="tx-section-head">
                            <span class="tx-step">3</span>
                            <div>
                                <div class="tx-section-title">Details</div>
                                <div class="tx-section-sub">Who, when and how it was paid.</div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-calendar3"></i></span>
                                    <input type="date" name="txn_date" class="form-control" required value="<?= esc($row['txn_date'] ?? old('txn_date', date('Y-m-d'))) ?>">
                                </div>
                                <?= $err('txn_date') ?>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Party Name <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-person"></i></span>
                                    <input type="text" name="name" class="form-control" required maxlength="191" value="<?= esc($row['name'] ?? old('name')) ?>" placeholder="e.g. Acme Corp / Ramesh Traders">
                                </div>
                                <?= $err('name') ?>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Payment Mode</label>
                                <div class="tx-modepick">
                                    <?php foreach (TransactionModel::MODE_LABELS as $val => $lbl): ?>
                                        <label class="tx-modeopt <?= $curMode === $val ? 'active' : '' ?>">
                                            <input type="radio" name="payment_mode" value="<?= $val ?>" <?= $curMode === $val ? 'checked' : '' ?>>
                                            <i class="bi <?= $modeIcon[$val] ?? 'bi-wallet' ?>"></i> <?= esc($lbl) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Status</label>
                                <div class="tx-statuspick">
                                    <?php foreach ($statusMeta as $val => [$lbl, $ic]): ?>
                                        <label class="tx-statusopt tx-st-<?= $val ?> <?= $curStat === $val ? 'active' : '' ?>">
                                            <input type="radio" name="status" value="<?= $val ?>" <?= $curStat === $val ? 'checked' : '' ?>>
                                            <i class="bi <?= $ic ?>"></i> <?= esc($lbl) ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Section 4 · Notes & reminder -->
                    <section class="tx-section">
                        <div class="tx-section-head">
                            <span class="tx-step">4</span>
                            <div>
                                <div class="tx-section-title">Notes &amp; Reminder</div>
                                <div class="tx-section-sub">Optional remarks and a follow-up alert.</div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Remarks <small class="text-muted fw-normal">(optional)</small></label>
                                <textarea name="notes" class="form-control" rows="2" maxlength="255" placeholder="Description, bill number, purpose…"><?= esc($row['notes'] ?? old('notes')) ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label"><i class="bi bi-alarm me-1"></i>Set a reminder <small class="text-muted fw-normal">(optional)</small></label>
                                <input type="datetime-local" name="remind_at" class="form-control" value="<?= esc(old('remind_at')) ?>">
                                <small class="text-secondary">When due, it appears in Reminders &amp; Notifications.</small>
                            </div>
                        </div>
                    </section>

                    <!-- Section 5 · Attachments -->
                    <section class="tx-section tx-section-last">
                        <div class="tx-section-head">
                            <span class="tx-step">5</span>
                            <div>
                                <div class="tx-section-title">Attachments</div>
                                <div class="tx-section-sub">Photos, camera, audio/video, PDF, Word, Excel… (max 25 MB each)</div>
                            </div>
                        </div>
                        <label class="tx-drop d-block" id="txDrop">
                            <i class="bi bi-cloud-arrow-up fs-3 d-block mb-1"></i>
                            <span>Click to choose files, or drag &amp; drop here</span>
                            <input type="file" name="attachments[]" id="txFiles" multiple class="d-none"
                                   accept="image/*,audio/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
                        </label>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
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
                        <ul id="txFileList" class="list-unstyled small mt-2 mb-0"></ul>

                        <?php if (! empty($attachments)): ?>
                            <div class="tx-att-grid mt-3">
                                <?php foreach ($attachments as $a): ?>
                                    <div class="tx-att">
                                        <div class="tx-att-thumb">
                                            <?php if ($a['kind'] === 'image'): ?>
                                                <img src="<?= site_url('transactions/file/' . hid($a['id']) . '/preview') ?>" alt="">
                                            <?php else: ?>
                                                <i class="bi <?= $kindIcon[$a['kind']] ?? 'bi-file-earmark' ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="tx-att-body"><div class="tx-att-name"><?= esc($a['original_name']) ?></div></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <small class="text-muted d-block mt-1">Existing attachments — manage them on the detail page after saving.</small>
                        <?php endif; ?>
                    </section>
                </div>

                <div class="card-footer d-flex justify-content-between align-items-center">
                    <a href="<?= site_url('transactions/list') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                    <button class="btn btn-primary btn-lg tx-save"><i class="bi bi-save me-1"></i> Save Transaction</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var form = document.querySelector('.tx-form');

    // Jama / Naam picker highlight + tint the amount field + tag.
    function paintType(v) {
        document.querySelectorAll('.tx-typeopt').forEach(function (l) { l.classList.remove('active-jama', 'active-naam'); });
        var opt = document.querySelector('.tx-typeopt input[value="' + v + '"]');
        if (opt) { opt.closest('.tx-typeopt').classList.add(v === 'jama' ? 'active-jama' : 'active-naam'); }
        form.classList.toggle('is-jama', v === 'jama');
        form.classList.toggle('is-naam', v === 'naam');
        var tag = document.querySelector('[data-amount-tag]');
        if (tag) { tag.textContent = v === 'naam' ? 'PAID' : 'RECEIVED'; }
    }
    document.querySelectorAll('.tx-typeopt input').forEach(function (i) {
        i.addEventListener('change', function () { paintType(i.value); });
    });
    var checked = document.querySelector('.tx-typeopt input:checked');
    paintType(checked ? checked.value : 'jama');

    // Payment-mode + status pill highlight.
    ['.tx-modeopt', '.tx-statusopt'].forEach(function (sel) {
        document.querySelectorAll(sel + ' input').forEach(function (i) {
            i.addEventListener('change', function () {
                document.querySelectorAll(sel).forEach(function (l) { l.classList.remove('active'); });
                i.closest(sel).classList.add('active');
            });
        });
    });

    // Attachment picker: preview chosen file names, drag & drop.
    var drop = document.getElementById('txDrop');
    var input = document.getElementById('txFiles');
    var list = document.getElementById('txFileList');
    function human(b) { if (b < 1024) return b + ' B'; if (b < 1048576) return (b / 1024).toFixed(1) + ' KB'; return (b / 1048576).toFixed(1) + ' MB'; }
    function render() {
        list.innerHTML = '';
        document.querySelectorAll('input[type=file][name="attachments[]"]').forEach(function (inp) {
            Array.prototype.forEach.call(inp.files, function (f) {
                var li = document.createElement('li');
                li.className = 'd-flex align-items-center gap-2 py-1';
                li.innerHTML = '<i class="bi bi-paperclip text-secondary"></i><span class="text-truncate">' + f.name + '</span><span class="text-muted ms-auto">' + human(f.size) + '</span>';
                list.appendChild(li);
            });
        });
    }
    input.addEventListener('change', render);
    document.querySelectorAll('.tx-extra').forEach(function (e) { e.addEventListener('change', render); });
    ['dragenter', 'dragover'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('dragover'); }); });
    ['dragleave', 'drop'].forEach(function (ev) { drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('dragover'); }); });
    drop.addEventListener('drop', function (e) { input.files = e.dataTransfer.files; render(); });
})();
</script>
