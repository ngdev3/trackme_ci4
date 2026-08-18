<?php
/**
 * Unified Stock Movement — one dense screen for IN | OUT | TRANSFER | PRODUCTION
 * | ADJUSTMENT. A type selector reveals only the fields each type needs. Compact,
 * keyboard-fast, ERP-style. Every save posts to MovementController::save.
 */
$products   = $products ?? [];
$warehouses = $warehouses ?? [];
$suppliers  = $suppliers ?? [];
$customers  = $customers ?? [];
$recent     = $recent ?? [];

$typeMeta = [
    'in'         => ['Inward', 'bi-box-arrow-in-down', 'success'],
    'out'        => ['Outward', 'bi-box-arrow-up', 'danger'],
    'transfer'   => ['Transfer', 'bi-arrow-left-right', 'primary'],
    'production' => ['Production', 'bi-gear-wide-connected', 'warning'],
    'adjustment' => ['Adjustment', 'bi-sliders', 'secondary'],
];
$dt = static fn ($v) => $v ? date('d-m-y H:i', strtotime((string) $v)) : '—';
?>

<div class="mv-wrap">
    <div class="mv-head">
        <a href="<?= site_url('inventory') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i></a>
        <h3><i class="bi bi-arrow-down-up me-1"></i> Stock Movement</h3>
        <div class="ms-auto d-flex gap-1">
            <a href="<?= site_url('inventory/position') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-clipboard-data"></i> Current Stock</a>
        </div>
    </div>

    <!-- Movement entry card -->
    <form action="<?= site_url('inventory/movement/save') ?>" method="post" autocomplete="off" class="mv-card" id="mvForm">
        <?= csrf_field() ?>
        <input type="hidden" name="_mvtoken" value="<?= esc($formToken ?? '', 'attr') ?>">
        <input type="hidden" name="type" id="mvType" value="in">

        <!-- Type selector -->
        <div class="mv-types">
            <?php foreach ($typeMeta as $key => [$label, $icon, $color]): ?>
                <button type="button" class="mv-type mv-type-<?= $color ?><?= $key === 'in' ? ' active' : '' ?>" data-type="<?= $key ?>">
                    <i class="bi <?= $icon ?>"></i><span><?= $label ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Field row (fields show/hide by type via data-when) -->
        <div class="mv-fields">
            <div class="mv-f" style="--w:2.2">
                <label>Product</label>
                <select name="product_id" id="mvProduct" class="form-select form-select-sm" required>
                    <option value="">Select…</option>
                    <?php foreach ($products as $p): ?>
                        <option value="<?= (int) $p['id'] ?>" data-unit="<?= esc($p['unit'] ?? 'bag', 'attr') ?>"><?= esc($p['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mv-f" style="--w:1.6" data-when="in,out,transfer,production,adjustment">
                <label id="whLabel">Godown</label>
                <select name="warehouse_id" id="mvWarehouse" class="form-select form-select-sm" required>
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= (int) $w['id'] ?>"><?= esc($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mv-f" style="--w:1.6" data-when="transfer" hidden>
                <label>To Godown</label>
                <select name="to_warehouse_id" class="form-select form-select-sm">
                    <?php foreach ($warehouses as $w): ?>
                        <option value="<?= (int) $w['id'] ?>"><?= esc($w['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mv-f" style="--w:1" data-when="adjustment" hidden>
                <label>+/−</label>
                <select name="sign" class="form-select form-select-sm">
                    <option value="+">+ Add</option>
                    <option value="-">− Reduce</option>
                </select>
            </div>

            <div class="mv-f" style="--w:1.3">
                <label>Qty (bags)</label>
                <input type="number" name="bags" id="mvBags" class="form-control form-control-sm" step="0.01" min="0" required>
                <span class="mv-avail" id="mvAvail" data-when="out,transfer,production,adjustment"></span>
            </div>

            <div class="mv-f" style="--w:1.1" data-when="in,out" hidden>
                <label>Rate</label>
                <input type="number" name="rate" class="form-control form-control-sm" step="0.01" min="0" placeholder="0.00">
            </div>

            <div class="mv-f" style="--w:1.8" data-when="in" hidden>
                <label>Supplier</label>
                <input type="text" name="party" class="form-control form-control-sm" list="mvSuppliers" placeholder="optional">
            </div>
            <div class="mv-f" style="--w:1.8" data-when="out" hidden>
                <label>Customer</label>
                <input type="text" name="party" class="form-control form-control-sm" list="mvCustomers" placeholder="optional">
            </div>

            <div class="mv-f" style="--w:1.8" data-when="adjustment" hidden>
                <label>Reason <span class="text-danger">*</span></label>
                <input type="text" name="reason" class="form-control form-control-sm" list="mvReasons" placeholder="required">
            </div>

            <div class="mv-f" style="--w:2">
                <label>Reference / Remarks</label>
                <input type="text" name="reference" class="form-control form-control-sm" placeholder="bill no / vehicle / note">
            </div>
        </div>

        <!-- Production output grid -->
        <div class="mv-prod" data-when="production" hidden>
            <div class="mv-prod-head">
                <span><i class="bi bi-arrow-return-right"></i> Outputs</span>
                <button type="button" class="btn btn-xs btn-outline-primary" id="mvAddOut"><i class="bi bi-plus-lg"></i> Add output</button>
            </div>
            <table class="mv-prod-table">
                <thead><tr><th style="width:60%">Product</th><th style="width:30%">Qty (bags)</th><th></th></tr></thead>
                <tbody id="mvOutRows">
                    <!-- rows injected by JS -->
                </tbody>
                <tfoot><tr>
                    <td class="text-end fw-semibold">Wastage (bags)</td>
                    <td><input type="number" name="wastage_bags" class="form-control form-control-sm" step="0.01" min="0" value="0"></td>
                    <td></td>
                </tr></tfoot>
            </table>
            <div class="mv-prod-hint">Input consumed = Outputs + Wastage. Wastage is recorded as loss (never added to stock).</div>
        </div>

        <div class="mv-actions">
            <span class="mv-hint" id="mvHint"></span>
            <button type="submit" class="btn btn-sm btn-primary" id="mvSave"><i class="bi bi-check-lg me-1"></i> Save Movement</button>
        </div>
    </form>

    <!-- Compact recent-movements ledger -->
    <div class="mv-ledger">
        <div class="mv-ledger-head"><i class="bi bi-clock-history me-1"></i> Recent Movements</div>
        <div class="mv-ledger-scroll">
            <table class="mv-table">
                <thead><tr>
                    <th>Date</th><th>Type</th><th>Ref</th><th>Item</th><th>Godown</th>
                    <th class="text-end">In</th><th class="text-end">Out</th><th class="text-end">Rate</th>
                </tr></thead>
                <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-3">No movements yet.</td></tr>
                <?php else: foreach ($recent as $m):
                    [$tl, $ti, $tc] = $typeMeta[$m['movement_type']] ?? [$m['movement_type'], 'bi-dot', 'secondary'];
                    $in  = (int) $m['direction'] > 0 ? (float) $m['bags'] : null;
                    $out = (int) $m['direction'] < 0 ? (float) $m['bags'] : null;
                ?>
                    <tr>
                        <td class="text-nowrap"><?= esc($dt($m['created_at'] ?? null)) ?></td>
                        <td><span class="mv-badge mv-badge-<?= $tc ?>"><i class="bi <?= $ti ?>"></i><?= esc($tl) ?></span></td>
                        <td class="text-nowrap"><small class="text-muted"><?= esc($m['entry_no']) ?></small></td>
                        <td><?= esc($m['product_name'] ?? '—') ?></td>
                        <td><?= esc($m['warehouse_name'] ?? '—') ?><?php if (! empty($m['to_warehouse_id'])): ?> <i class="bi bi-arrow-right text-muted"></i><?php endif; ?></td>
                        <td class="text-end text-success"><?= $in !== null ? number_format($in, 2) : '' ?></td>
                        <td class="text-end text-danger"><?= $out !== null ? number_format($out, 2) : '' ?></td>
                        <td class="text-end"><?= (float) ($m['rate'] ?? 0) > 0 ? number_format((float) $m['rate'], 2) : '' ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<datalist id="mvSuppliers"><?php foreach ($suppliers as $s): ?><option value="<?= esc($s['name'], 'attr') ?>"></option><?php endforeach; ?></datalist>
<datalist id="mvCustomers"><?php foreach ($customers as $c): ?><option value="<?= esc($c['name'], 'attr') ?>"></option><?php endforeach; ?></datalist>
<datalist id="mvReasons"><option value="moisture_loss"><option value="damaged"><option value="theft"><option value="counting_error"><option value="spillage"></datalist>

<style>
.mv-wrap { max-width: 1180px; margin: 0 auto; font-size: 13px; }
.mv-head { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
.mv-head h3 { margin: 0; font-size: 17px; font-weight: 700; }
.btn-xs { padding: 2px 8px; font-size: 11.5px; line-height: 1.5; }

.mv-card { background: var(--bs-body-bg, #fff); border: 1px solid var(--erp-border, #e4e9f2); border-radius: 10px; padding: 12px; margin-bottom: 14px; }
.mv-types { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 12px; }
.mv-type { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1px solid var(--erp-border, #d8dfea); border-radius: 999px; background: transparent; font-size: 12.5px; font-weight: 600; color: #55617a; cursor: pointer; transition: all .12s; }
.mv-type i { font-size: 14px; }
.mv-type.active { color: #fff; }
.mv-type-success.active { background: #16a34a; border-color: #16a34a; }
.mv-type-danger.active  { background: #dc2626; border-color: #dc2626; }
.mv-type-primary.active { background: #2563eb; border-color: #2563eb; }
.mv-type-warning.active { background: #d97706; border-color: #d97706; }
.mv-type-secondary.active { background: #64748b; border-color: #64748b; }

.mv-fields { display: flex; flex-wrap: wrap; gap: 10px; align-items: flex-end; }
.mv-f { flex: var(--w, 1) 1 130px; min-width: 110px; position: relative; }
.mv-f[hidden] { display: none; }
.mv-f label { display: block; font-size: 11px; font-weight: 600; color: #6b7688; margin-bottom: 3px; }
.mv-avail { position: absolute; right: 2px; top: 0; font-size: 10.5px; font-weight: 700; color: #16a34a; }
.mv-avail.low { color: #d97706; } .mv-avail.zero { color: #dc2626; }

.mv-prod { margin-top: 12px; border-top: 1px dashed var(--erp-border, #e4e9f2); padding-top: 10px; }
.mv-prod-head { display: flex; align-items: center; justify-content: space-between; font-size: 12.5px; font-weight: 700; margin-bottom: 6px; }
.mv-prod-table { width: 100%; border-collapse: collapse; }
.mv-prod-table th { font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: #8a95a8; text-align: left; padding: 2px 6px; }
.mv-prod-table td { padding: 3px 6px; }
.mv-prod-hint { font-size: 11px; color: #8a95a8; margin-top: 6px; }

.mv-actions { display: flex; align-items: center; gap: 10px; margin-top: 12px; }
.mv-hint { font-size: 12px; color: #8a95a8; }
.mv-actions .btn { margin-left: auto; }

.mv-ledger { background: var(--bs-body-bg, #fff); border: 1px solid var(--erp-border, #e4e9f2); border-radius: 10px; overflow: hidden; }
.mv-ledger-head { padding: 8px 12px; font-size: 12.5px; font-weight: 700; border-bottom: 1px solid var(--erp-border, #eef1f6); }
.mv-ledger-scroll { max-height: 420px; overflow: auto; }
.mv-table { width: 100%; border-collapse: collapse; font-size: 12px; }
.mv-table thead th { position: sticky; top: 0; background: #f6f8fb; z-index: 1; font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px; color: #7a8698; padding: 7px 10px; text-align: left; border-bottom: 1px solid var(--erp-border, #e4e9f2); }
.mv-table tbody td { padding: 6px 10px; border-bottom: 1px solid #f2f5f9; }
.mv-table tbody tr:hover { background: #f9fbfd; }
.mv-badge { display: inline-flex; align-items: center; gap: 3px; padding: 1px 7px; border-radius: 999px; font-size: 10.5px; font-weight: 700; }
.mv-badge i { font-size: 11px; }
.mv-badge-success { background: #e7f6ec; color: #157347; }
.mv-badge-danger  { background: #fdecec; color: #b02a37; }
.mv-badge-primary { background: #e8effd; color: #1d4ed8; }
.mv-badge-warning { background: #fdf1e3; color: #b45309; }
.mv-badge-secondary { background: #eef1f6; color: #55617a; }
</style>

<script>
(function () {
    var form = document.getElementById('mvForm');
    if (!form) return;
    var typeInput = document.getElementById('mvType');
    var product = document.getElementById('mvProduct');
    var warehouse = document.getElementById('mvWarehouse');
    var bags = document.getElementById('mvBags');
    var availEl = document.getElementById('mvAvail');
    var whLabel = document.getElementById('whLabel');
    var hint = document.getElementById('mvHint');
    var avail = null;

    function currentType() { return typeInput.value; }

    // Show only the fields relevant to the chosen type.
    function applyType(t) {
        typeInput.value = t;
        document.querySelectorAll('.mv-type').forEach(function (b) { b.classList.toggle('active', b.dataset.type === t); });
        document.querySelectorAll('[data-when]').forEach(function (el) {
            var ok = el.getAttribute('data-when').split(',').indexOf(t) !== -1;
            if (el.classList.contains('mv-avail')) { el.style.display = ok ? '' : 'none'; return; }
            el.hidden = !ok;
        });
        whLabel.textContent = (t === 'transfer') ? 'From Godown' : 'Godown';
        // Production requires at least one output row.
        if (t === 'production' && !document.querySelector('#mvOutRows tr')) addOutRow();
        refreshAvail();
    }

    // Live available stock beside qty (for out/transfer/production/adjustment).
    function refreshAvail() {
        availEl.textContent = '';
        var needy = ['out', 'transfer', 'production', 'adjustment'].indexOf(currentType()) !== -1;
        if (!needy || !product.value || !warehouse.value) { avail = null; return; }
        fetch('<?= site_url('inventory/movement/available') ?>?product_id=' + product.value + '&warehouse_id=' + warehouse.value, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (d) {
                avail = parseFloat(d.available || 0);
                availEl.textContent = 'Avail: ' + avail.toLocaleString('en-IN', { maximumFractionDigits: 2 });
                availEl.className = 'mv-avail' + (avail <= 0 ? ' zero' : (avail < 10 ? ' low' : ''));
            }).catch(function () {});
    }

    // Production outputs grid.
    function addOutRow() {
        var opts = product.innerHTML; // reuse the product option list
        var tr = document.createElement('tr');
        tr.innerHTML = '<td><select name="out_product[]" class="form-select form-select-sm">' + opts + '</select></td>' +
            '<td><input type="number" name="out_bags[]" class="form-control form-control-sm" step="0.01" min="0"></td>' +
            '<td><button type="button" class="btn btn-xs btn-outline-danger mv-del-out"><i class="bi bi-x"></i></button></td>';
        document.getElementById('mvOutRows').appendChild(tr);
    }
    document.getElementById('mvAddOut').addEventListener('click', addOutRow);
    document.getElementById('mvOutRows').addEventListener('click', function (e) {
        var b = e.target.closest('.mv-del-out'); if (b) b.closest('tr').remove();
    });

    document.querySelectorAll('.mv-type').forEach(function (b) {
        b.addEventListener('click', function () { applyType(b.dataset.type); });
    });
    product.addEventListener('change', refreshAvail);
    warehouse.addEventListener('change', refreshAvail);
    bags.addEventListener('input', function () {
        hint.textContent = '';
        if (avail !== null && parseFloat(bags.value || 0) > avail && currentType() !== 'adjustment') {
            hint.textContent = '⚠ Exceeds available (' + avail + ')';
        }
    });

    // Guard against a double-click resubmit (server also de-dupes via token).
    form.addEventListener('submit', function () {
        var btn = document.getElementById('mvSave');
        setTimeout(function () { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving…'; }, 0);
    });

    applyType('in');
})();
</script>
