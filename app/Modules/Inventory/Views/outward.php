<?php
/** Stock Outward — minimal worker form. Mirrors Inward; shows live availability. */
$err = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
$short = session()->getFlashdata('short_available');
?>
<div class="inv-form-wrap">
    <div class="inv-form-card">
        <div class="inv-form-head out">
            <a href="<?= site_url('inventory') ?>" class="inv-back"><i class="bi bi-arrow-left"></i></a>
            <div><h2><i class="bi bi-box-arrow-up me-2"></i>Stock Outward</h2><p>Goods dispatched — inventory reduces automatically.</p></div>
        </div>

        <form action="<?= site_url('inventory/outward') ?>" method="post" enctype="multipart/form-data" autocomplete="off" class="inv-form" id="outwardForm">
            <?= csrf_field() ?>

            <div class="inv-field">
                <label>Customer <span class="opt">(optional)</span></label>
                <input type="text" name="customer_name" class="form-control form-control-lg" list="customerList"
                       value="<?= esc(old('customer_name')) ?>" placeholder="Type or pick a name">
                <datalist id="customerList">
                    <?php foreach ($customers as $c): ?><option value="<?= esc($c['name'], 'attr') ?>"></option><?php endforeach; ?>
                </datalist>
            </div>

            <div class="inv-field">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" id="prodSel" class="form-select form-select-lg" required>
                    <option value="">— Choose product —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= esc($p['id']) ?>" data-avg="<?= esc($p['avg_weight'], 'attr') ?>" <?= (int) old('product_id') === (int) $p['id'] ? 'selected' : '' ?>>
                            <?= esc($p['name']) ?>
                        </option>
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
                <div class="inv-avail" id="availNote" hidden><i class="bi bi-boxes"></i> <span id="availVal">0</span> bags available</div>
            </div>

            <div class="inv-row2">
                <div class="inv-field">
                    <label>Number of Bags <span class="req">*</span></label>
                    <div class="inv-stepper">
                        <button type="button" class="inv-step" data-step="-1">−</button>
                        <input type="number" name="bags" id="bagsInput" class="form-control form-control-lg text-center" inputmode="numeric"
                               min="1" step="1" required value="<?= esc(old('bags')) ?>" placeholder="0">
                        <button type="button" class="inv-step" data-step="1">+</button>
                    </div>
                    <?= $err('bags') ?>
                </div>
                <div class="inv-field">
                    <label>Approx. Weight (kg) <span class="opt">(auto)</span></label>
                    <input type="number" name="weight" id="weightInput" class="form-control form-control-lg" inputmode="decimal"
                           min="0" step="0.01" value="<?= esc(old('weight')) ?>" placeholder="Auto from bags">
                </div>
            </div>

            <div class="inv-field">
                <label>Vehicle Number <span class="opt">(optional)</span></label>
                <input type="text" name="vehicle_no" class="form-control form-control-lg text-uppercase" value="<?= esc(old('vehicle_no')) ?>" placeholder="e.g. PB10AB1234">
            </div>

            <div class="inv-field">
                <label>Proof — photo / bill / challan / voice note <span class="opt">(optional)</span></label>
                <label class="inv-photo">
                    <input type="file" name="attachments[]" accept="image/*,application/pdf,video/*,audio/*" capture="environment" multiple hidden id="photoInput">
                    <i class="bi bi-paperclip"></i><span id="photoLabel">Add photos, bills, challans, videos or voice notes</span>
                </label>
            </div>

            <div class="inv-field">
                <label>Notes <span class="opt">(optional)</span></label>
                <input type="text" name="notes" class="form-control" value="<?= esc(old('notes')) ?>" placeholder="Anything to remember">
            </div>

            <?php if ($short !== null): ?>
                <label class="inv-allow">
                    <input type="checkbox" name="allow_negative" value="1"> Dispatch anyway (allow negative stock)
                </label>
            <?php endif; ?>

            <button type="submit" class="inv-save out"><i class="bi bi-check2-circle me-2"></i>Save Outward</button>
        </form>
    </div>
</div>

<script>
(function () {
    var AVAIL = <?= json_encode($avail ?? [], JSON_UNESCAPED_SLASHES) ?>;
    var bags = document.getElementById('bagsInput');
    var weight = document.getElementById('weightInput');
    var prod = document.getElementById('prodSel');
    var wh = document.getElementById('whSel');
    var note = document.getElementById('availNote');
    var val = document.getElementById('availVal');

    document.querySelectorAll('.inv-step').forEach(function (b) {
        b.addEventListener('click', function () {
            var v = parseInt(bags.value || '0', 10) + parseInt(b.dataset.step, 10);
            bags.value = v < 0 ? 0 : v; autoWeight();
        });
    });
    function autoWeight() {
        var opt = prod.options[prod.selectedIndex];
        var avg = opt ? parseFloat(opt.dataset.avg || '0') : 0;
        if (avg > 0 && bags.value) { weight.value = (avg * parseFloat(bags.value)).toFixed(2); }
    }
    function showAvail() {
        if (prod.value && wh.value) {
            var a = AVAIL[prod.value + '_' + wh.value] || 0;
            val.textContent = new Intl.NumberFormat('en-IN').format(a);
            note.hidden = false;
            note.classList.toggle('zero', a <= 0);
        } else { note.hidden = true; }
    }
    bags.addEventListener('input', autoWeight);
    prod.addEventListener('change', function () { autoWeight(); showAvail(); });
    wh.addEventListener('change', showAvail);
    showAvail();
    var photo = document.getElementById('photoInput');
    if (photo) photo.addEventListener('change', function () {
        var n = photo.files.length;
        document.getElementById('photoLabel').textContent = n
            ? (n + ' file' + (n > 1 ? 's' : '') + ' selected')
            : 'Add photos, bills, challans, videos or voice notes';
    });
})();
</script>
