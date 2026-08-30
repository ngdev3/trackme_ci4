<?php
/** New Sale / Purchase bill entry. In layout.php. */
$type     = ($type ?? 'sale') === 'purchase' ? 'purchase' : 'sale';
$products = $products ?? [];
$isSale   = $type === 'sale';
// Compact catalogue for the client-side line editor.
$catalogue = array_map(static fn ($p) => [
    'id'   => (int) $p['id'],
    'name' => $p['name'],
    'sale' => (float) $p['sale_price'],
    'pur'  => (float) $p['purchase_price'],
    'tax'  => (float) $p['tax_rate'],
    'unit' => $p['unit'],
    'stock'=> (float) $p['current_stock'],
], $products);
?>
<form method="post" action="<?= site_url('invoices/store') ?>" id="invForm">
    <?= csrf_field() ?>
    <input type="hidden" name="type" value="<?= esc($type, 'attr') ?>">

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">
                <i class="bi <?= $isSale ? 'bi-cart-plus' : 'bi-bag-plus' ?> me-1"></i>
                <?= $isSale ? 'New Sale Bill' : 'New Purchase Bill' ?>
            </h3>
            <a href="<?= site_url('invoices') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
        <div class="card-body">
            <div class="row g-3 mb-2">
                <div class="col-md-4">
                    <label class="form-label"><?= $isSale ? 'Customer' : 'Supplier' ?></label>
                    <input type="text" name="party_name" class="form-control" placeholder="<?= $isSale ? 'Cash Sale' : 'Cash Purchase' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Date</label>
                    <input type="date" name="invoice_date" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-5">
                    <label class="form-label">Payment mode</label>
                    <select name="payment_mode" class="form-select">
                        <option value="cash">Cash</option>
                        <option value="bank">Bank</option>
                        <option value="upi">UPI</option>
                        <option value="cheque">Cheque</option>
                        <option value="card">Card</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive mt-2">
                <table class="table table-bordered align-middle mb-0" id="lineTable">
                    <thead class="table-light">
                        <tr>
                            <th style="min-width:200px">Product</th>
                            <th style="width:110px" class="text-end">Qty</th>
                            <th style="width:130px" class="text-end">Rate</th>
                            <th style="width:100px" class="text-end">Tax %</th>
                            <th style="width:140px" class="text-end">Amount</th>
                            <th style="width:44px"></th>
                        </tr>
                    </thead>
                    <tbody id="lineBody"></tbody>
                </table>
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addLine"><i class="bi bi-plus-lg me-1"></i>Add item</button>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-md-7">
            <div class="card">
                <div class="card-body">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
        </div>
        <div class="col-md-5">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Subtotal</span><span id="tSub">₹0.00</span></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Tax</span><span id="tTax">₹0.00</span></div>
                    <div class="d-flex justify-content-between py-1 align-items-center">
                        <span class="text-muted">Discount</span>
                        <input type="number" name="discount" id="disc" class="form-control form-control-sm text-end" style="width:130px" value="0" min="0" step="0.01">
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between py-1 fs-5 fw-bold"><span>Grand Total</span><span id="tGrand" class="text-primary">₹0.00</span></div>
                    <button type="submit" class="btn <?= $isSale ? 'btn-success' : 'btn-primary' ?> w-100 mt-2" id="saveBtn">
                        <i class="bi bi-check2-circle me-1"></i><?= $isSale ? 'Save Sale' : 'Save Purchase' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>

<script nonce="{csp-script-nonce}">
(function () {
    var CATALOGUE = <?= json_encode($catalogue, JSON_UNESCAPED_UNICODE) ?>;
    var IS_SALE = <?= $isSale ? 'true' : 'false' ?>;
    var idx = 0;

    function money(v) { return '₹' + (Number(v) || 0).toFixed(2); }

    function optionList() {
        var o = '<option value="">— Select product —</option>';
        CATALOGUE.forEach(function (p) {
            o += '<option value="' + p.id + '">' + p.name + ' (' + p.stock + ' ' + (p.unit || '') + ')</option>';
        });
        return o;
    }

    function addRow() {
        var i = idx++;
        var tr = document.createElement('tr');
        tr.innerHTML =
            '<td><select name="items[' + i + '][product_id]" class="form-select form-select-sm ln-prod">' + optionList() + '</select>'
              + '<input type="hidden" name="items[' + i + '][name]" class="ln-name"></td>'
            + '<td><input type="number" name="items[' + i + '][qty]" class="form-control form-control-sm text-end ln-qty" value="1" min="0" step="0.001"></td>'
            + '<td><input type="number" name="items[' + i + '][rate]" class="form-control form-control-sm text-end ln-rate" value="0" min="0" step="0.01"></td>'
            + '<td><input type="number" name="items[' + i + '][tax_rate]" class="form-control form-control-sm text-end ln-tax" value="0" min="0" step="0.01"></td>'
            + '<td class="text-end ln-amt">₹0.00</td>'
            + '<td class="text-center"><button type="button" class="btn btn-sm btn-outline-danger ln-del"><i class="bi bi-trash"></i></button></td>';
        document.getElementById('lineBody').appendChild(tr);

        var prod = tr.querySelector('.ln-prod');
        prod.addEventListener('change', function () {
            var p = CATALOGUE.find(function (x) { return String(x.id) === prod.value; });
            if (p) {
                tr.querySelector('.ln-rate').value = (IS_SALE ? p.sale : p.pur) || 0;
                tr.querySelector('.ln-tax').value = p.tax || 0;
                tr.querySelector('.ln-name').value = p.name;
            } else {
                tr.querySelector('.ln-name').value = '';
            }
            recalc();
        });
        ['.ln-qty', '.ln-rate', '.ln-tax'].forEach(function (sel) {
            tr.querySelector(sel).addEventListener('input', recalc);
        });
        tr.querySelector('.ln-del').addEventListener('click', function () {
            tr.remove();
            if (!document.querySelectorAll('#lineBody tr').length) { addRow(); }
            recalc();
        });
    }

    function recalc() {
        var sub = 0, tax = 0;
        document.querySelectorAll('#lineBody tr').forEach(function (tr) {
            var q = Number(tr.querySelector('.ln-qty').value) || 0;
            var r = Number(tr.querySelector('.ln-rate').value) || 0;
            var t = Number(tr.querySelector('.ln-tax').value) || 0;
            var amt = q * r;
            tr.querySelector('.ln-amt').textContent = money(amt);
            sub += amt; tax += amt * t / 100;
        });
        var disc = Number(document.getElementById('disc').value) || 0;
        document.getElementById('tSub').textContent = money(sub);
        document.getElementById('tTax').textContent = money(tax);
        document.getElementById('tGrand').textContent = money(Math.max(0, sub + tax - disc));
    }

    document.getElementById('addLine').addEventListener('click', addRow);
    document.getElementById('disc').addEventListener('input', recalc);
    document.getElementById('invForm').addEventListener('submit', function (e) {
        var any = Array.prototype.some.call(document.querySelectorAll('#lineBody tr'), function (tr) {
            return (Number(tr.querySelector('.ln-qty').value) || 0) > 0 &&
                   (tr.querySelector('.ln-prod').value || (tr.querySelector('.ln-name').value || '').trim());
        });
        if (!any) { e.preventDefault(); alert('Add at least one product line.'); }
    });

    addRow();
    recalc();
})();
</script>
