<?php include __DIR__ . '/_style.php'; ?>
<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="rp-wrap">
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
    <div class="rp-head">
        <div class="rp-title">Sister-Firm Reconciliation <small>Inter-firm balances paired across firms (current firm's view)</small></div>
        <a class="rp-btn ghost" href="<?= base_url('admin/accounts_report') ?>"><i class="ti-arrow-left"></i> Reports</a>
    </div>

    <?php if (empty($ready)): ?>
        <div class="rp-panel"><div class="rp-empty">Chart-of-accounts schema not applied. See <a href="<?= base_url('admin/accounts_report') ?>">setup</a>.</div></div>
    <?php else: ?>
    <div class="rp-panel">
        <table class="rp">
            <thead><tr>
                <th>Our Ledger</th><th>Sister Firm</th>
                <th class="num">Our Balance</th><th class="num">Their Balance</th>
                <th class="num">Difference</th><th>Status</th>
            </tr></thead>
            <tbody>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="rp-empty">No Trade-Party ledgers are tagged to a sister firm yet. Set an account's Account Type to <b>Sister Firm</b> and pick the linked firm.</td></tr>
            <?php else: foreach ($rows as $r): ?>
                <tr>
                    <td><?= esc($r->ledger_name) ?></td>
                    <td><?= esc($r->other_firm) ?></td>
                    <td class="num <?= strtolower($r->our_side) ?>"><?= $r->our_side === 'Nil' ? 'Nil' : '&#8377; ' . acc_money($r->our_abs) . ' ' . $r->our_side ?></td>
                    <td class="num <?= strtolower($r->their_side) ?>"><?= $r->their_side === 'Nil' ? 'Nil' : '&#8377; ' . acc_money($r->their_abs) . ' ' . $r->their_side ?></td>
                    <td class="num"><?= abs($r->difference) <= 0.5 ? '&#8377; 0.00' : '&#8377; ' . acc_money(abs($r->difference)) ?></td>
                    <td><span class="rp-pill <?= $r->reconciled ? 'cr' : 'dr' ?>"><?= $r->reconciled ? 'Reconciled' : 'Mismatch' ?></span></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="rp-note">Reconciled when our balance of a sister firm mirrors their balance of us (our Dr &harr; their Cr). Requires the reciprocal ledger in the other firm to be tagged back to us.</div>
    <?php endif; ?>
</div></div></div></main>
