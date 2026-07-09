<?php
/** Simple inventory top nav — three tabs. $active = daily|products|position. */
$active = $active ?? 'daily';
$tabs = [
    'daily'    => ['Daily Stock', 'bi-arrow-down-up', site_url('inventory')],
    'products' => ['Products',     'bi-box-seam',      site_url('inventory/products')],
    'position' => ['Stock Position','bi-clipboard-data', site_url('inventory/position')],
];
?>
<div class="sinv-nav">
    <?php foreach ($tabs as $key => [$label, $icon, $url]): ?>
        <a href="<?= $url ?>" class="sinv-tab<?= $active === $key ? ' active' : '' ?>">
            <i class="bi <?= $icon ?>"></i><span><?= esc($label) ?></span>
        </a>
    <?php endforeach; ?>
</div>
