<?php
/** Deleted (soft-removed) Rokad Parcha entries for a date, with restore. In layout.php. */
$fmt = fn ($n) => number_format((float) $n, 2);
$dmy = fn ($d) => date('d-m-Y', strtotime($d));
?>
<div class="rp-wrap">
    <div class="card tm-table-card">
        <div class="tm-table-head">
            <h3 class="tm-table-title"><i class="bi bi-trash"></i> Deleted Entries — <?= esc($dmy($date)) ?></h3>
            <a href="<?= site_url('transactions/report') . '?period=day&date=' . esc($date) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Rokadh Parcha</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table align-middle mb-0 tm-table">
                    <thead><tr>
                        <th>Txn No</th><th>Party</th><th>Type</th><th>Mode</th>
                        <th class="text-end">Amount</th><th>Reason</th><th>Deleted At</th><th class="text-end">Action</th>
                    </tr></thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="8" class="text-center text-secondary py-4"><i class="bi bi-inbox fs-3 d-block opacity-50 mb-1"></i>No deleted entries for this date.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                        <tr>
                            <td><span class="tx-no"><?= esc($r['txn_no']) ?></span></td>
                            <td><?= esc($r['name']) ?></td>
                            <td><span class="tx-type <?= $r['type'] === 'jama' ? 'tx-jama' : 'tx-naam' ?>"><?= $r['type'] === 'jama' ? 'Jama' : 'Naam' ?></span></td>
                            <td class="text-capitalize small text-secondary"><?= esc($r['payment_mode']) ?></td>
                            <td class="text-end fw-semibold <?= $r['type'] === 'jama' ? 'tx-amt-jama' : 'tx-amt-naam' ?>">&#8377; <?= $fmt($r['amount']) ?></td>
                            <td class="small"><?= esc($r['delete_reason'] ?: '—') ?></td>
                            <td class="small text-secondary"><?= esc($r['deleted_at'] ? date('d M Y, H:i', strtotime($r['deleted_at'])) : '—') ?></td>
                            <td class="text-end">
                                <?php if (can($moduleCode, 'edit')): ?>
                                    <form action="<?= site_url('transactions/report/restore/' . hid($r['id'])) ?>" method="post" class="d-inline" onsubmit="return confirm('Restore this entry?');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
