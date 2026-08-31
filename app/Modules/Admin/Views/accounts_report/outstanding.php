<?php
include __DIR__ . '/_style.php';
$mode = isset($mode) ? $mode : 'outstanding';
$self = $mode === 'debtor' ? 'debtors' : ($mode === 'creditor' ? 'creditors' : 'outstanding');
$heading = isset($title) ? $title : 'Outstanding Report';
?>
<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="rp-wrap">
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
    <div class="rp-head">
        <div class="rp-title"><?= esc($heading) ?> <small>As on <?= esc($ason !== '' ? $ason : date('Y-m-d')) ?> &middot; live balances</small></div>
        <form class="rp-tools" method="get" action="<?= base_url('admin/accounts_report/' . $self) ?>">
            <div><label>As on date</label><input type="date" name="ason" value="<?= esc($ason) ?>"></div>
            <button class="rp-btn" type="submit"><i class="ti-reload"></i> Apply</button>
            <a class="rp-btn ghost" href="<?= base_url('admin/accounts_report') ?>"><i class="ti-arrow-left"></i> Reports</a>
        </form>
    </div>

    <?php if (empty($ready)): ?>
        <div class="rp-panel"><div class="rp-empty">Chart-of-accounts schema not applied. See <a href="<?= base_url('admin/accounts_report') ?>">setup</a>.</div></div>
    <?php else: ?>
    <div class="rp-panel">
        <table class="rp">
            <thead><tr><th>Party</th><th>Group</th><th>Type</th><th class="num">Balance</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="rp-empty">No matching parties.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= esc($r->name) ?></td>
                    <td><span class="rp-pill nil"><?= esc($r->group_name) ?></span></td>
                    <td><?= esc(acc_account_type_label($r->account_type)) ?></td>
                    <td class="num <?= strtolower($r->side) ?>">&#8377; <?= acc_money($r->abs) ?> <?= $r->side ?></td>
                    <td><span class="rp-pill <?= strtolower($r->side) ?>"><?= esc($r->status) ?></span></td>
                    <td><a class="rp-btn ghost" style="padding:0 10px;min-height:28px;" href="<?= base_url('admin/report/ledger?party=' . (int) $r->account_id) ?>"><i class="ti-book"></i> Ledger</a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="num">TOTAL</td>
                    <td class="num" colspan="3">
                        <span class="dr">Debtors &#8377; <?= acc_money($tot_dr) ?></span> &nbsp;|&nbsp;
                        <span class="cr">Creditors &#8377; <?= acc_money($tot_cr) ?></span> &nbsp;|&nbsp;
                        Net <?php $net = $tot_cr - $tot_dr; echo '&#8377; ' . acc_money(abs($net)) . ' ' . ($net >= 0 ? 'Cr' : 'Dr'); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php endif; ?>
</div></div></div></main>
