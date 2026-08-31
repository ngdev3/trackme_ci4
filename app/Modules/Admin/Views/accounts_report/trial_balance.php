<?php include __DIR__ . '/_style.php'; ?>
<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="rp-wrap">
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
    <div class="rp-head">
        <div class="rp-title">Trial Balance <small>As on <?= esc($ason !== '' ? $ason : date('Y-m-d')) ?> &middot; live from cash book</small></div>
        <form class="rp-tools" method="get" action="<?= base_url('admin/accounts_report/trial_balance') ?>">
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
            <thead><tr><th>Ledger</th><th>Group</th><th class="num">Debit</th><th class="num">Credit</th></tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="4" class="rp-empty">No ledger activity for this firm / period.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= esc($r->name) ?></td>
                    <td><span class="rp-pill nil"><?= esc($r->group_name) ?></span></td>
                    <td class="num dr"><?= $r->side === 'Dr' ? '&#8377; ' . acc_money($r->abs) : '' ?></td>
                    <td class="num cr"><?= $r->side === 'Cr' ? '&#8377; ' . acc_money($r->abs) : '' ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="num">TOTAL</td>
                    <td class="num dr">&#8377; <?= acc_money($dr) ?></td>
                    <td class="num cr">&#8377; <?= acc_money($cr) ?></td>
                </tr>
                <?php if (abs($diff) > 0.5): ?>
                <tr>
                    <td colspan="2" class="num rp-diff-bad">Difference (unbalanced)</td>
                    <td colspan="2" class="num rp-diff-bad">&#8377; <?= acc_money(abs($diff)) ?> <?= $diff > 0 ? 'Cr' : 'Dr' ?></td>
                </tr>
                <?php else: ?>
                <tr><td colspan="4" class="num rp-diff-ok">Balanced &#10003;</td></tr>
                <?php endif; ?>
            </tfoot>
        </table>
    </div>
    <div class="rp-note">Note: this ERP is a single-entry cash book; a non-zero difference is carried as a balancing figure and does not indicate data loss.</div>
    <?php endif; ?>
</div></div></div></main>
