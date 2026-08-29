<?php
/** Inventory report — modern stock summary + movement statement (printable). In layout.php. */
$products  = $products ?? [];
$movements = $movements ?? [];
$tab       = $tab ?? 'reports';
$firm      = $company['name'] ?? 'Hissab-Kitaab';
$money     = static fn ($v): string => '₹' . number_format((float) $v, 2);
$navTabs = [
    ['dashboard', 'Dashboard', 'bi-speedometer2', 'inventory'],
    ['products', 'Products', 'bi-box-seam', 'inventory/products'],
    ['stock', 'Stock In / Out', 'bi-arrow-down-up', 'inventory/stock'],
    ['reports', 'Report', 'bi-bar-chart', 'inventory/reports'],
];
$totalCost = 0.0; $totalSale = 0.0; $low = 0; $out = 0;
foreach ($products as $p) {
    $stk = (float) $p['current_stock'];
    $totalCost += $stk * (float) $p['purchase_price'];
    $totalSale += $stk * (float) $p['sale_price'];
    if ($stk <= 0) { $out++; }
    elseif ((float) $p['low_stock'] > 0 && $stk <= (float) $p['low_stock']) { $low++; }
}
$stats = [
    ['Items', count($products), 'b', 'bi-boxes'],
    ['Stock Cost', $money($totalCost), 'v', 'bi-cash-stack'],
    ['Retail Value', $money($totalSale), 'g', 'bi-graph-up-arrow'],
    ['Low', $low, 'a', 'bi-exclamation-triangle'],
    ['Out', $out, 'r', 'bi-x-octagon'],
];
?>
<div class="inv-wrap">
    <div class="inv-tabs d-print-none">
        <?php foreach ($navTabs as [$k, $lbl, $ic, $url]): ?>
        <a href="<?= site_url($url) ?>" class="inv-tab <?= $tab === $k ? 'active' : '' ?>"><i class="bi <?= $ic ?>"></i><span><?= esc($lbl) ?></span></a>
        <?php endforeach; ?>
    </div>

    <div class="d-flex justify-content-end gap-2 d-print-none">
        <button class="inv-btn primary" data-window="print"><i class="bi bi-printer"></i> Print / Save PDF</button>
    </div>

    <div class="inv-panel">
        <div class="inv-panel-body" id="repArea">
            <div class="inv-report-head">
                <div>
                    <div class="inv-report-firm"><?= esc($firm) ?></div>
                    <div class="inv-report-title">Inventory Report</div>
                </div>
                <div class="text-end inv-prod-sub">Generated<br><b><?= esc(date('d M Y, H:i')) ?></b></div>
            </div>

            <div class="inv-stats mb-4">
                <?php foreach ($stats as [$label, $val, $tone, $icon]): ?>
                <div class="inv-stat">
                    <div class="inv-stat-ic <?= $tone ?>"><i class="bi <?= $icon ?>"></i></div>
                    <div class="inv-stat-body"><div class="inv-stat-label"><?= esc($label) ?></div><div class="inv-stat-value"><?= esc($val) ?></div></div>
                </div>
                <?php endforeach; ?>
            </div>

            <h6 class="inv-panel-title mb-2"><i class="bi bi-clipboard-data"></i> Stock Summary</h6>
            <div class="table-responsive mb-4">
                <table class="inv-table">
                    <thead><tr><th>Product</th><th>SKU</th><th class="r">Stock</th><th class="r">Cost Value</th><th class="r">Retail Value</th></tr></thead>
                    <tbody>
                        <?php foreach ($products as $p): $stk = (float) $p['current_stock']; ?>
                        <tr>
                            <td class="inv-prod-name"><?= esc($p['name']) ?></td>
                            <td class="inv-prod-sub"><?= esc($p['sku'] ?: '—') ?></td>
                            <td class="r"><?= rtrim(rtrim((string) $stk, '0'), '.') ?> <?= esc($p['unit'] ?: '') ?></td>
                            <td class="r"><?= $money($stk * (float) $p['purchase_price']) ?></td>
                            <td class="r" style="color:#0f9d58"><?= $money($stk * (float) $p['sale_price']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($products)): ?><tr><td colspan="5" class="c inv-prod-sub" style="padding:24px">No products.</td></tr><?php endif; ?>
                    </tbody>
                    <?php if (! empty($products)): ?>
                    <tfoot><tr>
                        <td colspan="3" class="r">Total</td>
                        <td class="r"><?= $money($totalCost) ?></td>
                        <td class="r" style="color:#0f9d58"><?= $money($totalSale) ?></td>
                    </tr></tfoot>
                    <?php endif; ?>
                </table>
            </div>

            <h6 class="inv-panel-title mb-2"><i class="bi bi-list-columns-reverse"></i> Stock Statement</h6>
            <div class="table-responsive">
                <table class="inv-table">
                    <thead><tr><th>Date</th><th>Product</th><th class="c">Type</th><th class="r">Qty</th><th>Note</th></tr></thead>
                    <tbody>
                        <?php foreach ($movements as $m): $in = $m['type'] === 'in'; ?>
                        <tr>
                            <td class="inv-prod-sub text-nowrap"><?= esc(date('d M Y, H:i', strtotime((string) $m['created_at']))) ?></td>
                            <td class="inv-prod-name"><?= esc($m['product_name']) ?></td>
                            <td class="c"><span class="inv-pill <?= $in ? 'in' : 'out' ?>"><?= $in ? 'IN' : 'OUT' ?></span></td>
                            <td class="r" style="color:<?= $in ? '#0f9d58' : '#dc2626' ?>;font-weight:800"><?= $in ? '+' : '−' ?><?= rtrim(rtrim((string) $m['qty'], '0'), '.') ?> <?= esc($m['unit'] ?: '') ?></td>
                            <td class="inv-prod-sub"><?= esc($m['note'] ?: '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($movements)): ?><tr><td colspan="5" class="c inv-prod-sub" style="padding:24px">No movements.</td></tr><?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="text-center inv-prod-sub mt-4">Generated by Hissab-Kitaab · computer-generated report</div>
        </div>
    </div>
</div>
