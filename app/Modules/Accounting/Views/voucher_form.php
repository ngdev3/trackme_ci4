<?php
/** Double-entry voucher form with dynamic lines + live balance. Rendered inside layout.php. */
$ledgerOptions = '';
foreach ($ledgers as $id => $name) {
    $ledgerOptions .= '<option value="' . (int) $id . '">' . esc($name) . '</option>';
}
?>
<div class="card">
    <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-receipt-cutoff me-1"></i> New Voucher</h3></div>
    <form action="<?= site_url('accounting/vouchers/store') ?>" method="post" id="voucherForm">
        <?= csrf_field() ?>
        <div class="card-body">
            <?php if (! empty($errors)): ?>
                <div class="alert alert-danger"><?= esc(is_array($errors) ? implode(' ', array_map(fn ($e) => is_array($e) ? implode(' ', $e) : $e, $errors)) : $errors) ?></div>
            <?php endif; ?>

            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label">Voucher Type <span class="text-danger">*</span></label>
                    <select name="voucher_type" class="form-select" required>
                        <?php foreach (\App\Models\VoucherModel::TYPES as $t): ?>
                            <option value="<?= $t ?>" <?= old('voucher_type') === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Date <span class="text-danger">*</span></label>
                    <input type="date" name="date" class="form-control" required value="<?= esc(old('date', date('Y-m-d'))) ?>">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Narration</label>
                    <input type="text" name="narration" class="form-control" value="<?= esc(old('narration')) ?>" placeholder="Optional">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-2" id="lineTable">
                    <thead class="table-light"><tr><th style="width:50%">Ledger</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th style="width:40px"></th></tr></thead>
                    <tbody id="lineBody">
                        <!-- rows injected by JS -->
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td class="text-end">Total</td>
                            <td class="text-end" id="totalDr">0.00</td>
                            <td class="text-end" id="totalCr">0.00</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end">
                                <span id="balanceMsg" class="badge text-bg-secondary">Balance: 0.00</span>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-2" id="addLine"><i class="bi bi-plus-lg"></i> Add Line</button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between">
            <a href="<?= site_url('accounting/vouchers') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            <button class="btn btn-primary" type="submit" id="saveBtn"><i class="bi bi-save me-1"></i> Save Voucher</button>
        </div>
    </form>
</div>

<template id="lineTpl">
    <tr>
        <td><select name="ledger_id[]" class="form-select form-select-sm"><option value="">— Select ledger —</option><?= $ledgerOptions ?></select></td>
        <td><input type="number" step="0.01" min="0" name="dr_amount[]" class="form-control form-control-sm text-end dr" value="0"></td>
        <td><input type="number" step="0.01" min="0" name="cr_amount[]" class="form-control form-control-sm text-end cr" value="0"></td>
        <td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger rm"><i class="bi bi-x"></i></button></td>
    </tr>
</template>

<script nonce="{csp-script-nonce}">
(function () {
    var body = document.getElementById('lineBody');
    var tpl  = document.getElementById('lineTpl');

    function addLine() { body.appendChild(tpl.content.cloneNode(true)); recalc(); }
    function recalc() {
        var dr = 0, cr = 0;
        body.querySelectorAll('tr').forEach(function (row) {
            dr += parseFloat(row.querySelector('.dr').value) || 0;
            cr += parseFloat(row.querySelector('.cr').value) || 0;
        });
        document.getElementById('totalDr').textContent = dr.toFixed(2);
        document.getElementById('totalCr').textContent = cr.toFixed(2);
        var diff = Math.round((dr - cr) * 100) / 100;
        var msg = document.getElementById('balanceMsg');
        var balanced = diff === 0 && dr > 0;
        msg.textContent = balanced ? 'Balanced ✓' : 'Difference: ' + diff.toFixed(2);
        msg.className = 'badge ' + (balanced ? 'text-bg-success' : 'text-bg-warning');
        document.getElementById('saveBtn').disabled = !balanced;
    }
    body.addEventListener('input', recalc);
    body.addEventListener('click', function (e) {
        if (e.target.closest('.rm')) { e.target.closest('tr').remove(); recalc(); }
    });
    document.getElementById('addLine').addEventListener('click', addLine);
    addLine(); addLine(); // start with two lines
})();
</script>
