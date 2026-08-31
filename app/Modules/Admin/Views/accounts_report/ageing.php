<?php include __DIR__ . '/_style.php'; ?>
<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="rp-wrap">
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
    <div class="rp-head">
        <div class="rp-title">Ageing Report — Receivables <small>FIFO ageing of debtor balances as on <?= esc($ason) ?></small></div>
        <form class="rp-tools" method="get" action="<?= base_url('admin/accounts_report/ageing') ?>">
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
            <thead><tr>
                <th>Party</th><th>Group</th>
                <th class="num">0–30</th><th class="num">31–60</th><th class="num">61–90</th><th class="num">90+</th>
                <th class="num">Total</th>
            </tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="7" class="rp-empty">No open receivables.</td></tr>
            <?php else: foreach ($rows as $r): $a = $r->ageing; ?>
                <tr>
                    <td><?= esc($r->name) ?></td>
                    <td><span class="rp-pill nil"><?= esc($r->group_name) ?></span></td>
                    <td class="num"><?= $a['b0_30'] > 0 ? '&#8377; ' . acc_money($a['b0_30']) : '—' ?></td>
                    <td class="num"><?= $a['b31_60'] > 0 ? '&#8377; ' . acc_money($a['b31_60']) : '—' ?></td>
                    <td class="num"><?= $a['b61_90'] > 0 ? '&#8377; ' . acc_money($a['b61_90']) : '—' ?></td>
                    <td class="num dr"><?= $a['b90_plus'] > 0 ? '&#8377; ' . acc_money($a['b90_plus']) : '—' ?></td>
                    <td class="num"><b>&#8377; <?= acc_money($a['total']) ?></b></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="num">TOTAL</td>
                    <td class="num">&#8377; <?= acc_money($tot['b0_30']) ?></td>
                    <td class="num">&#8377; <?= acc_money($tot['b31_60']) ?></td>
                    <td class="num">&#8377; <?= acc_money($tot['b61_90']) ?></td>
                    <td class="num dr">&#8377; <?= acc_money($tot['b90_plus']) ?></td>
                    <td class="num">&#8377; <?= acc_money($tot['total']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <div class="rp-note">Ageing walks each party's dated debit/credit entries oldest-first (FIFO), settling credits against the oldest debits; whatever debit remains open is bucketed by its own age.</div>
    <?php endif; ?>
</div></div></div></main>
