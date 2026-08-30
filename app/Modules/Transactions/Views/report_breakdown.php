<?php
/**
 * Report — Jama / Naam totals grouped by party type and payment mode over a date
 * range. Clicking any group row lists the entries behind it (drill-down), and each
 * of those rows opens the entry modal.
 */
use Modules\Transactions\Controllers\ReportController;
use App\Models\TransactionModel;

$dmy = fn ($d) => date('d-m-Y', strtotime($d));
// Every link (drill, print, export) carries the current range *and* filter.
$base = array_filter(['from' => $from, 'to' => $to, 'ptype' => $filters['ptype']], fn ($v) => $v !== '');
$qs   = fn (array $extra = []) => http_build_query(array_merge($base, $extra));
$NONE = TransactionModel::UNSET_VALUE;
$hasFilter = $filters['ptype'] !== '';
$typeBadge = fn ($t) => $t === 'jama'
    ? '<span class="tx-type tx-jama"><i class="bi bi-arrow-up-right"></i>Jama</span>'
    : '<span class="tx-type tx-naam"><i class="bi bi-arrow-down-left"></i>Naam</span>';
?>

<!-- ===== Range + actions ===== -->
<div class="card tm-table-card mb-3">
    <div class="tm-table-head">
        <h3 class="tm-table-title"><i class="bi bi-pie-chart"></i> Report</h3>
        <div class="d-flex flex-wrap gap-2">
            <a href="<?= site_url('transactions/report') ?>?period=day" class="btn btn-sm btn-outline-secondary"><i class="bi bi-journal-text"></i> Rokadh Parcha</a>
            <a href="<?= site_url('transactions/report/breakdown/print') . '?' . $qs() ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</a>
            <?php if (can($moduleCode, 'export')): ?>
                <a href="<?= site_url('transactions/report/breakdown/export/pdf') . '?' . $qs() ?>" class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-file-earmark-pdf me-1"></i>Export PDF
                    <span class="badge rounded-pill text-bg-primary ms-1">New</span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-6 col-md-3">
                <label class="tx-flabel">From</label>
                <input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm">
            </div>
            <div class="col-6 col-md-3">
                <label class="tx-flabel">To</label>
                <input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm">
            </div>
            <!-- Keeps the selected party-type tab when only the dates change. -->
            <input type="hidden" name="ptype" value="<?= esc($filters['ptype'], 'attr') ?>">
            <div class="col-12 col-md-auto">
                <button class="btn btn-sm btn-primary"><i class="bi bi-search"></i> Show</button>
                <a href="<?= site_url('transactions/report/breakdown') ?>" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- ===== Party type tabs: one per type present in this range ===== -->
    <?php
    $tabUrl = fn (string $v) => site_url('transactions/report/breakdown') . '?' . http_build_query(
        array_filter(['from' => $from, 'to' => $to, 'ptype' => $v], fn ($x) => $x !== '')
    );
    $totalCount = array_sum(array_column($tabs, 'count'));
    ?>
    <ul class="nav nav-tabs px-3 flex-nowrap overflow-auto">
        <li class="nav-item">
            <a class="nav-link text-nowrap <?= $filters['ptype'] === '' ? 'active' : '' ?>" href="<?= esc($tabUrl(''), 'attr') ?>">
                All party types <span class="badge rounded-pill text-bg-secondary"><?= number_format($totalCount) ?></span>
            </a>
        </li>
        <?php foreach ($tabs as $t):
            $val = $t['label'] === '' ? $NONE : $t['label'];
        ?>
            <li class="nav-item">
                <a class="nav-link text-nowrap <?= $filters['ptype'] === $val ? 'active' : '' ?>" href="<?= esc($tabUrl($val), 'attr') ?>">
                    <?php if ($t['label'] === ''): ?>
                        <span class="fst-italic text-secondary">Unspecified</span>
                    <?php else: ?>
                        <?= esc($t['label']) ?>
                    <?php endif; ?>
                    <span class="badge rounded-pill text-bg-secondary"><?= number_format($t['count']) ?></span>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <div class="card-body pt-3">
        <?php if ($hasFilter): ?>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-secondary small">Showing only entries where:</span>
                <span class="rp-chip rp-chip-party">Party type = <?= $filters['ptype'] === $NONE ? 'Unspecified' : esc($filters['ptype']) ?></span>
                <span class="text-secondary small">Every total below is limited to these entries.</span>
            </div>
        <?php else: ?>
            <span class="text-secondary small">Showing every entry from <?= esc($dmy($from)) ?> to <?= esc($dmy($to)) ?>. Pick a tab to narrow by party type.</span>
        <?php endif; ?>
    </div>
</div>

<!-- ===== Range totals ===== -->
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card tm-table-card h-100"><div class="card-body">
            <div class="tx-flabel">Total Jama (In)</div>
            <div class="fs-4 fw-bold tx-amt-jama"><?= money($summary['jama']) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card tm-table-card h-100"><div class="card-body">
            <div class="tx-flabel">Total Naam (Out)</div>
            <div class="fs-4 fw-bold tx-amt-naam"><?= money($summary['naam']) ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card tm-table-card h-100"><div class="card-body">
            <div class="tx-flabel">Net (<?= esc($dmy($from)) ?> &rarr; <?= esc($dmy($to)) ?>)</div>
            <div class="fs-4 fw-bold <?= $summary['net'] < 0 ? 'tx-amt-naam' : 'tx-amt-jama' ?>"><?= money($summary['net']) ?></div>
        </div></div>
    </div>
</div>

<!-- ===== The three groupings ===== -->
<div class="row g-3">
    <?php foreach (ReportController::GROUPS as $key => $meta): $rows = $groups[$key]; ?>
        <div class="col-xl-6">
            <div class="card tm-table-card h-100">
                <div class="tm-table-head"><h3 class="tm-table-title"><i class="bi <?= $meta['icon'] ?>"></i> <?= esc($meta['title']) ?></h3></div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0 tm-table">
                        <thead><tr>
                            <th><?= $key === 'party_type' ? 'Type' : 'Mode' ?></th>
                            <th class="text-end">Entries</th>
                            <th class="text-end">Jama</th>
                            <th class="text-end">Naam</th>
                            <th class="text-end">Net</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="5" class="text-center text-secondary py-4">Nothing recorded in this range.</td></tr>
                        <?php else: foreach ($rows as $g):
                            $isEmpty = $g['label'] === '';
                            $url = site_url('transactions/report/breakdown') . '?' . $qs(['group' => $key, 'value' => $g['label']]);
                            $on  = $drill && $drill['group'] === $key && $drill['value'] === $g['label'];
                        ?>
                            <tr class="<?= $on ? 'table-active' : '' ?>">
                                <td>
                                    <a href="<?= esc($url, 'attr') ?>" class="text-decoration-none">
                                        <?php if ($isEmpty): ?>
                                            <span class="text-secondary fst-italic"><?= esc($meta['empty']) ?></span>
                                        <?php else: ?>
                                            <?php if ($key === 'payment_mode'): ?>
                                                <?= esc(TransactionModel::MODE_LABELS[$g['label']] ?? ucfirst($g['label'])) ?>
                                            <?php else: ?>
                                                <span class="rp-chip rp-chip-party"><?= esc($g['label']) ?></span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </a>
                                </td>
                                <td class="text-end"><?= number_format($g['count']) ?></td>
                                <td class="text-end tx-amt-jama"><?= $g['jama'] ? money($g['jama']) : '—' ?></td>
                                <td class="text-end tx-amt-naam"><?= $g['naam'] ? money($g['naam']) : '—' ?></td>
                                <td class="text-end fw-semibold <?= $g['net'] < 0 ? 'tx-amt-naam' : '' ?>"><?= money($g['net']) ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- ===== Drill-down: the entries behind one group ===== -->
<?php if ($drill): ?>
    <div class="card tm-table-card mt-3">
        <div class="tm-table-head">
            <h3 class="tm-table-title">
                <i class="bi bi-list-ul"></i>
                <?= esc(ReportController::GROUPS[$drill['group']]['title']) ?>: <strong><?= esc($drill['label']) ?></strong>
                <span class="text-secondary fw-normal">&middot; <?= count($drill['rows']) ?> <?= count($drill['rows']) === 1 ? 'entry' : 'entries' ?></span>
            </h3>
            <a href="<?= site_url('transactions/report/breakdown') . '?' . $qs() ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> Clear</a>
        </div>
        <div class="table-responsive">
            <table class="table align-middle mb-0 tm-table">
                <thead><tr>
                    <th>Date</th><th>Txn No</th><th>Party</th><th>Type</th><th>Mode</th><th class="text-end">Amount</th>
                </tr></thead>
                <tbody>
                <?php if (empty($drill['rows'])): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">No entries.</td></tr>
                <?php else: foreach ($drill['rows'] as $r): ?>
                    <tr class="tx-row-view" data-tx-view data-id="<?= hid($r['id']) ?>" title="Click to view full details">
                        <td class="text-nowrap"><?= esc(fmt_date($r['txn_date'])) ?></td>
                        <td><span class="tx-no"><?= esc($r['txn_no'] ?: '—') ?></span></td>
                        <td class="fw-semibold">
                            <?= esc($r['name']) ?>
                            <?php if (! empty($r['party_type'])): ?><span class="rp-chip rp-chip-party ms-1"><?= esc($r['party_type']) ?></span><?php endif; ?>
                        </td>
                        <td><?= $typeBadge($r['type']) ?></td>
                        <td class="small text-secondary"><?= esc(TransactionModel::MODE_LABELS[$r['payment_mode']] ?? ucfirst((string) $r['payment_mode'])) ?></td>
                        <td class="text-end fw-semibold <?= $r['type'] === 'jama' ? 'tx-amt-jama' : 'tx-amt-naam' ?>"><?= money($r['amount']) ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?= view('Modules\Transactions\Views\_modals') ?>
    <style nonce="{csp-style-nonce}">.tx-row-view { cursor: pointer; }</style>
<?php endif; ?>
