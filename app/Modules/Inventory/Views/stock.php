<?php
/** Stock In / Out + movement timeline — modern. In layout.php. */
$products  = $products ?? [];
$movements = $movements ?? [];
$tab       = $tab ?? 'stock';
$navTabs = [
    ['dashboard', 'Dashboard', 'bi-speedometer2', 'inventory'],
    ['products', 'Products', 'bi-box-seam', 'inventory/products'],
    ['stock', 'Stock In / Out', 'bi-arrow-down-up', 'inventory/stock'],
    ['reports', 'Report', 'bi-bar-chart', 'inventory/reports'],
];
?>
<div class="cust-page inv-wrap">
    <div class="inv-tabs">
        <?php foreach ($navTabs as [$k, $lbl, $ic, $url]): ?>
        <a href="<?= site_url($url) ?>" class="inv-tab <?= $tab === $k ? 'active' : '' ?>"><i class="bi <?= $ic ?>"></i><span><?= esc($lbl) ?></span></a>
        <?php endforeach; ?>
    </div>

    <div class="row g-3">
        <!-- Movement form -->
        <div class="col-lg-5">
            <section class="cust-panel inv-panel">
                <div class="cust-toolbar"><div><h5 class="cust-table-title">Record Movement</h5><p class="cust-table-note">Add stock in or out without leaving the page.</p></div></div>
                <form method="post" action="<?= site_url('inventory/stock/move') ?>" class="inv-panel-body">
                    <?= csrf_field() ?>
                    <div class="inv-seg inv-field">
                        <input type="radio" name="type" id="tIn" value="in" checked>
                        <label for="tIn" class="in"><i class="bi bi-box-arrow-in-down"></i> Stock In</label>
                        <input type="radio" name="type" id="tOut" value="out">
                        <label for="tOut" class="out"><i class="bi bi-box-arrow-up"></i> Stock Out</label>
                    </div>
                    <div class="inv-field">
                        <label>Product</label>
                        <select name="product_id" id="stProduct" class="inv-input" required>
                            <option value="">— Select product —</option>
                            <?php foreach ($products as $p): ?>
                            <option value="<?= (int) $p['id'] ?>" data-stock="<?= esc($p['current_stock'], 'attr') ?>" data-unit="<?= esc($p['unit'], 'attr') ?>">
                                <?= esc($p['name']) ?> (<?= rtrim(rtrim((string) $p['current_stock'], '0'), '.') ?> <?= esc($p['unit'] ?: '') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="stStock" class="inv-prod-sub mt-2"></div>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 inv-field"><label>Quantity</label><input type="number" step="0.001" min="0" name="qty" class="inv-input" required></div>
                        <div class="col-6 inv-field"><label>Rate (optional)</label><input type="number" step="0.01" min="0" name="rate" class="inv-input" value="0"></div>
                    </div>
                    <div class="inv-field"><label>Note (optional)</label><input name="note" class="inv-input" placeholder="purchase, correction, damage…"></div>
                    <button type="submit" class="cust-btn cust-btn-primary w-100" style="justify-content:center;padding:12px;"><i class="bi bi-check2-circle"></i> Save Movement</button>
                </form>
            </section>
        </div>

        <!-- Timeline -->
        <div class="col-lg-7">
            <section class="cust-panel cust-table-panel inv-panel">
                <div class="cust-toolbar">
                    <div><h5 class="cust-table-title">Recent Movements</h5><p class="cust-table-note">Latest stock in and out records.</p></div>
                    <span class="cust-total-tag"><i class="bi bi-clock-history"></i> <?= count($movements) ?> total</span>
                </div>
                <div class="inv-panel-body">
                    <?php if (empty($movements)): ?>
                        <div class="inv-empty"><i class="bi bi-arrow-down-up"></i><p>No stock movements yet.</p></div>
                    <?php else: ?>
                        <div class="inv-moves" style="max-height:560px;overflow:auto;">
                            <?php foreach ($movements as $m): $in = $m['type'] === 'in'; ?>
                            <div class="inv-move">
                                <div class="inv-move-ic <?= $in ? 'in' : 'out' ?>"><i class="bi bi-arrow-<?= $in ? 'down' : 'up' ?>"></i></div>
                                <div class="inv-move-main">
                                    <div class="inv-move-name"><?= esc($m['product_name']) ?></div>
                                    <div class="inv-move-sub"><?= esc(date('d M Y, H:i', strtotime((string) $m['created_at']))) ?><?php if (! empty($m['note'])): ?> · <?= esc($m['note']) ?><?php endif; ?></div>
                                </div>
                                <div class="inv-move-qty <?= $in ? 'in' : 'out' ?>"><?= $in ? '+' : '−' ?><?= rtrim(rtrim((string) $m['qty'], '0'), '.') ?> <?= esc($m['unit'] ?: '') ?></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </div>
</div>

<script nonce="{csp-script-nonce}">
(function () {
    var sel = document.getElementById('stProduct');
    var hint = document.getElementById('stStock');
    if (sel) {
        sel.addEventListener('change', function () {
            var o = sel.options[sel.selectedIndex];
            hint.innerHTML = o && o.value ? ('<i class="bi bi-layers"></i> In stock: <b>' + (o.dataset.stock || 0) + ' ' + (o.dataset.unit || '') + '</b>') : '';
        });
    }
})();
</script>
