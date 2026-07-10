<?php
/** Jama / Naam transactions ledger — TrackMe-style table. Rendered in layout.php. */
use App\Models\TransactionModel;

$fmt = fn ($n) => number_format((float) $n, 2);

$typeBadge = static function (string $t): string {
    return $t === 'naam'
        ? '<span class="tx-type tx-naam"><i class="bi bi-arrow-down-left"></i>Naam</span>'
        : '<span class="tx-type tx-jama"><i class="bi bi-arrow-up-right"></i>Jama</span>';
};
$statusBadge = static function (string $s): string {
    $map = [
        'paid'      => ['Paid', 'tx-paid', 'bi-check-circle'],
        'pending'   => ['Pending', 'tx-pending', 'bi-hourglass-split'],
        'overdue'   => ['Overdue', 'tx-overdue', 'bi-exclamation-circle'],
        'cancelled' => ['Cancelled', 'tx-cancelled', 'bi-x-circle'],
        'draft'     => ['Draft', 'tx-draft', 'bi-pencil-square'],
    ];
    [$label, $cls, $icon] = $map[$s] ?? ['—', 'tx-cancelled', 'bi-dash'];
    return '<span class="tx-status ' . $cls . '"><i class="bi ' . $icon . '"></i>' . $label . '</span>';
};
$modeIcon = [
    'cash' => 'bi-cash-coin', 'bank' => 'bi-bank', 'upi' => 'bi-phone',
    'cheque' => 'bi-receipt', 'card' => 'bi-credit-card', 'other' => 'bi-three-dots',
];
$f = $filters;
$qs = array_filter(['q' => $f['q'], 'from' => $f['from'], 'to' => $f['to'], 'status' => $f['status'], 'type' => $f['type'], 'mode' => $f['mode'], 'per' => $per]);
?>

<!-- ===== Summary ===== -->
<?php
    $opening  = (float) ($summary['opening'] ?? 0);
    $net      = (float) $summary['net'];
    $closing  = (float) ($summary['closing'] ?? $opening + $net);
    $openName = $openLabel ?? 'Opening Balance';
    $signCls  = static fn (float $v) => $v < 0 ? 'tx-amt-naam' : 'tx-amt-jama';
?>
<div class="row row-cols-2 row-cols-lg-5 g-3 mb-3">
    <div class="col">
        <div class="tx-summary tx-sum-opening">
            <div class="tx-sum-ic"><i class="bi bi-cash-stack"></i></div>
            <div><div class="tx-sum-lbl" title="<?= esc($openName, 'attr') ?> (opening cash carried in)"><?= esc($openName) ?></div><div class="tx-sum-val <?= $signCls($opening) ?>">&#8377; <?= $fmt($opening) ?></div></div>
        </div>
    </div>
    <div class="col">
        <div class="tx-summary tx-sum-jama">
            <div class="tx-sum-ic"><i class="bi bi-arrow-up-right"></i></div>
            <div><div class="tx-sum-lbl">Total Jama</div><div class="tx-sum-val tx-amt-jama">&#8377; <?= $fmt($summary['jama']) ?></div></div>
        </div>
    </div>
    <div class="col">
        <div class="tx-summary tx-sum-naam">
            <div class="tx-sum-ic"><i class="bi bi-arrow-down-left"></i></div>
            <div><div class="tx-sum-lbl">Total Naam</div><div class="tx-sum-val tx-amt-naam">&#8377; <?= $fmt($summary['naam']) ?></div></div>
        </div>
    </div>
    <div class="col">
        <div class="tx-summary tx-sum-net">
            <div class="tx-sum-ic"><i class="bi bi-arrow-left-right"></i></div>
            <div><div class="tx-sum-lbl" title="Jama − Naam for the shown entries">Net (Jama − Naam)</div><div class="tx-sum-val <?= $signCls($net) ?>"><?= $net < 0 ? '−' : '' ?>&#8377; <?= $fmt(abs($net)) ?></div></div>
        </div>
    </div>
    <div class="col">
        <div class="tx-summary tx-sum-balance">
            <div class="tx-sum-ic"><i class="bi bi-wallet2"></i></div>
            <div><div class="tx-sum-lbl" title="Opening + Jama − Naam = cash in hand">Closing Balance</div><div class="tx-sum-val tx-bal <?= $signCls($closing) ?>"><?= $closing < 0 ? '−' : '' ?>&#8377; <?= $fmt(abs($closing)) ?></div></div>
        </div>
    </div>
</div>

<!-- ===== Charts ===== -->
<?php
$modeLabels = $modeVals = [];
foreach (TransactionModel::MODE_LABELS as $mk => $ml) {
    $modeLabels[] = $ml;
    $modeVals[]   = round(($byMode[$mk]['jama'] ?? 0) + ($byMode[$mk]['naam'] ?? 0), 2);
}
$hasChart = array_sum($modeVals) > 0 || ! empty($trend);
?>
<?php if ($hasChart): ?>
<div class="row g-3 mb-3">
    <div class="col-lg-8">
        <div class="card tm-table-card h-100">
            <div class="tm-table-head"><h3 class="tm-table-title"><i class="bi bi-graph-up"></i> Jama vs Naam Trend</h3></div>
            <div class="card-body"><div style="height:240px"><canvas id="txTrend"></canvas></div></div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card tm-table-card h-100">
            <div class="tm-table-head"><h3 class="tm-table-title"><i class="bi bi-pie-chart"></i> By Payment Mode</h3></div>
            <div class="card-body"><div style="height:240px"><canvas id="txMode"></canvas></div></div>
        </div>
    </div>
</div>
<script>
window.TX_CHARTS = {
    trend: <?= json_encode(array_map(fn ($b) => ['d' => date('d M', strtotime($b['d'])), 'jama' => $b['jama'], 'naam' => $b['naam']], $trend)) ?>,
    modeLabels: <?= json_encode($modeLabels) ?>,
    modeData: <?= json_encode($modeVals) ?>
};
</script>
<?php endif; ?>

<!-- ===== Table card ===== -->
<div class="card tm-table-card">
    <div class="tm-table-head">
        <h3 class="tm-table-title"><i class="bi bi-receipt-cutoff"></i> Jama / Naam Transactions</h3>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('transactions/report') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-journal-text"></i> Rokadh Parcha</a>
            <a href="<?= site_url('transactions/statement') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-vcard"></i> Account Statement</a>
            <?php if (sub_is_pro()): ?>
                <a href="<?= site_url('transactions/export/pdf') . '?' . http_build_query($qs) ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
                    <span class="badge rounded-pill text-bg-primary ms-1">New</span>
                </a>
            <?php else: ?>
                <a href="<?= site_url('subscription') ?>" class="btn btn-outline-primary btn-sm" title="Available on the paid plan"><i class="bi bi-lock me-1"></i>Export PDF</a>
            <?php endif; ?>
            <?php if (can($moduleCode, 'add')): ?>
                <a href="<?= site_url('transactions/create') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Add New</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filters -->
    <div class="tx-filters">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-12 col-md">
                <label class="tx-flabel">Search</label>
                <div class="tx-search"><i class="bi bi-search"></i>
                    <input type="search" name="q" value="<?= esc($f['q']) ?>" class="form-control form-control-sm" placeholder="Txn no, party or notes...">
                </div>
            </div>
            <div class="col-6 col-md-auto">
                <label class="tx-flabel">From</label>
                <input type="date" name="from" value="<?= esc($f['from']) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-auto">
                <label class="tx-flabel">To</label>
                <input type="date" name="to" value="<?= esc($f['to']) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-auto">
                <label class="tx-flabel">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="jama" <?= $f['type'] === 'jama' ? 'selected' : '' ?>>Jama</option>
                    <option value="naam" <?= $f['type'] === 'naam' ? 'selected' : '' ?>>Naam</option>
                </select>
            </div>
            <div class="col-6 col-md-auto">
                <label class="tx-flabel">Mode</label>
                <select name="mode" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (TransactionModel::MODE_LABELS as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $f['mode'] === $val ? 'selected' : '' ?>><?= esc($lbl) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-auto">
                <label class="tx-flabel">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (['paid', 'pending', 'overdue', 'cancelled', 'draft'] as $s): ?>
                        <option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 col-md-auto d-flex gap-2">
                <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Filter</button>
                <a href="<?= site_url('transactions/list') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table align-middle mb-0 tm-table">
                <thead><tr>
                    <th>Txn No</th><th>Date</th><th>Party</th><th>Type</th><th>Mode</th>
                    <th class="text-end">Amount</th><th>Status</th>
                    <th class="text-end">Balance</th><th class="text-end">Action</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="9" class="text-center text-secondary py-5"><i class="bi bi-inbox fs-1 d-block mb-2 opacity-50"></i>No transactions found.</td></tr>
                <?php else: foreach ($rows as $r): $att = $counts[$r['id']] ?? 0; ?>
                    <tr>
                        <td class="text-nowrap"><span class="tx-no"><?= esc($r['txn_no'] ?: '—') ?></span></td>
                        <td class="text-nowrap"><?= esc(date('d M Y', strtotime($r['txn_date']))) ?></td>
                        <td>
                            <div class="fw-semibold"><?= esc($r['name']) ?>
                                <?php if ($att > 0): ?><span class="text-secondary ms-1" title="<?= $att ?> attachment(s)"><i class="bi bi-paperclip"></i><?= $att ?></span><?php endif; ?>
                            </div>
                            <?php if (! empty($r['notes'])): ?><small class="text-muted"><?= esc(character_limiter($r['notes'], 40)) ?></small><?php endif; ?>
                        </td>
                        <td><?= $typeBadge($r['type']) ?></td>
                        <td><span class="tx-mode"><i class="bi <?= $modeIcon[$r['payment_mode']] ?? 'bi-wallet' ?>"></i><?= esc(TransactionModel::MODE_LABELS[$r['payment_mode']] ?? ucfirst((string) $r['payment_mode'])) ?></span></td>
                        <td class="text-end fw-semibold <?= $r['type'] === 'jama' ? 'tx-amt-jama' : 'tx-amt-naam' ?>">
                            <?= $r['type'] === 'jama' ? '+' : '−' ?> &#8377; <?= $fmt($r['amount']) ?>
                        </td>
                        <td><?= $statusBadge($r['status']) ?></td>
                        <td class="text-end fw-semibold tx-bal">&#8377; <?= $fmt($r['balance'] ?? 0) ?></td>
                        <td class="text-end text-nowrap">
                            <button type="button" class="tx-act tx-view" title="View" data-tx-view data-id="<?= hid($r['id']) ?>"><i class="bi bi-eye"></i></button>
                            <?php if (can($moduleCode, 'edit')): ?>
                                <a href="<?= site_url('transactions/edit/' . hid($r['id'])) ?>" class="tx-act tx-edit" title="Edit"><i class="bi bi-pencil"></i></a>
                            <?php endif; ?>
                            <?php if (can($moduleCode, 'delete')): ?>
                                <button type="button" class="tx-act tx-delete" title="Delete" data-tx-delete data-action="<?= site_url('transactions/delete/' . hid($r['id'])) ?>" data-label="<?= esc($r['txn_no'], 'attr') ?>"><i class="bi bi-trash"></i></button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="tm-table-foot">
        <span class="tm-table-count">
            <?php $d = isset($pager) ? $pager->getDetails() : ['total' => count($rows)]; ?>
            <?= ! empty($d['total']) ? 'Showing <strong>' . number_format(count($rows)) . '</strong> of <strong>' . number_format($d['total']) . '</strong>' : 'No records' ?>
        </span>
        <?php if (isset($pager)): ?><div class="tm-pager"><?= $pager->only(['q', 'from', 'to', 'status', 'type', 'mode', 'per'])->links('default', 'erp') ?></div><?php endif; ?>
    </div>
</div>

<script>
// Charts render after Chart.js (loaded in the footer) — defer to DOMContentLoaded.
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined' || !window.TX_CHARTS) return;
    var C = window.TX_CHARTS;
    var muted = getComputedStyle(document.body).getPropertyValue('--erp-muted') || '#94a3b8';

    var trendEl = document.getElementById('txTrend');
    if (trendEl && C.trend.length) {
        new Chart(trendEl, {
            type: 'line',
            data: {
                labels: C.trend.map(function (r) { return r.d; }),
                datasets: [
                    { label: 'Jama', data: C.trend.map(function (r) { return r.jama; }), borderColor: '#16a34a', backgroundColor: 'rgba(34,197,94,.12)', fill: true, tension: .3 },
                    { label: 'Naam', data: C.trend.map(function (r) { return r.naam; }), borderColor: '#dc2626', backgroundColor: 'rgba(239,68,68,.12)', fill: true, tension: .3 }
                ]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
        });
    }

    var modeEl = document.getElementById('txMode');
    if (modeEl && C.modeData.some(function (v) { return v > 0; })) {
        new Chart(modeEl, {
            type: 'doughnut',
            data: {
                labels: C.modeLabels,
                datasets: [{ data: C.modeData, backgroundColor: ['#22c55e', '#3b82f6', '#8b5cf6', '#f59e0b', '#0ea5e9', '#94a3b8'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });
    }
});
</script>

<?= view('Modules\Transactions\Views\_modals') ?>
