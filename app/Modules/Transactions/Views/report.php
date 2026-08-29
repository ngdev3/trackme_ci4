<?php
/** Rokad Parcha (cash book) report — period selector + cash-book table. In layout.php. */
use App\Models\TransactionModel;

$fmt = fn ($n) => number_format((float) $n, 2);
$in  = $in;
$p   = $period;
$ts  = time();
$curFy = (int) date('n', $ts) >= 4 ? (int) date('Y', $ts) : (int) date('Y', $ts) - 1;
$qs  = fn (array $extra = []) => http_build_query(array_merge(array_filter($in), $extra));

$authors   = $authors ?? [];
$modeLabels = TransactionModel::MODE_LABELS;
$statusCls  = ['paid' => 'tx-paid', 'pending' => 'tx-pending', 'overdue' => 'tx-overdue', 'cancelled' => 'tx-cancelled', 'draft' => 'tx-draft'];

$srcBadge = static fn (?string $s): string => $s === 'app'
    ? '<span class="rp-badge rp-badge-app"><i class="bi bi-phone"></i> App</span>'
    : '<span class="rp-badge rp-badge-web"><i class="bi bi-display"></i> Web</span>';
$typeBadge = static fn (string $t): string => $t === 'naam'
    ? '<span class="tx-type tx-expense"><i class="bi bi-arrow-down-left"></i> Naam</span>'
    : '<span class="tx-type tx-income"><i class="bi bi-arrow-up-right"></i> Jama</span>';
?>

<style>
/* Wide 15-column cash-book table: pin the row-number (#) column on the left and
   the running Balance column on the right so they never scroll away or clip.
   Opaque backgrounds (covering thead / tbody / tfoot + highlighted rows) keep
   the scrolled middle columns from showing through the pinned cells. */
.rp-scroll{ position:relative; }
.rp-scroll .tm-table th:first-child, .rp-scroll .tm-table td:first-child{
    position:sticky; left:0; z-index:3; box-shadow:1px 0 0 var(--bs-border-color); }
.rp-scroll .tm-table th:last-child,  .rp-scroll .tm-table td:last-child{
    position:sticky; right:0; z-index:3; box-shadow:-1px 0 0 var(--bs-border-color); }
/* default opaque background for every pinned cell (all row groups) */
.rp-scroll .tm-table td:first-child, .rp-scroll .tm-table td:last-child{ background:var(--bs-body-bg); }
/* header pinned cells sit above the body ones */
.rp-scroll .tm-table thead th:first-child, .rp-scroll .tm-table thead th:last-child{
    background:var(--bs-secondary-bg,#f6f7fb); z-index:4; }
/* opening (tbody) + closing (tfoot) highlighted rows */
.rp-scroll .tm-table tr.table-light td:first-child, .rp-scroll .tm-table tr.table-light td:last-child{
    background:var(--bs-secondary-bg,#eef1f6); }
/* keep pinned cells opaque on hover, else scrolled content shows through */
.rp-scroll .tm-table tbody tr:hover td:first-child, .rp-scroll .tm-table tbody tr:hover td:last-child{
    background:color-mix(in srgb, var(--bs-primary) 6%, var(--bs-body-bg)); }
</style>

<!-- ===== Period selector ===== -->
<div class="card tm-table-card mb-3">
    <div class="tm-table-head">
        <h3 class="tm-table-title"><i class="bi bi-journal-text"></i> Rokadh Parcha</h3>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('transactions/report/print') . '?' . $qs() ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</a>
            <a href="<?= site_url('transactions/report/export/pdf') . '?' . $qs() ?>" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
                <span class="badge rounded-pill text-bg-primary ms-1">New</span>
            </a>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="tx-flabel">Period</label>
                <select name="period" class="form-select form-select-sm" data-tx-period>
                    <?php foreach (['day' => 'Daily', 'month' => 'Monthly', 'quarter' => 'Quarterly', 'fy' => 'Financial Year', 'custom' => 'Custom Range'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= $p->period === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-6 col-md-3 tx-pf tx-pf-day" <?= $p->period === 'day' ? '' : 'hidden' ?>>
                <label class="tx-flabel">Date</label>
                <input type="date" name="date" value="<?= esc($in['date'] ?: date('Y-m-d')) ?>" class="form-control form-control-sm">
            </div>

            <div class="col-6 col-md-2 tx-pf tx-pf-month" <?= $p->period === 'month' ? '' : 'hidden' ?>>
                <label class="tx-flabel">Month</label>
                <select name="month" class="form-select form-select-sm">
                    <?php for ($m = 1; $m <= 12; $m++): $sel = (int) ($in['month'] ?: date('n')) === $m; ?>
                        <option value="<?= $m ?>" <?= $sel ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 tx-pf tx-pf-month" <?= $p->period === 'month' ? '' : 'hidden' ?>>
                <label class="tx-flabel">Year</label>
                <input type="number" name="year" value="<?= esc($in['year'] ?: date('Y')) ?>" class="form-control form-control-sm" min="2000" max="2100">
            </div>

            <div class="col-6 col-md-2 tx-pf tx-pf-quarter" <?= $p->period === 'quarter' ? '' : 'hidden' ?>>
                <label class="tx-flabel">Quarter</label>
                <select name="quarter" class="form-select form-select-sm">
                    <?php foreach (['1' => 'Q1 (Apr-Jun)', '2' => 'Q2 (Jul-Sep)', '3' => 'Q3 (Oct-Dec)', '4' => 'Q4 (Jan-Mar)'] as $val => $lbl): ?>
                        <option value="<?= $val ?>" <?= (string) ($in['quarter'] ?: '1') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2 tx-pf tx-pf-quarter tx-pf-fy" <?= in_array($p->period, ['quarter', 'fy'], true) ? '' : 'hidden' ?>>
                <label class="tx-flabel">FY (start yr)</label>
                <input type="number" name="fy" value="<?= esc($in['fy'] ?: $curFy) ?>" class="form-control form-control-sm" min="2000" max="2100">
            </div>

            <div class="col-6 col-md-2 tx-pf tx-pf-custom" <?= $p->period === 'custom' ? '' : 'hidden' ?>>
                <label class="tx-flabel">From</label>
                <input type="date" name="from" value="<?= esc($in['from'] ?: date('Y-m-01')) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-2 tx-pf tx-pf-custom" <?= $p->period === 'custom' ? '' : 'hidden' ?>>
                <label class="tx-flabel">To</label>
                <input type="date" name="to" value="<?= esc($in['to'] ?: date('Y-m-d')) ?>" class="form-control form-control-sm">
            </div>

            <div class="col-12 col-md-auto">
                <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-repeat"></i> Generate</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== Balance summary ===== -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3"><div class="tx-summary tx-sum-balance"><div class="tx-sum-ic"><i class="bi bi-box-arrow-in-down"></i></div><div><div class="tx-sum-lbl">Opening Balance</div><div class="tx-sum-val tx-bal">&#8377; <?= $fmt($opening) ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="tx-summary tx-sum-jama"><div class="tx-sum-ic"><i class="bi bi-arrow-up-right"></i></div><div><div class="tx-sum-lbl">Total Jama</div><div class="tx-sum-val tx-amt-jama">&#8377; <?= $fmt($totalJama) ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="tx-summary tx-sum-naam"><div class="tx-sum-ic"><i class="bi bi-arrow-down-left"></i></div><div><div class="tx-sum-lbl">Total Naam</div><div class="tx-sum-val tx-amt-naam">&#8377; <?= $fmt($totalNaam) ?></div></div></div></div>
    <div class="col-6 col-lg-3"><div class="tx-summary tx-sum-closing"><div class="tx-sum-ic"><i class="bi bi-safe2"></i></div><div><div class="tx-sum-lbl">Closing Balance</div><div class="tx-sum-val tx-closing">&#8377; <?= $fmt($closing) ?></div></div></div></div>
</div>

<!-- ===== Cash-book table ===== -->
<div class="card tm-table-card">
    <div class="tm-table-head">
        <h3 class="tm-table-title"><i class="bi bi-table"></i> <?= esc($p->label) ?></h3>
        <span class="tx-mode"><i class="bi bi-arrow-right-circle"></i> Carry forward: &#8377; <?= $fmt($carry) ?></span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive rp-scroll">
            <table class="table align-middle mb-0 tm-table">
                <thead><tr>
                    <th>#</th><th>Date</th><th>Txn No</th><th>Party</th><th>Party Type</th><th>Type</th>
                    <th>Mode</th><th>Status</th><th>Source</th><th>Added By</th><th>Entry</th><th>Remarks</th>
                    <th class="text-end">Jama (In)</th><th class="text-end">Naam (Out)</th><th class="text-end">Balance</th>
                </tr></thead>
                <tbody>
                    <tr class="table-light fw-semibold">
                        <td></td><td colspan="11">Opening Balance</td>
                        <td class="text-end">—</td><td class="text-end">—</td>
                        <td class="text-end tx-bal">&#8377; <?= $fmt($opening) ?></td>
                    </tr>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="15" class="text-center text-secondary py-4">No transactions in this period.</td></tr>
                    <?php else: $i = 1; foreach ($rows as $r):
                        $rc  = (int) ($r['restore_count'] ?? 0);
                        $who = $authors[(int) ($r['user_id'] ?? 0)] ?? 'Unknown';
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td class="text-nowrap"><?= esc(date('d M Y', strtotime($r['txn_date']))) ?></td>
                            <td><span class="tx-no"><?= esc($r['txn_no']) ?></span></td>
                            <td class="fw-semibold"><?= esc($r['name']) ?></td>
                            <td><?= ! empty($r['party_type']) ? '<span class="rp-chip rp-chip-party">' . esc($r['party_type']) . '</span>' : '<span class="text-secondary">—</span>' ?></td>
                            <td><?= $typeBadge($r['type']) ?></td>
                            <td class="small"><?= esc($modeLabels[$r['payment_mode']] ?? ucfirst((string) $r['payment_mode'])) ?></td>
                            <td><span class="tx-status <?= $statusCls[$r['status']] ?? 'tx-cancelled' ?>"><?= esc(ucfirst((string) $r['status'])) ?></span></td>
                            <td><?= $srcBadge($r['source'] ?? 'web') ?></td>
                            <td class="small text-nowrap">
                                <div class="fw-semibold"><?= esc($who) ?></div>
                                <?php if (! empty($r['created_at'])): ?><div class="text-secondary" style="font-size:.72rem"><?= esc(date('d M Y, h:i A', strtotime($r['created_at']))) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($rc > 0): ?>
                                    <span class="rp-info-restored" title="Deleted &amp; restored <?= $rc ?> time<?= $rc === 1 ? '' : 's' ?><?= ! empty($r['restored_at']) ? ', last on ' . esc(date('d M Y, h:i A', strtotime($r['restored_at'])), 'attr') : '' ?>"><i class="bi bi-arrow-counterclockwise"></i> Restored <?= $rc ?>&times;</span>
                                <?php else: ?>
                                    <span class="rp-info-fresh"><i class="bi bi-stars"></i> Fresh</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-secondary"><?= ! empty($r['notes']) ? esc(character_limiter($r['notes'], 40)) : '—' ?></td>
                            <td class="text-end tx-amt-jama"><?= $r['type'] === 'jama' ? '&#8377; ' . $fmt($r['amount']) : '' ?></td>
                            <td class="text-end tx-amt-naam"><?= $r['type'] === 'naam' ? '&#8377; ' . $fmt($r['amount']) : '' ?></td>
                            <td class="text-end fw-semibold tx-bal">&#8377; <?= $fmt($r['balance']) ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td></td><td colspan="11">Closing Balance</td>
                        <td class="text-end tx-amt-jama">&#8377; <?= $fmt($totalJama) ?></td>
                        <td class="text-end tx-amt-naam">&#8377; <?= $fmt($totalNaam) ?></td>
                        <td class="text-end tx-closing">&#8377; <?= $fmt($closing) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script>
function txPeriod(v) {
    document.querySelectorAll('.tx-pf').forEach(function (el) { el.hidden = true; });
    document.querySelectorAll('.tx-pf-' + v).forEach(function (el) { el.hidden = false; });
}
// Wire the period selector without an inline on* attribute (CSP-clean).
document.querySelectorAll('[data-tx-period]').forEach(function (sel) {
    sel.addEventListener('change', function () { txPeriod(this.value); });
});
</script>
