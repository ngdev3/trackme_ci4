<?php
$badgeMap = [
    'success' => 'text-bg-success',
    'error' => 'text-bg-danger',
    'warning' => 'text-bg-warning',
    'info' => 'text-bg-info',
    'system_update' => 'text-bg-primary',
    'user_activity' => 'text-bg-secondary',
];
?>
<div class="card">
    <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <h3 class="card-title mb-0"><i class="bi bi-bell me-1"></i> Notifications</h3>
        <form class="d-flex flex-wrap gap-2 align-items-center" method="get">
            <input type="search" name="q" value="<?= esc($search) ?>" class="form-control form-control-sm" placeholder="Search notifications...">
            <select name="type" class="form-select form-select-sm">
                <option value="">All types</option>
                <?php foreach ($types as $t): ?>
                    <option value="<?= esc($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= esc(ucwords(str_replace('_', ' ', $t))) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="form-select form-select-sm">
                <option value="">All status</option>
                <option value="unread" <?= $status === 'unread' ? 'selected' : '' ?>>Unread</option>
                <option value="read" <?= $status === 'read' ? 'selected' : '' ?>>Read</option>
            </select>
            <input type="search" name="module" value="<?= esc($module) ?>" class="form-control form-control-sm" placeholder="Module">
            <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
            <a href="<?= site_url('notifications') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i></a>
        </form>
    </div>
    <div class="card-body p-0">
        <form method="post" id="notificationBulkForm">
            <?= csrf_field() ?>
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center p-3 border-bottom">
                <div class="d-flex gap-2">
                    <button type="submit" formaction="<?= site_url('notifications/mark-read') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-check2-circle me-1"></i> Mark selected read
                    </button>
                    <button type="submit" formaction="<?= site_url('notifications/delete') ?>" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash me-1"></i> Delete selected
                    </button>
                </div>
                <button type="submit" formaction="<?= site_url('notifications/mark-all-read') ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-check-all me-1"></i> Mark all as read
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width:42px"><input type="checkbox" data-check-all-notifications></th>
                        <th>Notification</th>
                        <th>Type</th>
                        <th>Module</th>
                        <th>User/Role</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-end">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($rows)): ?>
                        <tr><td colspan="9" class="text-center text-secondary py-4">No notifications found.</td></tr>
                    <?php else: foreach ($rows as $r): ?>
                        <tr class="<?= empty($r['is_read']) ? 'table-primary' : '' ?>">
                            <td><input type="checkbox" name="ids[]" value="<?= esc($r['id']) ?>"></td>
                            <td>
                                <strong><?= esc($r['title']) ?></strong>
                                <small class="d-block text-secondary"><?= esc($r['message']) ?></small>
                            </td>
                            <td><span class="badge <?= esc($badgeMap[$r['type']] ?? 'text-bg-light') ?>"><?= esc(ucwords(str_replace('_', ' ', $r['type']))) ?></span></td>
                            <td><span class="badge text-bg-light border"><?= esc($r['module'] ?: 'Global') ?></span></td>
                            <td><small><?= esc($r['user_name'] ?? ($r['role_name'] ?? 'Broadcast')) ?></small></td>
                            <td><span class="badge text-bg-<?= $r['priority'] === 'critical' ? 'danger' : ($r['priority'] === 'high' ? 'warning' : 'secondary') ?>"><?= esc($r['priority']) ?></span></td>
                            <td><?= empty($r['is_read']) ? '<span class="badge text-bg-primary">Unread</span>' : '<span class="badge text-bg-light border">Read</span>' ?></td>
                            <td><small><?= esc(date('d M Y, H:i', strtotime($r['created_at']))) ?></small></td>
                            <td class="text-end">
                                <?php if (! empty($r['action_url'])): ?>
                                    <a href="<?= esc($r['action_url']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a>
                                <?php endif; ?>
                                <?php if (empty($r['is_read'])): ?>
                                    <button type="submit" name="id" value="<?= esc($r['id']) ?>" formaction="<?= site_url('notifications/mark-read') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-check2"></i></button>
                                <?php endif; ?>
                                <button type="submit" name="id" value="<?= esc($r['id']) ?>" formaction="<?= site_url('notifications/delete') ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
    <?php if (isset($pager)): ?><div class="card-footer d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</div>

<script>
document.addEventListener('change', function (e) {
    if (!e.target.matches('[data-check-all-notifications]')) return;
    document.querySelectorAll('#notificationBulkForm input[name="ids[]"]').forEach(function (box) {
        box.checked = e.target.checked;
    });
});
</script>
