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
$np = $pl['net_profit'];
// Grand total for the T (both sides tie): larger of income/expense + carried result.
$grand = max($pl['total_income'], $pl['total_expense']);
?>
<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="rp-wrap">
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
    <div class="rp-head">
        <div class="rp-title">Profit &amp; Loss <small>As on <?= esc($ason !== '' ? $ason : date('Y-m-d')) ?></small></div>
        <form class="rp-tools" method="get" action="<?= base_url('admin/accounts_report/profit_loss') ?>">
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
                <thead><tr><th>Expenses (Dr)</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    <?= $render_side($expense_groups, ($np >= 0 ? 'Net Profit c/d' : ''), $np >= 0 ? $np : 0) ?>
                    <?php if (empty($expense_groups) && $np < 0): ?><tr><td colspan="2" class="rp-empty">—</td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr><td class="num">TOTAL</td><td class="num">&#8377; <?= acc_money($grand) ?></td></tr></tfoot>
            </table>
        </div>
        <div class="rp-panel">
            <table class="rp">
                <thead><tr><th>Income (Cr)</th><th class="num">Amount</th></tr></thead>
                <tbody>
                    <?= $render_side($income_groups, ($np < 0 ? 'Net Loss c/d' : ''), $np < 0 ? -$np : 0) ?>
                    <?php if (empty($income_groups) && $np >= 0): ?><tr><td colspan="2" class="rp-empty">—</td></tr><?php endif; ?>
                </tbody>
                <tfoot><tr><td class="num">TOTAL</td><td class="num">&#8377; <?= acc_money($grand) ?></td></tr></tfoot>
            </table>
        </div>
    </div>
    <div class="rp-panel" style="margin-top:14px;">
        <table class="rp">
            <tbody>
                <tr><td>Gross Profit (trading)</td><td class="num <?= $pl['gross_profit'] >= 0 ? 'cr' : 'dr' ?>">&#8377; <?= acc_money(abs($pl['gross_profit'])) ?> <?= $pl['gross_profit'] >= 0 ? 'Cr' : 'Dr' ?></td></tr>
                <tr><td>Total Income</td><td class="num">&#8377; <?= acc_money($pl['total_income']) ?></td></tr>
                <tr><td>Total Expense</td><td class="num">&#8377; <?= acc_money($pl['total_expense']) ?></td></tr>
                <tr class="rp-grp"><td><?= $np >= 0 ? 'Net Profit' : 'Net Loss' ?></td><td class="num <?= $np >= 0 ? 'cr' : 'dr' ?>">&#8377; <?= acc_money(abs($np)) ?></td></tr>
            </tbody>
        </table>
    </div>
    <div class="rp-note">Gross figures use groups flagged as affecting gross profit (Sales, Purchase, Direct Expenses). Derived live from the cash book; validate against known figures before filing.</div>
    <?php endif; ?>
</div></div></div></main>
