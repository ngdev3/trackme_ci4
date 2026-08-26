<?php
/** Product Master — modern list + add/edit modal. In layout.php. */
$rows  = $rows ?? [];
$tab   = $tab ?? 'products';
$money = static fn ($v): string => '₹' . number_format((float) $v, 2);
$hue   = static fn (string $x): int => (int) (crc32($x) % 360);
$navTabs = [
    ['dashboard', 'Dashboard', 'bi-speedometer2', 'inventory'],
    ['products', 'Products', 'bi-box-seam', 'inventory/products'],
    ['stock', 'Stock In / Out', 'bi-arrow-down-up', 'inventory/stock'],
    ['reports', 'Report', 'bi-bar-chart', 'inventory/reports'],
];
?>
<div class="inv-wrap">
    <div class="inv-tabs">
        <?php foreach ($navTabs as [$k, $lbl, $ic, $url]): ?>
        <a href="<?= site_url($url) ?>" class="inv-tab <?= $tab === $k ? 'active' : '' ?>"><i class="bi <?= $ic ?>"></i><span><?= esc($lbl) ?></span></a>
        <?php endforeach; ?>
    </div>

    <div class="inv-panel">
        <div class="inv-panel-head">
            <h3 class="inv-panel-title"><i class="bi bi-box-seam"></i> Product Master <span class="inv-pill gray"><?= count($rows) ?></span></h3>
            <div class="d-flex gap-2 flex-wrap">
                <div class="inv-search"><i class="bi bi-search"></i><input type="search" id="pmSearch" placeholder="Search name, SKU, category…"></div>
                <button class="inv-btn primary" id="pmAdd"><i class="bi bi-plus-lg"></i> Add Product</button>
            </div>
        </div>
        <div class="inv-panel-body">
            <?php if (empty($rows)): ?>
                <div class="inv-empty">
                    <i class="bi bi-box-seam"></i>
                    <p>No products yet. Add your first item to start billing and tracking stock.</p>
                    <button class="inv-btn primary mt-3" id="pmAdd2"><i class="bi bi-plus-lg"></i> Add Product</button>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="inv-table" id="pmTable">
                        <thead><tr>
                            <th>Product</th><th>Category</th><th class="r">Stock</th>
                            <th class="r">Purchase</th><th class="r">Sale</th><th class="r">Tax</th><th class="c">Action</th>
                        </tr></thead>
                        <tbody>
                            <?php foreach ($rows as $p):
                                $stk = (float) $p['current_stock']; $low = (float) $p['low_stock'];
                                [$badge, $cls] = $stk <= 0 ? ['Out', 'out'] : ($low > 0 && $stk <= $low ? ['Low', 'low'] : ['', '']);
                                $hay = strtolower(trim($p['name'] . ' ' . $p['sku'] . ' ' . $p['category']));
                                $h = $hue($p['name']);
                            ?>
                            <tr data-search="<?= esc($hay, 'attr') ?>"
                                data-id="<?= (int) $p['id'] ?>"
                                data-name="<?= esc($p['name'], 'attr') ?>" data-sku="<?= esc($p['sku'], 'attr') ?>"
                                data-category="<?= esc($p['category'], 'attr') ?>" data-unit="<?= esc($p['unit'], 'attr') ?>"
                                data-hsn="<?= esc($p['hsn'], 'attr') ?>" data-sale_price="<?= esc($p['sale_price'], 'attr') ?>"
                                data-purchase_price="<?= esc($p['purchase_price'], 'attr') ?>" data-low_stock="<?= esc($p['low_stock'], 'attr') ?>"
                                data-tax_rate="<?= esc($p['tax_rate'], 'attr') ?>" data-description="<?= esc($p['description'], 'attr') ?>">
                                <td>
                                    <div class="inv-cellflex">
                                        <span class="inv-ava" style="background:hsl(<?= $h ?>,70%,93%);color:hsl(<?= $h ?>,55%,38%)"><?= esc(strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
                                        <span>
                                            <span class="inv-prod-name d-block"><?= esc($p['name']) ?><?php if ($badge): ?> <span class="inv-pill <?= $cls ?>"><?= $badge ?></span><?php endif; ?></span>
                                            <span class="inv-prod-sub"><?= esc($p['sku'] ?: '—') ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td><?= esc($p['category'] ?: '—') ?></td>
                                <td class="r"><b><?= rtrim(rtrim((string) $stk, '0'), '.') ?></b> <span class="inv-prod-sub"><?= esc($p['unit'] ?: '') ?></span></td>
                                <td class="r"><?= $money($p['purchase_price']) ?></td>
                                <td class="r"><?= $money($p['sale_price']) ?></td>
                                <td class="r"><?= ((float) $p['tax_rate']) ? $p['tax_rate'] . '%' : '—' ?></td>
                                <td class="c text-nowrap">
                                    <button class="inv-icon-btn pm-edit" title="Edit"><i class="bi bi-pencil"></i></button>
                                    <form method="post" action="<?= site_url('inventory/products/delete/' . (int) $p['id']) ?>" class="d-inline" onsubmit="return confirm('Remove this product?');">
                                        <?= csrf_field() ?>
                                        <button class="inv-icon-btn danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add / Edit modal -->
<div class="modal fade" id="pmModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <form method="post" action="<?= site_url('inventory/products/save') ?>" class="modal-content" style="border-radius:18px;overflow:hidden;">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="pmId">
            <div class="modal-header" style="background:var(--inv-grad, linear-gradient(135deg,#0b2f73,#1769c2));color:#fff;border:0;">
                <h5 class="modal-title"><i class="bi bi-box-seam me-1"></i><span id="pmTitle">Add Product</span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-md-6"><label class="form-label fw-semibold">Name <span class="text-danger">*</span></label><input name="name" id="f_name" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">SKU</label><input name="sku" id="f_sku" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Category</label><input name="category" id="f_category" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Unit</label><input name="unit" id="f_unit" class="form-control" placeholder="pcs, kg, ltr"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">HSN</label><input name="hsn" id="f_hsn" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Purchase ₹</label><input type="number" step="0.01" min="0" name="purchase_price" id="f_purchase_price" class="form-control" value="0"></div>
                    <div class="col-md-3"><label class="form-label fw-semibold">Sale ₹</label><input type="number" step="0.01" min="0" name="sale_price" id="f_sale_price" class="form-control" value="0"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Opening Stock <span id="openHint" class="text-muted small fw-normal"></span></label><input type="number" step="0.001" min="0" name="opening_stock" id="f_opening_stock" class="form-control" value="0"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Low-stock Alert</label><input type="number" step="0.001" min="0" name="low_stock" id="f_low_stock" class="form-control" value="0"></div>
                    <div class="col-md-4"><label class="form-label fw-semibold">Tax %</label><input type="number" step="0.01" min="0" name="tax_rate" id="f_tax_rate" class="form-control" value="0"></div>
                    <div class="col-12"><label class="form-label fw-semibold">Description</label><textarea name="description" id="f_description" class="form-control" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check2 me-1"></i>Save Product</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var modalEl = document.getElementById('pmModal');
    var modal = new bootstrap.Modal(modalEl);
    var openField = document.getElementById('f_opening_stock');
    var openHint = document.getElementById('openHint');

    function fill(d) {
        document.getElementById('pmId').value = d.id || '';
        ['name','sku','category','unit','hsn','sale_price','purchase_price','low_stock','tax_rate','description'].forEach(function (k) {
            var el = document.getElementById('f_' + k); if (el) { el.value = (d[k] != null ? d[k] : ''); }
        });
    }
    function openAdd() {
        fill({}); document.getElementById('pmTitle').textContent = 'Add Product';
        openField.value = 0; openField.disabled = false; openHint.textContent = '';
        modal.show();
    }
    ['pmAdd','pmAdd2'].forEach(function (id) { var b = document.getElementById(id); if (b) { b.addEventListener('click', openAdd); } });
    document.querySelectorAll('.pm-edit').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = btn.closest('tr').dataset;
            fill(d); document.getElementById('pmTitle').textContent = 'Edit Product';
            openField.value = ''; openField.disabled = true; openHint.textContent = '(use Stock In/Out)';
            modal.show();
        });
    });
    var search = document.getElementById('pmSearch');
    if (search) {
        search.addEventListener('input', function () {
            var q = this.value.toLowerCase().trim();
            document.querySelectorAll('#pmTable tbody tr').forEach(function (tr) {
                tr.style.display = (tr.dataset.search || '').indexOf(q) >= 0 ? '' : 'none';
            });
        });
    }
})();
</script>
