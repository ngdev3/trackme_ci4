<?php
/** Super Admin — Trash: soft-deleted customers (restore or delete forever). In layout.php. */
$rows = $rows ?? [];
$ago  = static function ($ts): string {
    if (! $ts) { return ''; }
    $d = time() - strtotime((string) $ts);
    if ($d < 3600)    { return floor($d / 60) . 'm ago'; }
    if ($d < 86400)   { return floor($d / 3600) . 'h ago'; }
    if ($d < 2592000) { return floor($d / 86400) . 'd ago'; }
    return floor($d / 2592000) . ' mo ago';
};
?>
<div class="cust-page">
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Deleted Customers</h4>
            <p class="cust-subtitle">Soft-deleted customer accounts — restore them, or delete forever to erase all data.</p>
        </div>
        <div class="cust-hero-actions">
            <a href="<?= site_url('admin/customers') ?>" class="cust-btn cust-btn-ghost"><i class="bi bi-arrow-left"></i> Back to Customers</a>
        </div>
    </section>
    <section class="cust-panel cust-table-panel">
        <div class="cust-toolbar">
            <div>
                <h5 class="cust-table-title">Trash</h5>
                <p class="cust-table-note">Restoring reactivates the customer and their firms; deleting forever cannot be undone.</p>
            </div>
            <span class="cust-total-tag"><i class="bi bi-trash3"></i> <?= number_format(count($rows)) ?> in trash</span>
        </div>
        <div class="erp-tbl-wrap">
            <table class="erp-tbl auto">
                <thead><tr>
                    <th class="text-start">ID</th>
                    <th class="text-start">Customer</th>
                    <th class="text-start">Email</th>
                    <th class="text-center">Firms</th>
                    <th class="text-start">Deleted</th>
                    <th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="6" class="erp-empty"><i class="bi bi-trash3"></i><div>Trash is empty. Deleted customers appear here and can be restored.</div></td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr>
                        <td class="text-start"><span class="erp-idchip">CUS-<?= str_pad((string) $r['id'], 4, '0', STR_PAD_LEFT) ?></span></td>
                        <td class="text-start">
                            <div class="erp-cellname">
                                <span class="erp-avatar"><?= esc(strtoupper(mb_substr((string) $r['name'], 0, 1) ?: '?')) ?></span>
                                <span class="erp-name-txt fw-semibold"><?= esc($r['name']) ?></span>
                            </div>
                        </td>
                        <td class="text-start"><span class="erp-truncate erp-muted" title="<?= esc($r['email'], 'attr') ?>"><?= esc($r['email']) ?></span></td>
                        <td class="text-center"><span class="erp-badge"><i class="bi bi-building"></i><?= (int) $r['firm_count'] ?></span></td>
                        <td class="text-start"><span class="erp-muted" title="<?= esc(date('d M Y, H:i', strtotime((string) $r['deleted_at'])), 'attr') ?>"><?= esc($ago($r['deleted_at'])) ?></span></td>
                        <td class="text-end">
                            <div class="erp-actions">
                                <form action="<?= site_url('admin/customers/restore/' . $r['id']) ?>" method="post" class="d-inline m-0" data-no-validate
                                      data-confirm="Reactivate this customer and their firms?" data-confirm-title="Restore “<?= esc($r['name'], 'attr') ?>”?" data-confirm-btn="Restore" data-confirm-icon="info">
                                    <?= csrf_field() ?>
                                    <button class="erp-act green" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                                <button type="button" class="erp-act red" title="Delete forever"
                                        data-bs-toggle="modal" data-bs-target="#purgeModal"
                                        data-id="<?= esc($r['id'], 'attr') ?>" data-name="<?= esc($r['name'], 'attr') ?>"
                                        data-firms="<?= (int) $r['firm_count'] ?>"><i class="bi bi-trash3-fill"></i></button>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Permanent-delete modal (type-to-confirm) -->
<div class="modal fade" id="purgeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="purgeForm" method="post" data-no-validate>
            <?= csrf_field() ?>
            <div class="modal-header" style="background:#fdecec">
                <h5 class="modal-title" style="color:#c53030"><i class="bi bi-exclamation-octagon-fill me-1"></i> Delete forever</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">This <strong>permanently erases</strong> <strong id="purgeName">this customer</strong> and <strong>everything</strong> tied to the account — <strong id="purgeFirms">0</strong> firm(s), all transactions, subscriptions, payments and logs. <span style="color:#c53030;font-weight:700">This cannot be undone and cannot be restored.</span></p>
                <label class="form-label">Type the account name <code id="purgeExpect" class="text-danger"></code> to confirm</label>
                <input type="text" name="confirm_name" id="purgeConfirm" class="form-control" autocomplete="off" placeholder="Exact account name">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger" id="purgeSubmit" disabled><i class="bi bi-trash3-fill me-1"></i> Delete forever</button>
            </div>
        </form>
    </div>
</div>
<script nonce="{csp-script-nonce}">
(function () {
    var modal = document.getElementById('purgeModal');
    if (!modal) return;
    var form = document.getElementById('purgeForm'), input = document.getElementById('purgeConfirm'), submit = document.getElementById('purgeSubmit');
    var expected = '';
    function norm(s) { return (s || '').trim().toLowerCase(); }
    function sync() { submit.disabled = norm(input.value) === '' || norm(input.value) !== norm(expected); }
    modal.addEventListener('show.bs.modal', function (ev) {
        var b = ev.relatedTarget; if (!b) return;
        expected = b.getAttribute('data-name') || '';
        form.setAttribute('action', '<?= site_url('admin/customers/purge') ?>/' + b.getAttribute('data-id'));
        document.getElementById('purgeName').textContent = expected;
        document.getElementById('purgeExpect').textContent = expected;
        document.getElementById('purgeFirms').textContent = b.getAttribute('data-firms') || '0';
        input.value = ''; sync();
    });
    modal.addEventListener('shown.bs.modal', function () { input.focus(); });
    input.addEventListener('input', sync);
    form.addEventListener('submit', function (e) { if (submit.disabled) { e.preventDefault(); return; } submit.disabled = true; submit.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Deleting…'; });
})();
</script>
