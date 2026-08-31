<?php
include __DIR__ . '/_style.php';
$render_side = function ($groups, $extra_label = '', $extra_amount = 0.0) {
    $html = '';
    foreach ($groups as $gname => $g) {
        $html .= '<tr class="rp-grp"><td>' . esc($gname) . '</td><td class="num">&#8377; ' . acc_money($g['total']) . '</td></tr>';
        foreach ($g['lines'] as $ln) {
            $html .= '<tr><td style="padding-left:26px;">' . esc($ln->name) . '</td><td class="num">&#8377; ' . acc_money($ln->line_value) . '</td></tr>';
        }
    }
    if ($extra_label !== '') {
        $html .= '<tr class="rp-grp"><td>' . esc($extra_label) . '</td><td class="num">&#8377; ' . acc_money($extra_amount) . '</td></tr>';
    }
    return $html;
};
?>
<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="rp-wrap">
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
    <div class="rp-head">
        <div class="rp-title">Balance Sheet <small>As on <?= esc($ason !== '' ? $ason : date('Y-m-d')) ?></small></div>
        <form class="rp-tools" method="get" action="<?= base_url('admin/accounts_report/balance_sheet') ?>">
            <div><label>As on date</label><input type="date" name="ason" value="<?= esc($ason) ?>"></div>
            <button class="rp-btn" type="submit"><i class="ti-reload"></i> Apply</button>
            <a class="rp-btn ghost" href="<?= base_url('admin/accounts_report') ?>"><i class="ti-arrow-left"></i> Reports</a>
        </form>
    </div>

    <?php if (empty($ready)): ?>
        <div class="rp-panel"><div class="rp-empty">Chart-of-accounts schema not applied. See <a href="<?= base_url('admin/accounts_report') ?>">setup</a>.</div></div>
    <?php else: ?>
    <div class="rp-two">
        <div class="rp-panel">
            <table class="rp">
                <thead><tr><th>Liabilities</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    <?= $render_side($liabilities, ($net_profit >= 0 ? 'Profit & Loss A/c (Net Profit)' : ''), $net_profit >= 0 ? $net_profit : 0) ?>
                    <?php if (empty($liabilities) && $net_profit < 0): ?><tr><td colspan="2" class="rp-empty">—</td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr><td class="num">TOTAL</td><td class="num">&#8377; <?= acc_money($liability_total) ?></td></tr></tfoot>
            </table>
        </div>
        <div class="rp-panel">
            <table class="rp">
                <thead><tr><th>Assets</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    <?= $render_side($assets, ($net_profit < 0 ? 'Profit & Loss A/c (Net Loss)' : ''), $net_profit < 0 ? -$net_profit : 0) ?>
                </tbody>
                <tfoot><tr><td class="num">TOTAL</td><td class="num">&#8377; <?= acc_money($asset_total) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
    <?php if (abs($difference) > 0.5): ?>
        <div class="rp-note rp-diff-bad">Assets and Liabilities differ by &#8377; <?= acc_money(abs($difference)) ?>. In a single-entry cash book this residual is expected; review via the Trial Balance.</div>
    <?php else: ?>
        <div class="rp-note rp-diff-ok">Balanced &#10003;</div>
    <?php endif; ?>
    <?php endif; ?>
</div></div></div></main>
