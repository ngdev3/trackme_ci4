<?php
$badgeMap = [
    'success' => 'text-bg-success',
    'error' => 'text-bg-danger',
    'warning' => 'text-bg-warning',
    'info' => 'text-bg-info',
    'system_update' => 'text-bg-primary',
    'user_activity' => 'text-bg-secondary',
];

// Group notifications that share the same time (minute precision, matching the
// displayed granularity). Groups preserve the incoming order (unread first,
// newest id first). Notifications with different times stay in separate groups.
$groups = [];
foreach (($rows ?: []) as $r) {
    $ts  = strtotime($r['created_at']);
    $key = date('Y-m-d H:i', $ts);
    if (! isset($groups[$key])) {
        $groups[$key] = [
            'label' => date('d M Y, H:i', $ts),
            'items' => [],
        ];
    }
    $groups[$key]['items'][] = $r;
}

$statusBadge = static function (array $r): string {
    return empty($r['is_read'])
        ? '<span class="badge text-bg-primary">Unread</span>'
        : '<span class="badge text-bg-light border">Read</span>';
};
?>
<div class="cust-page">
    <section class="cust-hero">
        <div>
            <h4 class="cust-title">Notifications</h4>
            <p class="cust-subtitle">System alerts, module messages, and user activity notifications.</p>
        </div>
        <div class="cust-hero-actions">
            <span class="cust-total-tag"><i class="bi bi-bell"></i> <?= number_format(count($rows ?? [])) ?> total</span>
        </div>
    </section>

<section class="cust-panel cust-table-panel">
    <div class="cust-toolbar">
        <div>
            <h5 class="cust-table-title">Notification Records</h5>
            <p class="cust-table-note">Filter, mark read, open actions, or remove selected notifications.</p>
        </div>
    </div>
    <div class="cust-filterbar">
        <form class="row g-2 align-items-end" method="get">
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
    <div class="card-body">
        <form method="post" id="notificationBulkForm">
            <?= csrf_field() ?>
            <div class="cust-tabletools">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <label class="form-check-label small text-secondary d-flex align-items-center gap-1 me-2">
                        <input type="checkbox" class="form-check-input mt-0" data-check-all-notifications> Select all
                    </label>
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

            <?php if (empty($groups)): ?>
                <div class="cust-empty"><i class="bi bi-bell"></i>No notifications found.</div>
            <?php else: ?>
                <div class="d-flex flex-column gap-2">
                <?php foreach ($groups as $gkey => $group):
                    $items = $group['items'];
                    $count = count($items);
                    $unread = 0;
                    foreach ($items as $it) { if (empty($it['is_read'])) { $unread++; } }
                    $groupId = 'ntf-group-' . md5($gkey);
                ?>
                    <?php if ($count === 1):
                        $r = $items[0];
                    ?>
                        <div class="ntf-single border rounded p-3 <?= empty($r['is_read']) ? 'border-primary bg-primary-subtle' : '' ?>">
                            <div class="d-flex gap-2">
                                <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" name="ids[]" value="<?= esc($r['id']) ?>">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                        <div class="min-w-0">
                                            <strong><?= esc($r['title']) ?></strong>
                                            <div class="small text-secondary"><?= esc($r['message']) ?></div>
                                        </div>
                                        <div class="text-end flex-shrink-0">
                                            <div class="small text-secondary"><i class="bi bi-clock me-1"></i><?= esc($group['label']) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                        <span class="badge <?= esc($badgeMap[$r['type']] ?? 'text-bg-light') ?>"><?= esc(ucwords(str_replace('_', ' ', $r['type']))) ?></span>
                                        <span class="badge text-bg-light border"><?= esc($r['module'] ?: 'Global') ?></span>
                                        <span class="badge text-bg-<?= $r['priority'] === 'critical' ? 'danger' : ($r['priority'] === 'high' ? 'warning' : 'secondary') ?>"><?= esc($r['priority']) ?></span>
                                        <?= $statusBadge($r) ?>
                                        <span class="ms-auto d-flex gap-1">
                                            <?php if (! empty($r['action_url'])): ?>
                                                <a href="<?= esc($r['action_url']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a>
                                            <?php endif; ?>
                                            <?php if (empty($r['is_read'])): ?>
                                                <button type="submit" name="id" value="<?= esc($r['id']) ?>" formaction="<?= site_url('notifications/mark-read') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-check2"></i></button>
                                            <?php endif; ?>
                                            <button type="submit" name="id" value="<?= esc($r['id']) ?>" formaction="<?= site_url('notifications/delete') ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="ntf-group border rounded">
                            <button type="button" class="ntf-group-toggle w-100 d-flex flex-wrap align-items-center gap-2 p-3 bg-transparent border-0 text-start" data-bs-toggle="collapse" data-bs-target="#<?= $groupId ?>" aria-expanded="false" aria-controls="<?= $groupId ?>">
                                <i class="bi bi-clock text-secondary"></i>
                                <span class="fw-semibold"><?= esc($group['label']) ?></span>
                                <span class="badge text-bg-primary rounded-pill"><?= $count ?> notifications</span>
                                <?php if ($unread > 0): ?>
                                    <span class="badge text-bg-danger rounded-pill"><?= $unread ?> unread</span>
                                <?php endif; ?>
                                <span class="text-secondary text-truncate small d-none d-sm-inline flex-grow-1"><?= esc($items[0]['title']) ?></span>
                                <span class="ntf-chevron ms-auto ms-sm-0 text-secondary"><i class="bi bi-chevron-down"></i></span>
                            </button>
                            <div class="collapse" id="<?= $groupId ?>">
                                <div class="border-top">
                                <?php foreach ($items as $r): ?>
                                    <div class="ntf-item d-flex gap-2 p-3 border-bottom <?= empty($r['is_read']) ? 'bg-primary-subtle' : '' ?>">
                                        <input class="form-check-input mt-1 flex-shrink-0" type="checkbox" name="ids[]" value="<?= esc($r['id']) ?>">
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                                                <div class="min-w-0">
                                                    <strong><?= esc($r['title']) ?></strong>
                                                    <div class="small text-secondary"><?= esc($r['message']) ?></div>
                                                </div>
                                                <div class="small text-secondary flex-shrink-0"><i class="bi bi-clock me-1"></i><?= esc(date('d M Y, H:i', strtotime($r['created_at']))) ?></div>
                                            </div>
                                            <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                                                <span class="badge <?= esc($badgeMap[$r['type']] ?? 'text-bg-light') ?>"><?= esc(ucwords(str_replace('_', ' ', $r['type']))) ?></span>
                                                <span class="badge text-bg-light border"><?= esc($r['module'] ?: 'Global') ?></span>
                                                <span class="badge text-bg-<?= $r['priority'] === 'critical' ? 'danger' : ($r['priority'] === 'high' ? 'warning' : 'secondary') ?>"><?= esc($r['priority']) ?></span>
                                                <?= $statusBadge($r) ?>
                                                <span class="ms-auto d-flex gap-1">
                                                    <?php if (! empty($r['action_url'])): ?>
                                                        <a href="<?= esc($r['action_url']) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-box-arrow-up-right"></i></a>
                                                    <?php endif; ?>
                                                    <?php if (empty($r['is_read'])): ?>
                                                        <button type="submit" name="id" value="<?= esc($r['id']) ?>" formaction="<?= site_url('notifications/mark-read') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-check2"></i></button>
                                                    <?php endif; ?>
                                                    <button type="submit" name="id" value="<?= esc($r['id']) ?>" formaction="<?= site_url('notifications/delete') ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>
    <?php if (isset($pager)): ?><div class="cust-pager-bar d-flex justify-content-end"><?= $pager->links() ?></div><?php endif; ?>
</section>
</div>

<script nonce="{csp-script-nonce}">
document.addEventListener('change', function (e) {
    if (!e.target.matches('[data-check-all-notifications]')) return;
    document.querySelectorAll('#notificationBulkForm input[name="ids[]"]').forEach(function (box) {
        box.checked = e.target.checked;
    });
});

// Rotate the chevron on the grouped-notification toggles as they expand/collapse.
document.querySelectorAll('.ntf-group .collapse').forEach(function (el) {
    var toggle = document.querySelector('[data-bs-target="#' + el.id + '"]');
    if (!toggle) return;
    el.addEventListener('show.bs.collapse', function () { toggle.classList.add('is-open'); });
    el.addEventListener('hide.bs.collapse', function () { toggle.classList.remove('is-open'); });
});
</script>

<style nonce="{csp-style-nonce}">
.ntf-group-toggle { cursor: pointer; }
.ntf-group-toggle .ntf-chevron { transition: transform .2s ease; }
.ntf-group-toggle.is-open .ntf-chevron { transform: rotate(180deg); }
.ntf-item:last-child { border-bottom: 0 !important; }
.min-w-0 { min-width: 0; }
</style>
