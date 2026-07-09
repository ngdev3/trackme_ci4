<?php
/** Entry details fragment — injected into #txEntryModal .modal-content via AJAX. */
use App\Models\TransactionModel;

$isJama   = $row['type'] === 'jama';
$modeLbl  = TransactionModel::MODE_LABELS[$row['payment_mode']] ?? ucfirst((string) $row['payment_mode']);
$statusCls = ['paid' => 'tx-paid', 'pending' => 'tx-pending', 'overdue' => 'tx-overdue', 'cancelled' => 'tx-cancelled', 'draft' => 'tx-draft'][$row['status']] ?? 'tx-cancelled';
$srcBadge = $row['source'] === 'app'
    ? '<span class="rp-badge rp-badge-app"><i class="bi bi-phone"></i> App entry</span>'
    : '<span class="rp-badge rp-badge-web"><i class="bi bi-display"></i> Web entry</span>';
$kindIcon = ['image' => 'bi-file-image', 'pdf' => 'bi-file-earmark-pdf', 'audio' => 'bi-file-music', 'video' => 'bi-file-play', 'doc' => 'bi-file-earmark-word', 'sheet' => 'bi-file-earmark-excel', 'file' => 'bi-file-earmark'];
$human = function ($b) { $b = (int) $b; if ($b < 1024) return $b . ' B'; if ($b < 1048576) return round($b / 1024, 1) . ' KB'; return round($b / 1048576, 1) . ' MB'; };
?>
<div class="modal-header">
    <h5 class="modal-title d-flex align-items-center gap-2">
        <span class="tx-no">#<?= esc($row['txn_no']) ?></span>
        <span class="tx-type <?= $isJama ? 'tx-jama' : 'tx-naam' ?>"><i class="bi <?= $isJama ? 'bi-arrow-up-right' : 'bi-arrow-down-left' ?>"></i><?= $isJama ? 'Jama' : 'Naam' ?></span>
        <?= $srcBadge ?>
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
</div>
<div class="modal-body">
    <div class="tx-detail-amt <?= $isJama ? 'tx-amt-jama' : 'tx-amt-naam' ?>">
        <?= $isJama ? '+' : '−' ?> &#8377; <?= number_format((float) $row['amount'], 2) ?>
    </div>
    <table class="table tx-detail mb-0">
        <tr><th>Date</th><td><?= esc(date('d M Y', strtotime($row['txn_date']))) ?></td></tr>
        <tr><th>Party Name</th><td class="fw-semibold"><?= esc($row['name']) ?></td></tr>
        <tr><th>Payment Mode</th><td><?= esc($modeLbl) ?></td></tr>
        <tr><th>Status</th><td><span class="tx-status <?= $statusCls ?>"><?= esc(ucfirst($row['status'])) ?></span></td></tr>
        <tr><th>Source</th><td><?= $srcBadge ?></td></tr>
        <tr><th>Remarks</th><td><?= esc($row['notes'] ?: '—') ?></td></tr>
        <tr><th>Created</th><td><?= esc(date('d M Y, H:i', strtotime($row['created_at']))) ?></td></tr>
    </table>

    <!-- Attachments -->
    <?php if (! empty($attachments)): ?>
        <h6 class="mt-3 mb-2"><i class="bi bi-paperclip"></i> Attachments <span class="badge text-bg-secondary"><?= count($attachments) ?></span></h6>
        <div class="tx-att-grid">
            <?php foreach ($attachments as $a):
                $preview  = site_url('transactions/file/' . hid($a['id']) . '/preview');
                $download = site_url('transactions/file/' . hid($a['id']) . '/download');
            ?>
                <div class="tx-att">
                    <a class="tx-att-thumb text-decoration-none" href="<?= $preview ?>" target="_blank" rel="noopener">
                        <?php if ($a['kind'] === 'image'): ?>
                            <img src="<?= $preview ?>" alt="<?= esc($a['original_name']) ?>">
                        <?php elseif ($a['kind'] === 'audio'): ?>
                            <i class="bi bi-file-music"></i>
                        <?php elseif ($a['kind'] === 'video'): ?>
                            <i class="bi bi-file-play"></i>
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
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="text-secondary small mt-3 mb-0"><i class="bi bi-paperclip"></i> No attachments.</p>
    <?php endif; ?>

    <!-- Reminder -->
    <?php if (can($moduleCode, 'edit')): ?>
        <h6 class="mt-3 mb-2"><i class="bi bi-alarm"></i> Reminder</h6>
        <?php if ($reminder): ?>
            <?php
                $rTs   = strtotime($reminder['snoozed_until'] ?: $reminder['remind_at']);
                $due   = $rTs <= time();
                $state = $reminder['status'] === 'completed' ? 'done' : ($due ? 'due' : 'upcoming');
            ?>
            <div class="alert <?= $state === 'due' ? 'alert-warning' : ($state === 'done' ? 'alert-success' : 'alert-info') ?> py-2 px-3 mb-2 d-flex align-items-center gap-2">
                <i class="bi <?= $state === 'due' ? 'bi-alarm-fill' : ($state === 'done' ? 'bi-check-circle' : 'bi-clock') ?>"></i>
                <div class="small">
                    <?= $state === 'due' ? 'Was due' : ($state === 'done' ? 'Completed' : 'Upcoming') ?>:
                    <strong><?= esc(date('d M Y, H:i', $rTs)) ?></strong>
                    <a href="<?= site_url('reminders/edit/' . $reminder['id']) ?>" class="ms-1">manage</a>
                </div>
            </div>
        <?php endif; ?>
        <form action="<?= site_url('transactions/reminder/' . hid($row['id'])) ?>" method="post" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-7">
                <label class="form-label small mb-1">Remind me at</label>
                <input type="datetime-local" name="remind_at" class="form-control form-control-sm" required
                       value="<?= $reminder ? esc(date('Y-m-d\TH:i', strtotime($reminder['remind_at']))) : '' ?>">
            </div>
            <div class="col-3">
                <label class="form-label small mb-1">Priority</label>
                <select name="priority" class="form-select form-select-sm">
                    <?php foreach (['low' => 'Low', 'medium' => 'Medium', 'high' => 'High'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($reminder['priority'] ?? 'medium') === $v ? 'selected' : '' ?>><?= $l ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-bell"></i></button>
            </div>
            <div class="col-12"><small class="text-secondary">When due, it appears in Reminders and Notifications.</small></div>
        </form>
    <?php endif; ?>
</div>
<div class="modal-footer justify-content-between">
    <div class="d-flex gap-2">
        <?php if (can($moduleCode, 'edit')): ?>
            <a href="<?= site_url('transactions/edit/' . hid($row['id'])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-pencil"></i> Edit</a>
        <?php endif; ?>
        <?php if (can($moduleCode, 'delete')): ?>
            <button type="button" class="btn btn-sm btn-outline-danger" data-tx-delete
                    data-action="<?= site_url('transactions/delete/' . hid($row['id'])) ?>" data-label="<?= esc($row['txn_no'], 'attr') ?>">
                <i class="bi bi-trash"></i> Delete
            </button>
        <?php endif; ?>
    </div>
    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Close</button>
</div>
