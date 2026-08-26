<?php
/** Stock / Inventory dashboard — modern hero + stats + tools + recent. In layout.php. */
$s      = $summary ?? ['count' => 0, 'stock_value' => 0, 'sale_value' => 0, 'low' => 0, 'out' => 0];
$recent = $recent ?? [];
$tab    = $tab ?? 'dashboard';
$money  = static fn ($v): string => '₹' . number_format((float) $v, 0);
$hue    = static fn (string $x): int => (int) (crc32($x) % 360);
$profit = (float) $s['sale_value'] - (float) $s['stock_value'];

$navTabs = [
    ['dashboard', 'Dashboard', 'bi-speedometer2', 'inventory'],
    ['products', 'Products', 'bi-box-seam', 'inventory/products'],
    ['stock', 'Stock In / Out', 'bi-arrow-down-up', 'inventory/stock'],
    ['reports', 'Report', 'bi-bar-chart', 'inventory/reports'],
];
$stats = [
    ['Products', number_format((int) $s['count']), 'b', 'bi-boxes'],
    ['Stock Cost', $money($s['stock_value']), 'v', 'bi-cash-stack'],
    ['Retail Value', $money($s['sale_value']), 'g', 'bi-graph-up-arrow'],
    ['Potential Profit', $money($profit), 'a', 'bi-piggy-bank'],
    ['Low Stock', number_format((int) $s['low']), 'a', 'bi-exclamation-triangle'],
    ['Out of Stock', number_format((int) $s['out']), 'r', 'bi-x-octagon'],
];
$tools = [
    ['Product Master', 'Manage items', 'bi-box-seam', 'b', 'inventory/products'],
    ['Stock In / Out', 'Adjust stock', 'bi-arrow-down-up', 'g', 'inventory/stock'],
    ['Report', 'Stock & value', 'bi-bar-chart', 'v', 'inventory/reports'],
    ['New Sale', 'Bill a customer', 'bi-cart-plus', 'n', 'invoices/new/sale'],
    ['New Purchase', 'Bill a supplier', 'bi-bag-plus', 'a', 'invoices/new/purchase'],
    ['All Bills', 'Sales & purchase', 'bi-receipt', 's', 'invoices'],
];
?>
<div class="inv-wrap">

    <!-- Hero -->
    <div class="inv-hero">
        <div class="inv-hero-l">
            <div class="inv-hero-eyebrow"><i class="bi bi-boxes"></i> Stock &amp; Inventory</div>
            <div class="inv-hero-title">Inventory Control Center</div>
            <div class="inv-hero-sub">Track products, move stock and raise bills — all synced live with your mobile app and cash book.</div>
        </div>
        <div class="inv-hero-actions">
            <a href="<?= site_url('inventory/products') ?>" class="inv-hero-btn solid"><i class="bi bi-plus-lg"></i> Add Product</a>
            <a href="<?= site_url('invoices/new/sale') ?>" class="inv-hero-btn"><i class="bi bi-cart-plus"></i> New Sale</a>
            <a href="<?= site_url('inventory/stock') ?>" class="inv-hero-btn"><i class="bi bi-arrow-down-up"></i> Move Stock</a>
        </div>
    </div>

    <!-- Tabs -->
    <div class="inv-tabs">
        <?php foreach ($navTabs as [$k, $lbl, $ic, $url]): ?>
        <a href="<?= site_url($url) ?>" class="inv-tab <?= $tab === $k ? 'active' : '' ?>"><i class="bi <?= $ic ?>"></i><span><?= esc($lbl) ?></span></a>
        <?php endforeach; ?>
    </div>

    <!-- Stat cards -->
    <div class="inv-stats">
        <?php foreach ($stats as [$label, $val, $tone, $icon]): ?>
        <div class="inv-stat">
            <div class="inv-stat-ic <?= $tone ?>"><i class="bi <?= $icon ?>"></i></div>
            <div class="inv-stat-body">
                <div class="inv-stat-label"><?= esc($label) ?></div>
                <div class="inv-stat-value"><?= esc($val) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Tools -->
    <div class="inv-panel">
        <div class="inv-panel-head"><h3 class="inv-panel-title"><i class="bi bi-grid-1x2"></i> Quick Actions</h3></div>
        <div class="inv-panel-body">
            <div class="inv-tools">
                <?php foreach ($tools as [$label, $sub, $icon, $tone, $url]): ?>
                <a href="<?= site_url($url) ?>" class="inv-tool">
                    <div class="inv-tool-ic <?= $tone ?>"><i class="bi <?= $icon ?>"></i></div>
                    <div>
                        <div class="inv-tool-label"><?= esc($label) ?></div>
                        <div class="inv-tool-sub"><?= esc($sub) ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Recent products -->
    <div class="inv-panel">
        <div class="inv-panel-head">
            <h3 class="inv-panel-title"><i class="bi bi-clock-history"></i> Recent Products</h3>
            <a href="<?= site_url('inventory/products') ?>" class="inv-btn ghost sm"><i class="bi bi-box-seam"></i> Manage</a>
        </div>
        <div class="inv-panel-body">
            <?php if (empty($recent)): ?>
                <div class="inv-empty">
                    <i class="bi bi-box-seam"></i>
                    <p>No products yet. Add your first item in the Product Master.</p>
                    <a class="inv-btn primary mt-3" href="<?= site_url('inventory/products') ?>"><i class="bi bi-plus-lg"></i> Add Product</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="inv-table">
                        <thead><tr><th>Product</th><th class="r">Stock</th><th class="r">Sale Price</th><th class="c">Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($recent as $p):
                                $stk = (float) $p['current_stock']; $low = (float) $p['low_stock'];
                                [$badge, $cls] = $stk <= 0 ? ['Out', 'out'] : ($low > 0 && $stk <= $low ? ['Low', 'low'] : ['In stock', 'ok']);
                                $h = $hue($p['name']);
                            ?>
                            <tr>
                                <td>
                                    <div class="inv-cellflex">
                                        <span class="inv-ava" style="background:hsl(<?= $h ?>,70%,93%);color:hsl(<?= $h ?>,55%,38%)"><?= esc(strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
                                        <span>
                                            <span class="inv-prod-name d-block"><?= esc($p['name']) ?></span>
                                            <span class="inv-prod-sub"><?= esc($p['sku'] ?: '—') ?></span>
                                        </span>
                                    </div>
                                </td>
                                <td class="r"><?= rtrim(rtrim((string) $stk, '0'), '.') ?> <span class="inv-prod-sub"><?= esc($p['unit'] ?: '') ?></span></td>
                                <td class="r"><?= $money($p['sale_price']) ?></td>
                                <td class="c"><span class="inv-pill <?= $cls ?>"><?= $badge ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
