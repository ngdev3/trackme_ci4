<?php
/** Super Admin — self-service account-deletion requests (app + web). Rendered in layout.php. */
$rows    = $rows ?? [];
$pending = $pending ?? 0;

$ago = static function ($ts): string {
    if (! $ts) { return ''; }
    $d = time() - strtotime((string) $ts);
    if ($d < 60)      { return 'just now'; }
    if ($d < 3600)    { return floor($d / 60) . 'm ago'; }
    if ($d < 86400)   { return floor($d / 3600) . 'h ago'; }
    if ($d < 2592000) { return floor($d / 86400) . 'd ago'; }
    return floor($d / 2592000) . ' mo ago';
};
$statusMap = [
    'pending'  => ['Pending', 'inactive'],
    'approved' => ['Deleted', 'delete'],
    'rejected' => ['Rejected', 'active'],
    'cancelled'=> ['Cancelled', 'inactive'],
];
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-person-x me-1"></i> Account Deletion Requests
            <?php if ($pending > 0): ?><span class="erp-pill red ms-1"><?= (int) $pending ?> pending</span><?php endif; ?>
        </h3>
    </div>
    <div class="card-body p-0">
        <div class="erp-tbl-wrap">
            <table class="erp-tbl auto">
                <thead><tr>
                    <th class="text-start">ID</th>
                    <th class="text-start">Requester</th>
                    <th class="text-start">Source</th>
                    <th class="text-start">Reason</th>
                    <th class="text-center">Status</th>
                    <th class="text-start">Requested</th>
                    <th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="7" class="erp-empty"><i class="bi bi-inbox"></i><div>No deletion requests.</div></td></tr>
                <?php else: foreach ($rows as $r):
                    [$sLabel, $sCls] = $statusMap[$r['status']] ?? [ucfirst((string) $r['status']), 'inactive'];
                    $tip = [
                        'type'   => 'Deletion request',
                        'icon'   => 'person-x',
                        'name'   => (string) $r['name'],
                        'accent' => 'red',
                        'chips'  => array_values(array_filter([
                            ['t' => $sLabel, 'ic' => 'flag', 'ok' => $r['status'] === 'pending'],
                            ['t' => $r['source'] === 'app' ? 'Mobile app' : 'Web portal', 'ic' => $r['source'] === 'app' ? 'phone' : 'display'],
                        ])),
                        'rows'   => array_values(array_filter([
                            ! empty($r['email'])  ? ['ic' => 'envelope', 'l' => 'Email', 'v' => (string) $r['email']] : null,
                            ! empty($r['mobile']) ? ['ic' => 'telephone', 'l' => 'Mobile', 'v' => (string) $r['mobile']] : null,
                            ! empty($r['reason']) ? ['ic' => 'chat-left-text', 'l' => 'Reason', 'v' => (string) $r['reason']] : null,
                            ! empty($r['admin_note']) ? ['ic' => 'sticky', 'l' => 'Admin note', 'v' => (string) $r['admin_note']] : null,
                        ])),
                        'foot'   => 'User #' . $r['user_id'] . ' · ' . date('d M Y, H:i', strtotime((string) $r['created_at'])),
                    ];
                ?>
                    <tr>
                        <td class="text-start"><span class="erp-idchip">DR-<?= str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                        <td class="text-start"><?= erp_cell_name((string) $r['name'], $tip) ?></td>
                        <td class="text-start"><span class="erp-badge"><i class="bi bi-<?= $r['source'] === 'app' ? 'phone' : 'display' ?>"></i><?= $r['source'] === 'app' ? 'App' : 'Web' ?></span></td>
                        <td class="text-start" style="max-width:280px"><span class="erp-truncate erp-muted" title="<?= esc((string) $r['reason'], 'attr') ?>"><?= esc($r['reason'] ?: '—') ?></span></td>
                        <td class="text-center"><span class="erp-status <?= $sCls ?>"><?= esc($sLabel) ?></span></td>
                        <td class="text-start"><span class="erp-muted" title="<?= esc(date('d M Y, H:i', strtotime((string) $r['created_at'])), 'attr') ?>"><?= esc($ago($r['created_at'])) ?></span></td>
                        <td class="text-end">
                            <?php if ($r['status'] === 'pending'): ?>
                                <div class="erp-actions">
                                    <button type="button" class="erp-act red" title="Approve & delete permanently"
                                            data-bs-toggle="modal" data-bs-target="#drApproveModal"
                                            data-id="<?= esc($r['id'], 'attr') ?>" data-name="<?= esc($r['name'], 'attr') ?>"><i class="bi bi-trash3"></i></button>
                                    <button type="button" class="erp-act slate" title="Reject (keep account)"
                                            data-bs-toggle="modal" data-bs-target="#drRejectModal"
                                            data-id="<?= esc($r['id'], 'attr') ?>" data-name="<?= esc($r['name'], 'attr') ?>"><i class="bi bi-x-lg"></i></button>
                                </div>
                            <?php else: ?>
                                <span class="erp-muted small"><?= $r['processed_at'] ? 'Actioned ' . esc($ago($r['processed_at'])) : '—' ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Approve (permanent delete) — type-to-confirm -->
<div class="modal fade" id="drApproveModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="drApproveForm" method="post" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header" style="background:#fdecec">
                <h5 class="modal-title" style="color:#c53030"><i class="bi bi-exclamation-octagon-fill me-1"></i> Approve &amp; delete permanently</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Approving this request will <strong>permanently delete</strong> <strong id="drApproveName"></strong> and <strong>every</strong> firm, transaction, subscription, payment and log tied to the account. <span style="color:#c53030;font-weight:700">This cannot be undone.</span></p>
                <label class="form-label">Type the account name <code id="drApproveExpect" class="text-danger"></code> to confirm</label>
                <input type="text" name="confirm_name" id="drApproveConfirm" class="form-control" autocomplete="off" placeholder="Exact account name">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="drApproveSubmit" disabled><i class="bi bi-trash3 me-1"></i> Delete permanently</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject (keep account) -->
<div class="modal fade" id="drRejectModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="drRejectForm" method="post" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-check me-1"></i> Reject request for <span id="drRejectName"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-secondary small mb-2">The account is kept. Optionally record why (shown in the request history).</p>
                <label class="form-label">Note <span class="text-muted">(optional)</span></label>
                <textarea name="admin_note" class="form-control" rows="3" maxlength="500" placeholder="e.g. Verified with the customer — they want to keep the account."></textarea>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Reject &amp; keep</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var appApprove = '<?= site_url('admin/deletion-requests/approve') ?>';
    var appReject  = '<?= site_url('admin/deletion-requests/reject') ?>';

    var aModal = document.getElementById('drApproveModal');
    if (aModal) {
        var aForm = document.getElementById('drApproveForm'), aIn = document.getElementById('drApproveConfirm'), aBtn = document.getElementById('drApproveSubmit'), aExp = '';
        function norm(s) { return (s || '').trim().toLowerCase(); }
        function aSync() { aBtn.disabled = norm(aIn.value) === '' || norm(aIn.value) !== norm(aExp); }
        aModal.addEventListener('show.bs.modal', function (ev) {
            var b = ev.relatedTarget; if (!b) return;
            aExp = b.getAttribute('data-name') || '';
            aForm.setAttribute('action', appApprove + '/' + b.getAttribute('data-id'));
            document.getElementById('drApproveName').textContent = aExp;
            document.getElementById('drApproveExpect').textContent = aExp;
            aIn.value = ''; aSync();
        });
        aModal.addEventListener('shown.bs.modal', function () { aIn.focus(); });
        aIn.addEventListener('input', aSync);
        aForm.addEventListener('submit', function (e) { if (aBtn.disabled) { e.preventDefault(); return; } aBtn.disabled = true; aBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting…'; });
    }
    var rModal = document.getElementById('drRejectModal');
    if (rModal) {
        var rForm = document.getElementById('drRejectForm');
        rModal.addEventListener('show.bs.modal', function (ev) {
            var b = ev.relatedTarget; if (!b) return;
            rForm.setAttribute('action', appReject + '/' + b.getAttribute('data-id'));
            document.getElementById('drRejectName').textContent = b.getAttribute('data-name') || '';
        });
    }
})();
</script>
