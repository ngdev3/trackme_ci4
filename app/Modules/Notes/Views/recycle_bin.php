<?php /** Soft-deleted notes — restore or permanently delete. Rendered inside layout.php. */ ?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-trash me-1"></i> Recycle Bin</h3>
        <a href="<?= site_url('notes') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Notes</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead><tr>
                    <th>Title</th><th>Deleted</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                <?php if (empty($rows)): ?>
                    <tr><td colspan="3" class="text-center text-secondary py-4">Recycle bin is empty.</td></tr>
                <?php else: foreach ($rows as $n): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold"><?= esc($n['title']) ?></div>
                            <small class="text-muted"><?= esc(character_limiter(strip_tags((string) $n['content']), 60)) ?></small>
                        </td>
                        <td><small><?= esc(date('d M Y, H:i', strtotime($n['deleted_at']))) ?></small></td>
                        <td class="text-end">
                            <form action="<?= site_url('notes/restore/' . $n['id']) ?>" method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-arrow-counterclockwise"></i> Restore</button>
                            </form>
                            <form action="<?= site_url('notes/force-delete/' . $n['id']) ?>" method="post" class="d-inline"
                                  data-no-validate data-confirm="This note will be permanently deleted. This cannot be undone." data-confirm-title="Delete forever?" data-confirm-btn="Delete permanently" data-confirm-icon="error">
                                <?= csrf_field() ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-circle"></i> Delete forever</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
