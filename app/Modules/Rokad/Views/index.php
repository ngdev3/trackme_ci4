<?php
/** Rokad Parcha — daily cash book. Rendered inside layout.php. */
$editing = ! empty($editRow);
$fmt     = fn ($n) => number_format((float) $n, 2);
?>
<!-- Toolbar: date navigation + search -->
<div class="card mb-3">
    <div class="card-body d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div class="d-flex gap-1 align-items-center">
            <a href="<?= site_url('rokad?date=' . $prevDate) ?>" class="btn btn-sm btn-outline-secondary" title="Previous day"><i class="bi bi-chevron-left"></i></a>
            <form method="get" class="d-flex gap-1 align-items-center">
                <input type="date" name="date" value="<?= esc($date) ?>" class="form-control form-control-sm" style="width:auto" onchange="this.form.submit()">
            </form>
            <a href="<?= site_url('rokad?date=' . $nextDate) ?>" class="btn btn-sm btn-outline-secondary" title="Next day"><i class="bi bi-chevron-right"></i></a>
            <a href="<?= site_url('rokad') ?>" class="btn btn-sm btn-outline-secondary">Today</a>
            <span class="ms-2 fw-semibold"><?= esc(date('l, d M Y', strtotime($date))) ?></span>
        </div>
        <div class="d-flex gap-2">
            <form method="get" class="d-flex gap-1">
                <input type="search" name="q" class="form-control form-control-sm" placeholder="Search particulars...">
                <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            </form>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#openingModal"><i class="bi bi-wallet2"></i> Opening</button>
            <a href="<?= site_url('rokad/print?date=' . $date) ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer"></i> Print</a>
        </div>
    </div>
</div>

<!-- Daily summary -->
<div class="row g-3 mb-3">
    <?php foreach ([
        ['Opening Balance', $opening, 'bi-wallet2', 'secondary'],
        ['Total Jama (In)', $totalJama, 'bi-arrow-down-circle', 'success'],
        ['Total Naam (Out)', $totalNaam, 'bi-arrow-up-circle', 'danger'],
        ['Closing Balance', $closing, 'bi-cash-stack', 'primary'],
    ] as [$label, $val, $icon, $col]): ?>
        <div class="col-6 col-lg-3">
            <div class="card h-100 border-start border-4 border-<?= $col ?>">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><?= esc($label) ?></span>
                        <i class="bi <?= $icon ?> text-<?= $col ?>"></i>
                    </div>
                    <div class="fs-4 fw-bold">&#8377; <?= $fmt($val) ?></div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Cash book table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-journal-text me-1"></i> Rokad Parcha &mdash; <?= esc(date('d-m-Y', strtotime($date))) ?></h3>
        <?php if ($canEdit && ! $carried): ?>
            <form action="<?= site_url('rokad/carry-forward') ?>" method="post" onsubmit="return confirm('Carry this day\'s closing balance forward to the next day?');">
                <?= csrf_field() ?>
                <input type="hidden" name="date" value="<?= esc($date) ?>">
                <button class="btn btn-sm btn-primary"><i class="bi bi-arrow-right-circle"></i> Carry Forward</button>
            </form>
        <?php elseif ($carried): ?>
            <span class="badge text-bg-success"><i class="bi bi-check-circle"></i> Carried forward</span>
        <?php endif; ?>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th style="width:60px">#</th><th>Particular</th>
                    <th class="text-end">Jama (In)</th><th class="text-end">Naam (Out)</th>
                    <th class="text-end">Balance</th><th>Remarks</th>
                    <?php if ($canEdit): ?><th class="text-end">Actions</th><?php endif; ?>
                </tr></thead>
                <tbody>
                    <tr class="table-light">
                        <td></td><td class="fw-semibold">Opening Balance</td>
                        <td></td><td></td>
                        <td class="text-end fw-semibold">&#8377; <?= $fmt($opening) ?></td>
                        <td colspan="<?= $canEdit ? 2 : 1 ?>"></td>
                    </tr>
                    <?php if (empty($entries)): ?>
                        <tr><td colspan="<?= $canEdit ? 7 : 6 ?>" class="text-center text-secondary py-3">No transactions on this day. Add one below.</td></tr>
                    <?php else: $i = 1; foreach ($entries as $e): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td class="fw-semibold"><?= esc($e['particular']) ?></td>
                            <td class="text-end text-success"><?= (float) $e['jama'] > 0 ? $fmt($e['jama']) : '' ?></td>
                            <td class="text-end text-danger"><?= (float) $e['naam'] > 0 ? $fmt($e['naam']) : '' ?></td>
                            <td class="text-end"><?= $fmt($e['balance']) ?></td>
                            <td><small class="text-muted"><?= esc($e['remarks'] ?: '') ?></small></td>
                            <?php if ($canEdit): ?>
                                <td class="text-end text-nowrap">
                                    <a href="<?= site_url('rokad?date=' . $date . '&edit=' . $e['id']) ?>" class="btn btn-sm btn-outline-primary py-0"><i class="bi bi-pencil"></i></a>
                                    <form action="<?= site_url('rokad/delete/' . $e['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this entry?');">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-sm btn-outline-danger py-0"><i class="bi bi-trash"></i></button>
                                    </form>
                                </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr class="table-light fw-bold">
                        <td></td><td>Closing Balance</td>
                        <td class="text-end text-success"><?= $fmt($totalJama) ?></td>
                        <td class="text-end text-danger"><?= $fmt($totalNaam) ?></td>
                        <td class="text-end">&#8377; <?= $fmt($closing) ?></td>
                        <td colspan="<?= $canEdit ? 2 : 1 ?>"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <?php if ($canEdit): ?>
        <!-- Add / edit entry -->
        <div class="card-footer">
            <form action="<?= $editing ? site_url('rokad/update/' . $editRow['id']) : site_url('rokad/store') ?>" method="post" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <input type="hidden" name="entry_date" value="<?= esc($editing ? $editRow['entry_date'] : $date) ?>">
                <div class="col-md-4">
                    <label class="form-label small mb-0">Particular <span class="text-danger">*</span></label>
                    <input type="text" name="particular" class="form-control form-control-sm" required value="<?= esc($editRow['particular'] ?? '') ?>" placeholder="e.g. Cash sale / Rent paid">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0 text-success">Jama (In)</label>
                    <input type="number" step="0.01" min="0" name="jama" class="form-control form-control-sm text-end" value="<?= esc($editRow ? ($editRow['jama'] > 0 ? $editRow['jama'] : '') : '') ?>" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0 text-danger">Naam (Out)</label>
                    <input type="number" step="0.01" min="0" name="naam" class="form-control form-control-sm text-end" value="<?= esc($editRow ? ($editRow['naam'] > 0 ? $editRow['naam'] : '') : '') ?>" placeholder="0.00">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-0">Remarks</label>
                    <input type="text" name="remarks" class="form-control form-control-sm" value="<?= esc($editRow['remarks'] ?? '') ?>" placeholder="Optional">
                </div>
                <div class="col-md-2 d-flex gap-1">
                    <button class="btn btn-sm btn-primary flex-grow-1" type="submit"><i class="bi bi-<?= $editing ? 'save' : 'plus-lg' ?>"></i> <?= $editing ? 'Update' : 'Add' ?></button>
                    <?php if ($editing): ?><a href="<?= site_url('rokad?date=' . $date) ?>" class="btn btn-sm btn-outline-secondary">Cancel</a><?php endif; ?>
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<!-- Opening balance modal -->
<?php if ($canEdit): ?>
<div class="modal fade" id="openingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="<?= site_url('rokad/opening') ?>" method="post">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Set Opening Balance</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <p class="text-muted small">The opening cash balance when your cash book starts. Every later day's opening is carried forward automatically.</p>
                <div class="mb-3">
                    <label class="form-label">As on date</label>
                    <input type="date" name="opening_date" class="form-control" value="<?= esc($baseOpening[1] ?: $date) ?>">
                </div>
                <div class="mb-0">
                    <label class="form-label">Opening Balance (&#8377;)</label>
                    <input type="number" step="0.01" name="opening_balance" class="form-control" value="<?= esc($baseOpening[0]) ?>">
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
</div>
<?php endif; ?>
