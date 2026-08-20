<?php
/** Company recycle bin — restore or permanently delete soft-deleted companies. */
$dmy = fn ($d) => $d ? date('d-m-Y H:i', strtotime($d)) : '—';
?>
<div class="row justify-content-center">
    <div class="col-xl-10">
        <div class="card shadow-sm">
            <div class="card-header">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <i class="bi bi-trash3 fs-5 text-danger"></i>
                    <h3 class="card-title mb-0">Deleted Companies</h3>
                    <a href="<?= site_url('settings') ?>" class="btn btn-outline-secondary btn-sm ms-auto"><i class="bi bi-arrow-left me-1"></i>Back to Settings</a>
                </div>
                <small class="text-muted d-block mt-1">Restore a company, or delete it forever. Permanent deletion cannot be undone.</small>
            </div>

            <div class="card-body">
                <?php if ($m = session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger"><?= esc($m) ?></div>
                <?php endif; ?>

                <?php if (empty($rows)): ?>
                    <div class="text-center text-secondary py-5">
                        <i class="bi bi-trash3 d-block fs-1 opacity-50 mb-2"></i>
                        Trash is empty. Deleted companies will appear here.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Company / Firm</th>
                                    <th>State</th>
                                    <th>Deleted at</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rows as $c): ?>
                                    <tr>
                                        <td>
                                            <strong><?= esc($c['name']) ?></strong>
                                            <?php if (! empty($c['gst_number'])): ?><div class="small text-muted"><?= esc($c['gst_number']) ?></div><?php endif; ?>
                                        </td>
                                        <td><?= esc($c['state'] ?: '—') ?></td>
                                        <td><?= esc($dmy($c['deleted_at'] ?? null)) ?></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-2">
                                                <form action="<?= site_url('company/restore/' . (int) $c['id']) ?>" method="post" class="d-inline" data-no-validate
                                                      data-confirm="Restore &ldquo;<?= esc($c['name'], 'attr') ?>&rdquo; back to your companies?"
                                                      data-confirm-title="Restore company?"
                                                      data-confirm-btn="Yes, restore"
                                                      data-confirm-icon="info">
                                                    <?= csrf_field() ?>
                                                    <button class="btn btn-sm btn-outline-success" type="submit"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                                                </form>
                                                <form action="<?= site_url('company/force-delete/' . (int) $c['id']) ?>" method="post" class="d-inline" data-no-validate id="purgeForm<?= (int) $c['id'] ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="confirm_name" value="">
                                                    <button class="btn btn-sm btn-outline-danger js-purge" type="button"
                                                            data-name="<?= esc($c['name'], 'attr') ?>"
                                                            data-form="purgeForm<?= (int) $c['id'] ?>"><i class="bi bi-x-octagon"></i> Delete forever</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="alert alert-warning mt-2 mb-0 small">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        <strong>Warning:</strong> “Delete forever” removes the company and every record inside it. Recovery is not possible after this deletion.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Permanent company delete requires typing the exact firm name — a final,
// deliberate confirmation for an irreversible action. The server re-checks it too.
document.addEventListener('click', function (ev) {
    var btn = ev.target.closest('.js-purge');
    if (!btn) return;
    ev.preventDefault();
    var expected = btn.getAttribute('data-name') || '';
    var typed = window.prompt(
        'This permanently deletes "' + expected + '" and ALL of its data (entries, accounts, attachments).\n' +
        'This CANNOT be undone.\n\nType the company name exactly to confirm:'
    );
    if (typed === null) return; // cancelled
    if (typed.trim().toLowerCase() !== expected.trim().toLowerCase()) {
        alert('The name you typed did not match "' + expected + '". Deletion cancelled.');
        return;
    }
    var form = document.getElementById(btn.getAttribute('data-form'));
    if (!form) return;
    form.querySelector('input[name="confirm_name"]').value = typed;
    form.submit();
});
</script>
