<?php /** Accounting overview + groups. Rendered inside layout.php. */ ?>
<div class="row g-3 mb-3">
    <?php foreach ([
        ['Groups', $stats['groups'], 'bi-diagram-3', 'primary', null],
        ['Ledgers', $stats['ledgers'], 'bi-journal-text', 'success', site_url('accounting/ledgers')],
        ['Vouchers', $stats['vouchers'], 'bi-receipt', 'info', site_url('accounting/vouchers')],
    ] as [$label, $val, $icon, $col, $link]): ?>
        <div class="col-12 col-md-4">
            <div class="card h-100 border-start border-4 border-<?= $col ?>">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted small"><?= esc($label) ?></span>
                        <i class="bi <?= $icon ?> text-<?= $col ?> fs-5"></i>
                    </div>
                    <div class="fs-3 fw-bold"><?= (int) $val ?></div>
                    <?php if ($link): ?><a href="<?= $link ?>" class="small stretched-link">Open →</a><?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-diagram-3 me-1"></i> Accounting Groups</h3>
        <div class="d-flex gap-2">
            <?php if ($canEdit): ?>
                <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#groupModal"><i class="bi bi-plus-lg"></i> Add Group</button>
            <?php endif; ?>
            <a href="<?= site_url('accounting/ledgers/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Ledger</a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Group</th><th>Nature</th><th>Ledgers</th><th class="text-end">Action</th></tr></thead>
                <tbody>
                <?php foreach ($groups as $g): ?>
                    <tr>
                        <td class="fw-semibold"><?= esc($g['name']) ?> <?php if (! (int) $g['is_default']): ?><span class="badge text-bg-light border">custom</span><?php endif; ?></td>
                        <td><span class="badge text-bg-secondary"><?= esc($g['nature']) ?></span></td>
                        <td><?= (int) ($ledgerCounts[$g['id']] ?? 0) ?></td>
                        <td class="text-end">
                            <?php if ($canEdit && ! (int) $g['is_default'] && (int) ($ledgerCounts[$g['id']] ?? 0) === 0): ?>
                                <form action="<?= site_url('accounting/groups/delete/' . $g['id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="This group will be deleted." data-confirm-title="Delete group?" data-confirm-btn="Yes, delete">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($canEdit): ?>
<div class="modal fade" id="groupModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" action="<?= site_url('accounting/groups/store') ?>" method="post">
            <?= csrf_field() ?>
            <div class="modal-header"><h5 class="modal-title">Add Accounting Group</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Group Name</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="mb-0">
                    <label class="form-label">Nature</label>
                    <select name="nature" class="form-select" required>
                        <?php foreach (['Assets', 'Liabilities', 'Income', 'Expenses'] as $n): ?>
                            <option value="<?= $n ?>"><?= $n ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">Add Group</button></div>
        </form>
    </div>
</div>
<?php endif; ?>
