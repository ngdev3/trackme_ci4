<?php
/**
 * Inventory top nav. Worker tabs stay simple; the Owner tab (dashboard, closing,
 * reports) shows only for owners/admins so the worker view stays uncluttered.
 * $active = daily|voice|verify|products|position|owner.
 */
helper(['company', 'auth']);
$active  = $active ?? 'daily';
$isOwner = (function_exists('is_super_admin_account') && is_super_admin_account())
    || in_array(function_exists('company_role') ? company_role() : null, ['owner', 'admin'], true);

$tabs = [
    'daily'    => ['Daily Stock',    'bi-arrow-down-up',   site_url('inventory')],
    'voice'    => ['Voice',          'bi-mic',             site_url('inventory/voice')],
    'verify'   => ['Verify',         'bi-clipboard-check', site_url('inventory/verify')],
    'products' => ['Products',       'bi-box-seam',        site_url('inventory/products')],
    'position' => ['Stock Position', 'bi-clipboard-data',  site_url('inventory/position')],
];
?>
<div class="sinv-nav">
    <?php foreach ($tabs as $key => [$label, $icon, $url]): ?>
        <a href="<?= $url ?>" class="sinv-tab<?= $active === $key ? ' active' : '' ?>">
            <i class="bi <?= $icon ?>"></i><span><?= esc($label) ?></span>
        </a>
    <?php endforeach; ?>
    <?php if ($isOwner): ?>
        <a href="<?= site_url('inventory/dashboard') ?>" class="sinv-tab sinv-tab-owner<?= ($active ?? '') === 'owner' ? ' active' : '' ?>">
            <i class="bi bi-speedometer2"></i><span>Owner</span>
        </a>
    <?php endif; ?>
</div>
