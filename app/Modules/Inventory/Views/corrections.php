<?php
/** Correction requests — workers see status; owners/admins approve/reject. */
$fmt = static fn ($n) => number_format((float) $n, 0);
$badge = ['pending' => ['warning', 'Pending'], 'approved' => ['success', 'Approved'], 'rejected' => ['secondary', 'Rejected']];
?>
<div class="inv-corrections">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <h2 class="mb-0"><i class="bi bi-clipboard-check me-2"></i>Stock Corrections</h2>
        <div class="d-flex gap-2">
            <a href="<?= site_url('inventory/verify') ?>" class="btn inv-btn-in"><i class="bi bi-plus-circle me-1"></i>New Verification</a>
            <a href="<?= site_url('inventory') ?>" class="btn btn-light border"><i class="bi bi-arrow-left me-1"></i>Back</a>
        </div>
    </div>
    <?php if (empty($canApprove)): ?>
        <p class="text-secondary">Your submitted corrections are listed here. Stock changes only after an owner/admin approves.</p>
    <?php endif; ?>

    <?php if (empty($rows)): ?>
        <div class="inv-empty-card"><i class="bi bi-clipboard-check"></i><h3>No corrections yet</h3><p>Do a <a href="<?= site_url('inventory/verify') ?>">Physical Verification</a> to raise one.</p></div>
    <?php else: ?>
        <div class="inv-corr-list">
            <?php foreach ($rows as $c):
                $b = $badge[$c['status']] ?? ['light', ucfirst($c['status'])];
                $d = (float) $c['difference'];
            ?>
                <div class="inv-corr-card <?= esc($c['status']) ?>">
                    <div class="inv-corr-main">
                        <div class="inv-corr-title">
                            <strong><?= esc($c['product_name']) ?></strong>
                            <span class="text-secondary">· <?= esc($c['warehouse_name']) ?></span>
                            <span class="badge text-bg-<?= esc($b[0]) ?> ms-1"><?= esc($b[1]) ?></span>
                        </div>
                        <div class="inv-corr-nums">
                            <span>System <strong><?= $fmt($c['system_bags']) ?></strong></span>
                            <span>Physical <strong><?= $fmt($c['physical_bags']) ?></strong></span>
                            <span class="<?= $d > 0 ? 'text-success' : ($d < 0 ? 'text-danger' : '') ?>">Diff <strong><?= $d > 0 ? '+' : '' ?><?= $fmt($d) ?></strong></span>
                            <span class="inv-corr-reason"><?= esc($reasons[$c['reason']] ?? ucfirst((string) $c['reason'])) ?></span>
                        </div>
                        <div class="inv-corr-meta">
                            <?= esc($c['requested_name'] ?? 'User #' . $c['requested_by']) ?> · <?= esc(date('d M Y, H:i', strtotime($c['created_at']))) ?>
                            <?php if (! empty($c['note'])): ?> · “<?= esc($c['note']) ?>”<?php endif; ?>
                        </div>
                    </div>
                    <?php if ($c['status'] === 'pending' && ! empty($canApprove)): ?>
                        <div class="inv-corr-actions">
                            <form action="<?= site_url('inventory/corrections/approve/' . $c['id']) ?>" method="post"
                                  data-no-validate data-confirm="An adjustment entry will be created and stock reconciled." data-confirm-title="Approve correction?" data-confirm-btn="Yes, approve" data-confirm-icon="info">
                                <?= csrf_field() ?>
                                <button class="btn btn-success"><i class="bi bi-check2-circle me-1"></i>Approve</button>
                            </form>
                            <form action="<?= site_url('inventory/corrections/reject/' . $c['id']) ?>" method="post"
                                  data-no-validate data-confirm="Stock stays unchanged." data-confirm-title="Reject correction?" data-confirm-btn="Yes, reject">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger"><i class="bi bi-x-circle me-1"></i>Reject</button>
                            </form>
                        </div>
                    <?php elseif ($c['status'] !== 'pending'): ?>
                        <div class="inv-corr-actions text-secondary small">
                            <?= esc(ucfirst($c['status'])) ?><?= ! empty($c['reviewed_at']) ? ' · ' . esc(date('d M, H:i', strtotime($c['reviewed_at']))) : '' ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
