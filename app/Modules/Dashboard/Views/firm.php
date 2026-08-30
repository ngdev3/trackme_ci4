<?php
/** Redesigned real-time ERP business dashboard. Rendered inside layout.php. */
$me    = $me ?? current_user();
$money = static fn ($n) => '&#8377;&nbsp;' . number_format((float) $n, 2);
$plain = static fn ($n) => number_format((float) $n, 2);
$scopeTitle = ! empty($isSuperDashboard) ? 'All Companies' : ($firm['name'] ?? 'Active Company');
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$firstName = trim(explode(' ', (string) ($me['name'] ?? 'there'))[0]) ?: 'there';
$periods = [
    'today' => 'Today', 'week' => 'This Week', 'month' => 'This Month',
    'quarter' => 'Quarter', 'financial_year' => 'Financial Year', 'custom' => 'Custom',
];
$net = (float) ($txn['net'] ?? 0);
?>
<script nonce="{csp-script-nonce}">
window.FIRM_CHARTS = <?= json_encode($charts) ?>;
window.DL_LIVE = <?= json_encode([
    'url'    => $liveUrl ?? site_url('dashboard/live'),
    'period' => $period ?? 'month',
    'from'   => $dateFrom ?? '',
    'to'     => $dateTo ?? '',
    'every'  => 20000,
]) ?>;
</script>

<div class="dl-wrap">

    <!-- ===================== HERO ===================== -->
    <div class="dl-hero mb-3">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="dl-hero-kicker"><i class="bi bi-speedometer2"></i> ERP Dashboard &middot; <?= esc($scopeTitle) ?></div>
                <h2 class="dl-hero-title"><?= esc($greeting) ?>, <?= esc($firstName) ?> 👋</h2>
                <div class="dl-hero-sub"><i class="bi bi-calendar3 me-1"></i><?= esc(date('l, d M Y')) ?> &middot; <span class="dl-hero-clock" data-live-clock><?= esc(date('H:i:s')) ?></span></div>
            </div>
            <div class="text-end">
                <div class="d-flex align-items-center justify-content-end gap-2 mb-2">
                    <span class="dl-live"><span class="dl-dot"></span> Live</span>
                    <span class="dl-updated" data-live-updated>updated just now</span>
                </div>
                <div class="dl-hero-kicker">Cash in Hand</div>
                <div class="dl-hero-cash" data-live="cash_in_hand" data-money><?= $money($txn['cash_in_hand'] ?? 0) ?></div>
            </div>
        </div>
        <form class="dl-hero-filter d-flex flex-wrap gap-2 mt-3" method="get" autocomplete="off">
            <select name="period" class="form-select form-select-sm w-auto" data-autosubmit>
                <?php foreach ($periods as $k => $l): ?>
                    <option value="<?= esc($k) ?>" <?= ($period ?? 'month') === $k ? 'selected' : '' ?>><?= esc($l) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="from" class="form-control form-control-sm w-auto" value="<?= esc($dateFrom) ?>">
            <input type="date" name="to" class="form-control form-control-sm w-auto" value="<?= esc($dateTo) ?>">
            <button class="btn btn-sm btn-light"><i class="bi bi-funnel"></i> Apply</button>
            <span class="align-self-center ms-1 small" style="opacity:.85"><?= esc($periodLabel ?? 'This Month') ?></span>
        </form>
    </div>

    <!-- ===================== PRIMARY KPIs (real ledger) ===================== -->
    <div class="row g-3 mb-3">
        <?php
        $todayJama = (float) ($txn['today_jama'] ?? 0);
        $todayNaam = (float) ($txn['today_naam'] ?? 0);
        $kpis = [
            ['Money In (Jama)', 'jama', $txn['jama'] ?? 0, 'bi-arrow-down-circle-fill', '#16a34a',
                '<span class="up">+' . $plain($todayJama) . '</span> today'],
            ['Money Out (Naam)', 'naam', $txn['naam'] ?? 0, 'bi-arrow-up-circle-fill', '#dc2626',
                '<span class="down">-' . $plain($todayNaam) . '</span> today'],
            ['Net Flow', 'net', $net, 'bi-activity', ($net >= 0 ? '#0ea5e9' : '#f59e0b'),
                ($net >= 0 ? 'Surplus this period' : 'Deficit this period')],
            ['Ledger Entries', 'count', $txn['count'] ?? 0, 'bi-journal-text', '#7c3aed',
                (int) ($txn['pending'] ?? 0) . ' pending / overdue'],
        ];
        foreach ($kpis as [$label, $key, $value, $icon, $accent, $sub]):
            $isMoney = $key !== 'count';
        ?>
            <div class="col-6 col-xl-3">
                <div class="dl-kpi" style="--dl-accent: <?= esc($accent, 'attr') ?>;">
                    <div class="dl-kpi-top">
                        <span class="dl-kpi-label"><?= esc($label) ?></span>
                        <span class="dl-kpi-icon"><i class="bi <?= esc($icon) ?>"></i></span>
                    </div>
                    <div class="dl-kpi-value" data-live="<?= esc($key) ?>" <?= $isMoney ? 'data-money' : '' ?>>
                        <?= $isMoney ? $money($value) : number_format((int) $value) ?>
                    </div>
                    <div class="dl-kpi-sub" data-live-sub="<?= esc($key) ?>"><?= $sub ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ===================== LIVE COUNTER CHIPS ===================== -->
    <div class="dl-chips mb-3">
        <?php
        $chips = [
            ['transactions', 'Transactions', 'bi-cash-coin', 'transactions'],
            ['ledgers', 'Ledgers', 'bi-journals', 'accounting/ledgers'],
            ['vouchers', 'Vouchers', 'bi-receipt', 'accounting/vouchers'],
            ['notes', 'Notes', 'bi-sticky', 'notes'],
            ['reminders', 'Reminders', 'bi-alarm', 'reminders'],
            ['passwords', 'Passwords', 'bi-shield-lock', 'passwords'],
        ];
        foreach ($chips as [$key, $label, $icon, $url]):
        ?>
            <a class="dl-chip" href="<?= site_url($url) ?>">
                <i class="bi <?= esc($icon) ?>"></i>
                <span>
                    <span class="n" data-live-count="<?= esc($key) ?>"><?= number_format((int) ($counts[$key] ?? 0)) ?></span>
                    <span class="l"><?= esc($label) ?></span>
                </span>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- ===================== TREND + MODE ===================== -->
    <div class="row g-3 mb-3">
        <div class="col-xl-8">
            <div class="dl-card">
                <div class="dl-card-head">
                    <h3 class="dl-card-title" translate="no"><i class="bi bi-graph-up-arrow"></i> Jama vs Naam — Daily Trend</h3>
                    <span class="badge text-bg-light border">Last 14 days</span>
                </div>
                <div class="dl-card-body"><div class="dl-chart"><canvas id="dlTrendChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="dl-card">
                <div class="dl-card-head"><h3 class="dl-card-title" translate="no"><i class="bi bi-pie-chart-fill"></i> By Payment Mode</h3></div>
                <div class="dl-card-body"><div class="dl-chart-sm"><canvas id="dlModeChart"></canvas></div></div>
            </div>
        </div>
    </div>

    <!-- ===================== ACTIVITY FEED + TOP PARTIES ===================== -->
    <div class="row g-3 mb-3">
        <div class="col-xl-7">
            <div class="dl-card">
                <div class="dl-card-head">
                    <h3 class="dl-card-title" translate="no"><i class="bi bi-lightning-charge-fill text-warning"></i> Live Activity</h3>
                    <a href="<?= site_url('transactions') ?>" class="small text-decoration-none">View ledger →</a>
                </div>
                <div class="dl-card-body">
                    <ul class="dl-feed" data-live-feed>
                        <?php if (empty($recentTxns)): ?>
                            <li class="dl-empty"><i class="bi bi-inbox"></i>No transactions yet. Add one in the ledger to see it here live.</li>
                        <?php else: foreach ($recentTxns as $t):
                            $isJama = ($t['type'] ?? '') === 'jama';
                            $rpUrl  = site_url('transactions/report') . '?period=day&date=' . ($t['txn_date'] ?? date('Y-m-d'));
                        ?>
                            <li class="dl-feed-link" data-href="<?= esc($rpUrl, 'attr') ?>" title="Open the Rokadh Parcha for <?= esc(date('d M Y', strtotime($t['txn_date'] ?? 'now')), 'attr') ?>">
                                <span class="dl-feed-ic <?= $isJama ? 'jama' : 'naam' ?>"><i class="bi <?= $isJama ? 'bi-arrow-down' : 'bi-arrow-up' ?>"></i></span>
                                <span class="dl-feed-main">
                                    <span class="dl-feed-name"><?= esc($t['name']) ?></span>
                                    <span class="dl-feed-meta"><?= esc($t['txn_no'] ?? ('#' . $t['id'])) ?> &middot; <?= esc(ucfirst((string) ($t['payment_mode'] ?? 'cash'))) ?> &middot; <?= esc(date('d M', strtotime($t['txn_date']))) ?></span>
                                </span>
                                <span class="dl-feed-amt <?= $isJama ? 'jama' : 'naam' ?>"><?= $isJama ? '+' : '-' ?><?= $plain($t['amount']) ?></span>
                            </li>
                        <?php endforeach; endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-5">
            <div class="dl-card">
                <div class="dl-card-head"><h3 class="dl-card-title" translate="no"><i class="bi bi-people-fill"></i> Top Accounts</h3></div>
                <div class="dl-card-body">
                    <?php if (empty($topParties)): ?>
                        <div class="dl-empty"><i class="bi bi-person-lines-fill"></i>No party activity yet.</div>
                    <?php else: foreach ($topParties as $p):
                        $pos  = (float) $p['net'] >= 0;
                        $stmt = site_url('transactions/statement') . '?party=' . rawurlencode((string) $p['name']);
                    ?>
                        <a class="dl-party" href="<?= esc($stmt, 'attr') ?>" title="Open the statement for <?= esc($p['name'], 'attr') ?>">
                            <span class="dl-party-av"><?= esc(strtoupper(mb_substr($p['name'], 0, 1))) ?></span>
                            <span class="dl-party-name"><?= esc($p['name']) ?><br><span class="dl-feed-meta"><?= (int) $p['cnt'] ?> entries</span></span>
                            <span class="dl-party-net <?= $pos ? 'text-success' : 'text-danger' ?>"><?= $pos ? '+' : '-' ?><?= $plain(abs($p['net'])) ?></span>
                            <i class="bi bi-chevron-right dl-party-go"></i>
                        </a>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== ACCOUNTING OVERVIEW (secondary) ===================== -->
    <div class="row g-3">
        <div class="col-xl-5">
            <div class="dl-card">
                <div class="dl-card-head"><h3 class="dl-card-title" translate="no"><i class="bi bi-bar-chart-line"></i> Sales vs Purchase</h3><span class="badge text-bg-light border">6 months</span></div>
                <div class="dl-card-body"><div class="dl-chart-sm"><canvas id="salesPurchaseChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-xl-3">
            <div class="dl-card">
                <div class="dl-card-head"><h3 class="dl-card-title" translate="no"><i class="bi bi-wallet2"></i> Cash & Bank</h3></div>
                <div class="dl-card-body"><div class="dl-chart-sm"><canvas id="cashBankChart"></canvas></div></div>
            </div>
        </div>
        <div class="col-xl-4">
            <div class="dl-card">
                <div class="dl-card-head"><h3 class="dl-card-title" translate="no"><i class="bi bi-calendar3"></i> Financial Year</h3></div>
                <div class="dl-card-body erp-fy-list">
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Period</span><strong class="small"><?= esc(date('d M Y', strtotime($fy['from']))) ?> – <?= esc(date('d M Y', strtotime($fy['to']))) ?></strong></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Sales</span><strong><?= $money($fy['sales']) ?></strong></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Purchases</span><strong><?= $money($fy['purchases']) ?></strong></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Net Flow</span><strong><?= $money($fy['net_flow']) ?></strong></div>
                    <div class="d-flex justify-content-between py-1"><span class="text-muted">Vouchers</span><strong><?= number_format((int) $fy['vouchers']) ?></strong></div>
                </div>
            </div>
        </div>
    </div>

</div>
