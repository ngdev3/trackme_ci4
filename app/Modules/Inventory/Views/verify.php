<?php
/** Physical Stock Verification — system vs physical, auto difference, reason. */
$err = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
?>
<div class="inv-form-wrap">
    <div class="inv-form-card">
        <div class="inv-form-head chk">
            <a href="<?= site_url('inventory') ?>" class="inv-back"><i class="bi bi-arrow-left"></i></a>
            <div><h2><i class="bi bi-clipboard-check me-2"></i>Verify Stock</h2><p>Count the physical bags — we’ll do the maths.</p></div>
        </div>

        <form action="<?= site_url('inventory/verify') ?>" method="post" autocomplete="off" class="inv-form" id="verifyForm">
            <?= csrf_field() ?>

            <div class="inv-field">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" id="prodSel" class="form-select form-select-lg" required>
                    <option value="">— Choose product —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= esc($p['id']) ?>" <?= (int) old('product_id') === (int) $p['id'] ? 'selected' : '' ?>><?= esc($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $err('product_id') ?>
            </div>
            <div class="inv-field">
                <label>Godown / Warehouse <span class="req">*</span></label>
                <select name="warehouse_id" id="whSel" class="form-select form-select-lg" required>
                    <option value="">— Choose godown —</option>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= esc($w['id']) ?>" <?= (int) old('warehouse_id') === (int) $w['id'] ? 'selected' : '' ?>><?= esc($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $err('warehouse_id') ?>
            </div>

            <!-- System vs Physical vs Difference -->
            <div class="inv-verify-grid">
                <div class="inv-verify-cell sys">
                    <small>System Stock</small>
                    <strong id="sysVal">—</strong><span>bags</span>
                </div>
                <div class="inv-verify-cell phy">
                    <small>Physical Count <span class="req">*</span></small>
                    <input type="number" name="physical_bags" id="physInput" inputmode="numeric" min="0" step="1"
                           required value="<?= esc(old('physical_bags')) ?>" placeholder="0">
                </div>
                <div class="inv-verify-cell diff" id="diffCell">
                    <small>Difference</small>
                    <strong id="diffVal">0</strong><span>bags</span>
                </div>
            </div>
            <?= $err('physical_bags') ?>

            <div class="inv-field" id="reasonWrap" style="display:none;">
                <label>Reason for difference <span class="req">*</span></label>
                <select name="reason" class="form-select form-select-lg">
                    <option value="">— Select reason —</option>
                    <?php foreach ($reasons as $key => $label): ?>
                        <option value="<?= esc($key) ?>" <?= old('reason') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                    <?php endforeach; ?>
                </select>
                <?= $err('reason') ?>
            </div>

            <div class="inv-field">
                <label>Note <span class="opt">(optional)</span></label>
                <input type="text" name="note" class="form-control" value="<?= esc(old('note')) ?>" placeholder="Anything to add for the reviewer">
            </div>

            <div class="inv-verify-hint">
                <i class="bi bi-info-circle"></i>
                <?php if (! empty($canApprove)): ?>
                    You’ll submit a correction request. As owner/admin you can approve it on the next screen.
                <?php else: ?>
                    Your correction request goes to the owner/admin. Stock is <strong>not</strong> changed until they approve.
                <?php endif; ?>
            </div>

            <button type="submit" class="inv-save chk"><i class="bi bi-send-check me-2"></i>Submit for Approval</button>
        </form>
    </div>
</div>

<script>
(function () {
    var SYS = <?= json_encode($sys ?? [], JSON_UNESCAPED_SLASHES) ?>;
    var prod = document.getElementById('prodSel');
    var wh = document.getElementById('whSel');
    var phys = document.getElementById('physInput');
    var sysVal = document.getElementById('sysVal');
    var diffVal = document.getElementById('diffVal');
    var diffCell = document.getElementById('diffCell');
    var reasonWrap = document.getElementById('reasonWrap');

    function systemBags() {
        if (prod.value && wh.value) { return SYS[prod.value + '_' + wh.value] || 0; }
        return null;
    }
    function recalc() {
        var s = systemBags();
        sysVal.textContent = s === null ? '—' : new Intl.NumberFormat('en-IN').format(s);
        if (s === null || phys.value === '') { diffVal.textContent = '0'; diffCell.className = 'inv-verify-cell diff'; reasonWrap.style.display = 'none'; return; }
        var d = parseFloat(phys.value) - s;
        diffVal.textContent = (d > 0 ? '+' : '') + new Intl.NumberFormat('en-IN').format(d);
        diffCell.className = 'inv-verify-cell diff ' + (d === 0 ? 'ok' : (d > 0 ? 'up' : 'down'));
        reasonWrap.style.display = d === 0 ? 'none' : '';
    }
    prod.addEventListener('change', recalc);
    wh.addEventListener('change', recalc);
    phys.addEventListener('input', recalc);
    recalc();
})();
</script>
