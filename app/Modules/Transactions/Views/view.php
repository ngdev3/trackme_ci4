<?php
/** Transaction detail + attachments. Rendered inside layout.php. */
use App\Models\TransactionModel;

$isJama   = $row['type'] === 'jama';
$modeLbl  = TransactionModel::MODE_LABELS[$row['payment_mode']] ?? ucfirst((string) $row['payment_mode']);
$statusCls = ['paid' => 'tx-paid', 'pending' => 'tx-pending', 'overdue' => 'tx-overdue', 'cancelled' => 'tx-cancelled', 'draft' => 'tx-draft'][$row['status']] ?? 'tx-cancelled';
$kindIcon = ['image' => 'bi-file-image', 'pdf' => 'bi-file-earmark-pdf', 'audio' => 'bi-file-music', 'video' => 'bi-file-play', 'doc' => 'bi-file-earmark-word', 'sheet' => 'bi-file-earmark-excel', 'file' => 'bi-file-earmark'];
$human = function ($b) { $b = (int) $b; if ($b < 1024) return $b . ' B'; if ($b < 1048576) return round($b / 1024, 1) . ' KB'; return round($b / 1048576, 1) . ' MB'; };
?>
<div class="row justify-content-center g-3">
    <div class="col-lg-7">
        <div class="card tm-table-card">
            <div class="tm-table-head">
                <h3 class="tm-table-title"><i class="bi bi-receipt"></i> <span class="tx-no">#<?= esc($row['txn_no']) ?></span></h3>
                <a href="<?= site_url('transactions/list') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
            </div>
            <div class="card-body">
                <div class="tx-detail-amt <?= $isJama ? 'tx-amt-jama' : 'tx-amt-naam' ?>">
                    <?= $isJama ? '+' : '−' ?> &#8377; <?= number_format((float) $row['amount'], 2) ?>
                </div>
                <table class="table tx-detail">
                    <tr><th>Txn No</th><td class="fw-semibold"><?= esc($row['txn_no']) ?></td></tr>
                    <tr><th>Date</th><td><?= esc(date('d M Y', strtotime($row['txn_date']))) ?></td></tr>
                    <tr><th>Party Name</th><td class="fw-semibold"><?= esc($row['name']) ?></td></tr>
                    <tr><th>Type</th><td><span class="tx-type <?= $isJama ? 'tx-jama' : 'tx-naam' ?>"><i class="bi <?= $isJama ? 'bi-arrow-up-right' : 'bi-arrow-down-left' ?>"></i><?= $isJama ? 'Jama (Received)' : 'Naam (Paid)' ?></span></td></tr>
                    <tr><th>Payment Mode</th><td><?= esc($modeLbl) ?></td></tr>
                    <tr><th>Status</th><td><span class="tx-status <?= $statusCls ?>"><?= esc(ucfirst($row['status'])) ?></span></td></tr>
                    <tr><th>Remarks</th><td><?= esc($row['notes'] ?: '—') ?></td></tr>
                    <tr><th>Created</th><td><?= esc(date('d M Y, H:i', strtotime($row['created_at']))) ?></td></tr>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-end gap-2">
                <?php if (can($moduleCode, 'edit')): ?>
                    <a href="<?= site_url('transactions/edit/' . hid($row['id'])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil me-1"></i> Edit</a>
                <?php endif; ?>
                <?php if (can($moduleCode, 'delete')): ?>
                    <button type="button" class="btn btn-sm btn-outline-danger" data-tx-delete
                            data-action="<?= site_url('transactions/delete/' . hid($row['id'])) ?>" data-label="<?= esc($row['txn_no'], 'attr') ?>">
                        <i class="bi bi-trash me-1"></i> Delete
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Attachments -->
    <div class="col-lg-5">
        <div class="card tm-table-card h-100">
            <div class="tm-table-head">
                <h3 class="tm-table-title"><i class="bi bi-paperclip"></i> Attachments <span class="badge text-bg-secondary ms-1"><?= count($attachments) ?></span></h3>
            </div>
            <div class="card-body">
                <?php if (! sub_is_pro()): ?>
                    <p class="text-secondary small mb-0"><i class="bi bi-lock"></i> Viewing images &amp; attachments is available on the paid plan. <a href="<?= site_url('subscription') ?>">Upgrade</a>.</p>
                <?php else: ?>
                <?php if (can($moduleCode, 'edit')): ?>
                    <form action="<?= site_url('transactions/attach/' . hid($row['id'])) ?>" method="post" enctype="multipart/form-data" class="mb-3">
                        <?= csrf_field() ?>
                        <div class="input-group input-group-sm">
                            <input type="file" name="attachments[]" multiple class="form-control" required
                                   accept="image/*,audio/*,video/*,application/pdf,.doc,.docx,.xls,.xlsx,.csv,.txt">
                            <button class="btn btn-primary"><i class="bi bi-upload"></i> Add</button>
                        </div>
                        <div class="d-flex gap-2 mt-2 flex-wrap">
                            <label class="btn btn-sm btn-outline-secondary mb-0"><i class="bi bi-camera"></i>
                                <input type="file" name="attachments[]" accept="image/*" capture="environment" class="d-none" data-fresh-submit>
                            </label>
                            <label class="btn btn-sm btn-outline-secondary mb-0"><i class="bi bi-mic"></i>
                                <input type="file" name="attachments[]" accept="audio/*" capture class="d-none" data-fresh-submit>
                            </label>
                            <label class="btn btn-sm btn-outline-secondary mb-0"><i class="bi bi-camera-video"></i>
                                <input type="file" name="attachments[]" accept="video/*" capture="environment" class="d-none" data-fresh-submit>
                            </label>
                        </div>
                    </form>
                <?php endif; ?>

                <?php if (empty($attachments)): ?>
                    <p class="text-secondary text-center py-3 mb-0"><i class="bi bi-inbox d-block fs-3 opacity-50 mb-1"></i>No attachments yet.</p>
                <?php else: ?>
                    <div class="tx-att-grid">
                        <?php foreach ($attachments as $a):
                            $preview  = site_url('transactions/file/' . hid($a['id']) . '/preview');
                            $download = site_url('transactions/file/' . hid($a['id']) . '/download');
                        ?>
                            <div class="tx-att">
                                <a class="tx-att-thumb text-decoration-none" href="<?= $preview ?>" target="_blank" rel="noopener">
                                    <?php if ($a['kind'] === 'image'): ?>
                                        <img src="<?= $preview ?>" alt="<?= esc($a['original_name']) ?>">
                                    <?php else: ?>
                                        <i class="bi <?= $kindIcon[$a['kind']] ?? 'bi-file-earmark' ?>"></i>
                                    <?php endif; ?>
                                </a>
                                <div class="tx-att-body">
                                    <div class="tx-att-name"><?= esc($a['original_name']) ?></div>
                                    <div class="tx-att-meta"><?= esc(ucfirst($a['kind'])) ?> · <?= $human($a['size']) ?></div>
                                </div>
                                <div class="tx-att-actions">
                                    <a class="tx-chip-icn tx-kind-<?= esc($a['kind']) ?>" href="<?= $preview ?>" target="_blank" rel="noopener" title="Preview"><i class="bi bi-eye"></i></a>
                                    <a class="tx-chip-icn" href="<?= $download ?>" title="Download"><i class="bi bi-download"></i></a>
                                    <?php if (can($moduleCode, 'edit')): ?>
                                        <label class="tx-chip-icn mb-0" title="Replace">
                                            <i class="bi bi-arrow-repeat"></i>
                                            <form action="<?= site_url('transactions/file/' . hid($a['id']) . '/replace') ?>" method="post" enctype="multipart/form-data" class="d-none tx-replace-form">
                                                <?= csrf_field() ?>
                                                <input type="file" name="replacement" data-fresh-submit>
                                            </form>
                                        </label>
                                    <?php endif; ?>
                                    <?php if (can($moduleCode, 'delete')): ?>
                                        <form action="<?= site_url('transactions/file/' . hid($a['id']) . '/delete') ?>" method="post" data-no-validate data-confirm="This attachment will be deleted." data-confirm-title="Delete attachment?" data-confirm-btn="Yes, delete" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button class="tx-chip-icn tx-kind-pdf" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php endif; /* sub_is_pro */ ?>
            </div>
        </div>
    </div>
</div>

<script>
// "Replace": the label wraps a hidden form; clicking the label opens its file picker.
document.querySelectorAll('.tx-replace-form').forEach(function (form) {
    var label = form.closest('label');
    var input = form.querySelector('input[type=file]');
    label.addEventListener('click', function (e) { e.preventDefault(); input.click(); });
});
</script>

<?= view('Modules\Transactions\Views\_modals') ?>
