<?php /** Day book — voucher list. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-receipt me-1"></i> Day Book</h3>
        <div class="d-flex gap-2">
            <a href="<?= site_url('accounting/ledgers') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-journal-text"></i> Ledgers</a>
            <a href="<?= site_url('accounting/vouchers/create') ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> New Voucher</a>
        </div>
    </div>
    <div class="card-body border-bottom">
        <form class="row g-2 align-items-end" method="get">
            <div class="col-auto"><label class="form-label small mb-0">From</label><input type="date" name="from" value="<?= esc($from) ?>" class="form-control form-control-sm"></div>
            <div class="col-auto"><label class="form-label small mb-0">To</label><input type="date" name="to" value="<?= esc($to) ?>" class="form-control form-control-sm"></div>
            <div class="col-auto">
                <label class="form-label small mb-0">Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach (\App\Models\VoucherModel::TYPES as $t): ?>
                        <option value="<?= $t ?>" <?= $type === $t ? 'selected' : '' ?>><?= ucfirst($t) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-sm btn-outline-secondary">Filter</button></div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr><th>Date</th><th>No.</th><th>Type</th><th>Narration</th><th class="text-end">Amount</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="text-center text-secondary py-4">No vouchers found.</td></tr>
                <?php else: foreach ($rows as $v): ?>
                    <tr>
                        <td><?= esc(date('d-m-Y', strtotime($v['date']))) ?></td>
                        <td><code><?= esc($v['voucher_no']) ?></code></td>
                        <td><span class="badge text-bg-info text-capitalize"><?= esc($v['voucher_type']) ?></span></td>
                        <td><small><?= esc(character_limiter($v['narration'] ?? '', 50)) ?></small></td>
                        <td class="text-end fw-semibold"><?= esc(number_format((float) $v['amount'], 2)) ?></td>
                        <td class="text-end">
                            <a href="<?= site_url('accounting/vouchers/view/' . $v['id']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-eye"></i></a>
                            <?php if ($canEdit): ?>
                                <form action="<?= site_url('accounting/vouchers/delete/' . $v['id']) ?>" method="post" class="d-inline" data-no-validate data-confirm="This voucher will be deleted." data-confirm-title="Delete voucher?" data-confirm-btn="Yes, delete">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>
