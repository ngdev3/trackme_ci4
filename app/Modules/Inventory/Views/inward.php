<?php
/** Stock Inward — minimal worker form. Big inputs, dropdowns, one save button. */
$err = fn ($k) => isset($errors[$k]) ? '<div class="invalid-feedback d-block">' . esc($errors[$k]) . '</div>' : '';
?>
<div class="inv-form-wrap">
    <div class="inv-form-card">
        <div class="inv-form-head in">
            <a href="<?= site_url('inventory') ?>" class="inv-back"><i class="bi bi-arrow-left"></i></a>
            <div><h2><i class="bi bi-box-arrow-in-down me-2"></i>Stock Inward</h2><p>Goods received — Lot & entry number are auto-generated.</p></div>
        </div>

        <form action="<?= site_url('inventory/inward') ?>" method="post" enctype="multipart/form-data" autocomplete="off" class="inv-form" id="inwardForm">
            <?= csrf_field() ?>

            <div class="inv-field">
                <label>Supplier / Farmer <span class="opt">(optional)</span></label>
                <input type="text" name="supplier_name" class="form-control form-control-lg" list="supplierList"
                       value="<?= esc(old('supplier_name')) ?>" placeholder="Type or pick a name">
                <datalist id="supplierList">
                    <?php foreach ($suppliers as $s): ?><option value="<?= esc($s['name'], 'attr') ?>"></option><?php endforeach; ?>
                </datalist>
            </div>

            <div class="inv-field">
                <label>Product <span class="req">*</span></label>
                <select name="product_id" class="form-select form-select-lg" required>
                    <option value="">— Choose product —</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= esc($p['id']) ?>" data-avg="<?= esc($p['avg_weight'], 'attr') ?>" <?= (int) old('product_id') === (int) $p['id'] ? 'selected' : '' ?>>
                            <?= esc($p['name']) ?><?= $p['avg_weight'] > 0 ? ' (' . esc($p['avg_weight']) . ' kg/bag)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= $err('product_id') ?>
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

            <div class="inv-row2">
                <div class="inv-field">
                    <label>Godown / Warehouse <span class="req">*</span></label>
                    <select name="warehouse_id" class="form-select form-select-lg" required>
                        <option value="">— Choose godown —</option>
                        <?php foreach ($warehouses as $w): ?>
                            <option value="<?= esc($w['id']) ?>" <?= (int) old('warehouse_id') === (int) $w['id'] ? 'selected' : '' ?>><?= esc($w['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?= $err('warehouse_id') ?>
                </div>
                <div class="inv-field">
                    <label>Rack / Location <span class="opt">(optional)</span></label>
                    <input type="text" name="rack" class="form-control form-control-lg" value="<?= esc(old('rack')) ?>" placeholder="e.g. A-12">
                </div>
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

            <button type="submit" class="inv-save in"><i class="bi bi-check2-circle me-2"></i>Save Inward</button>
        </form>
    </div>
</div>

<script>
(function () {
    var bags = document.getElementById('bagsInput');
    var weight = document.getElementById('weightInput');
    var product = document.querySelector('[name="product_id"]');
    document.querySelectorAll('.inv-step').forEach(function (b) {
        b.addEventListener('click', function () {
            var v = parseInt(bags.value || '0', 10) + parseInt(b.dataset.step, 10);
            bags.value = v < 0 ? 0 : v; autoWeight();
        });
    });
    function autoWeight() {
        var opt = product.options[product.selectedIndex];
        var avg = opt ? parseFloat(opt.dataset.avg || '0') : 0;
        if (avg > 0 && bags.value) { weight.value = (avg * parseFloat(bags.value)).toFixed(2); }
    }
    bags.addEventListener('input', autoWeight);
    product.addEventListener('change', autoWeight);
    var photo = document.getElementById('photoInput');
    if (photo) photo.addEventListener('change', function () {
        var n = photo.files.length;
        document.getElementById('photoLabel').textContent = n
            ? (n + ' file' + (n > 1 ? 's' : '') + ' selected')
            : 'Add photos, bills, challans, videos or voice notes';
    });
})();
</script>
