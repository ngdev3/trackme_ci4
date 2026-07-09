<?php
/** Ledger list grouped by accounting group. Rendered inside layout.php. */
$byGroup = [];
foreach ($rows as $r) {
    $byGroup[$r['group_name'] ?: 'Ungrouped'][] = $r;
}
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-journal-text me-1"></i> Ledgers</h3>
        <div class="d-flex gap-2">
            <a href="<?= site_url('accounting') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-diagram-3"></i> Groups</a>
            <a href="<?= site_url('accounting/ledgers/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Ledger</a>
        </div>
    </div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="text-center text-secondary py-5"><i class="bi bi-journal-text fs-1 d-block mb-2"></i>No ledgers yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead><tr><th>Ledger</th><th>Group</th><th class="text-end">Opening</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                    <?php foreach ($byGroup as $group => $items): ?>
                        <tr class="table-light"><td colspan="4" class="fw-bold small text-uppercase text-muted"><?= esc($group) ?></td></tr>
                        <?php foreach ($items as $l): ?>
                            <tr>
                                <td class="fw-semibold"><a href="<?= site_url('accounting/ledgers/statement/' . $l['id']) ?>" class="text-decoration-none"><?= esc($l['name']) ?></a></td>
                                <td><small class="text-muted"><?= esc($l['group_name'] ?: '—') ?></small></td>
                                <td class="text-end"><?= esc(number_format((float) $l['opening_balance'], 2)) ?> <?= esc($l['opening_type']) ?></td>
                                <td class="text-end">
                                    <a href="<?= site_url('accounting/ledgers/statement/' . $l['id']) ?>" class="btn btn-sm btn-outline-secondary" title="Statement"><i class="bi bi-file-text"></i></a>
                                    <?php if ($canEdit): ?>
                                        <a href="<?= site_url('accounting/ledgers/edit/' . $l['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                                        <form action="<?= site_url('accounting/ledgers/delete/' . $l['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Delete this ledger?');">
                                            <?= csrf_field() ?>
                                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
