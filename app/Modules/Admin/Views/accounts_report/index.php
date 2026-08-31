<?php $ss = !empty($ready); ?>
<style>
.ar-wrap { max-width: 1200px; margin: 0 auto; }
.ar-h { font-size: 22px; font-weight: 900; color: #18243c; margin: 6px 0 4px; }
.ar-sub { color: #718096; font-size: 13px; margin-bottom: 18px; }
.ar-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 14px; }
@media (max-width: 900px){ .ar-grid { grid-template-columns: 1fr; } }
.ar-card { display:block; border:1px solid #dce6f2; border-radius:12px; background:#fff; padding:18px; box-shadow:0 8px 22px rgba(24,36,60,.06); text-decoration:none; transition:.15s; }
.ar-card:hover { transform: translateY(-2px); box-shadow:0 14px 30px rgba(24,36,60,.12); text-decoration:none; }
.ar-card h6 { margin:0 0 6px; font-size:15px; font-weight:900; color:#0c315f; }
.ar-card p { margin:0; font-size:12px; color:#718096; }
.ar-card .ar-ic { font-size:20px; color:#1769c2; margin-bottom:8px; display:block; }
.ar-setup { border:1px solid #ffe0a6; background:#fff8e9; border-radius:12px; padding:16px 18px; margin-bottom:18px; }
.ar-setup .btn { margin-right:8px; }
</style>
<main class="main-content bgc-grey-100"><div id="mainContent"><div class="container-fluid"><div class="ar-wrap">
    <?php if (session()->getFlashdata('success')): ?><div class="alert alert-success"><?= esc(session()->getFlashdata('success')); ?></div><?php endif; ?>
    <div class="ar-h">Accounting Reports</div>
    <div class="ar-sub">Live Debtor / Creditor status and financial statements — every figure is computed from the cash book (Rokad) in real time.</div>

    <?php if (!$ss): ?>
        <div class="ar-setup">
            <strong>Setup required.</strong> The chart-of-accounts schema is not applied yet.
            Apply <code>database/account_master_modernization.sql</code> on the database, then
            (as super admin) run the setup actions.
            <?php if (erp_is_super_admin()): ?>
                <div style="margin-top:10px;">
                    <a class="btn btn-warning" href="<?= base_url('admin/account_name/ensure_chart') ?>">1. Ensure Chart of Accounts</a>
                    <a class="btn btn-warning" href="<?= base_url('admin/account_name/backfill') ?>">2. Backfill Existing Accounts</a>
                </div>
            <?php endif; ?>
        </div>
    <?php elseif (erp_is_super_admin()): ?>
        <div class="ar-setup" style="border-color:#bfe3c8;background:#f2fbf5;">
            <strong>Maintenance.</strong> Re-run anytime (idempotent):
            <a class="btn btn-sm btn-success" href="<?= base_url('admin/account_name/ensure_chart') ?>">Ensure Chart</a>
            <a class="btn btn-sm btn-success" href="<?= base_url('admin/account_name/backfill') ?>">Backfill Accounts</a>
        </div>
    <?php endif; ?>

    <div class="ar-grid">
        <?php
        $cards = array(
            array('trial_balance', 'ti-list', 'Trial Balance', 'Every ledger\'s net Dr / Cr with totals.'),
            array('balance_sheet', 'ti-layout-grid2', 'Balance Sheet', 'Assets vs Liabilities, trade parties split by balance.'),
            array('profit_loss', 'ti-stats-up', 'Profit & Loss', 'Income vs Expense with Gross / Net split.'),
            array('trading_profit', 'ti-money', 'Trading Profit', 'Sales (BOS + Tax + Un-reg) minus Purchase (Kisan + Purchase module).'),
            array('outstanding', 'ti-wallet', 'Outstanding', 'All parties with a live non-zero balance.'),
            array('debtors', 'ti-arrow-down', 'Debtor Report', 'Parties with a net Debit (they owe us).'),
            array('creditors', 'ti-arrow-up', 'Creditor Report', 'Parties with a net Credit (we owe them).'),
            array('ageing', 'ti-time', 'Ageing Report', 'Receivables bucketed 0–30 / 31–60 / 61–90 / 90+.'),
            array('inter_firm', 'ti-exchange-vertical', 'Sister-Firm Reconciliation', 'Inter-firm balances paired across firms.'),
        );
        foreach ($cards as $c):
        ?>
            <a class="ar-card" href="<?= base_url('admin/accounts_report/' . $c[0]) ?>">
                <span class="ar-ic"><i class="<?= $c[1] ?>"></i></span>
                <h6><?= esc($c[2]) ?></h6>
                <p><?= esc($c[3]) ?></p>
            </a>
        <?php endforeach; ?>
    </div>
</div></div></div></main>
