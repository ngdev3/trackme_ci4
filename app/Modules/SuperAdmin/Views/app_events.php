<?php
/** Super Admin — app usage analytics, USER-BASED (menu/screen taps). Rendered in layout.php. */
$rows         = $rows ?? [];
$top          = $top ?? [];
$users        = $users ?? [];
$total        = $total ?? 0;
$userId       = $userId ?? 0;
$selectedUser = $selectedUser ?? null;
$maxC   = 0;
foreach ($top as $t) { $maxC = max($maxC, (int) $t['c']); }
$maxE = 0;
foreach ($users as $u) { $maxE = max($maxE, (int) $u['events']); }
$ago = static function ($ts): string {
    if (! $ts) { return ''; }
    $d = time() - strtotime((string) $ts);
    if ($d < 60)      { return 'just now'; }
    if ($d < 3600)    { return floor($d / 60) . 'm ago'; }
    if ($d < 86400)   { return floor($d / 3600) . 'h ago'; }
    if ($d < 2592000) { return floor($d / 86400) . 'd ago'; }
    return floor($d / 2592000) . ' mo ago';
};
?>
<?php if ($userId > 0): ?>
    <!-- Selected-user banner -->
    <div class="alert alert-info d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
        <div>
            <i class="bi bi-person-badge me-1"></i>
            Showing activity for
            <strong><?= esc($selectedUser['user_name'] ?? ('User #' . $userId)) ?></strong>
            <?php if (! empty($selectedUser['user_email'])): ?>
                <span class="text-secondary">&lt;<?= esc($selectedUser['user_email']) ?>&gt;</span>
            <?php endif; ?>
            <?php if ($selectedUser): ?>
                <span class="erp-pill gray ms-1"><?= number_format((int) $selectedUser['events']) ?> events</span>
                <span class="erp-pill ms-1"><?= (int) $selectedUser['menus'] ?> menus</span>
            <?php endif; ?>
        </div>
        <a href="<?= site_url('admin/app-events') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>All users</a>
    </div>
<?php endif; ?>

<div class="row g-3">
    <!-- USER-BASED summary: who used the app and how much -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-people me-1"></i> Users by Activity</h3>
                <span class="erp-pill gray"><?= count($users) ?> users · <?= number_format($total) ?> events</span>
            </div>
            <div class="card-body p-0">
                <div class="erp-tbl-wrap">
                    <table class="erp-tbl auto">
                        <thead><tr>
                            <th class="text-start">User</th>
                            <th class="text-end">Events</th>
                            <th class="text-end">Menus</th>
                            <th class="text-start">Last active</th>
                            <th></th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($users)): ?>
                            <tr><td colspan="5" class="erp-empty"><i class="bi bi-people"></i><div>No user activity recorded yet.</div></td></tr>
                        <?php else: foreach ($users as $u):
                            $pct = $maxE > 0 ? round((int) $u['events'] / $maxE * 100) : 0;
                            $isSel = $userId > 0 && (int) $u['user_id'] === $userId; ?>
                            <tr<?= $isSel ? ' class="table-active"' : '' ?>>
                                <td class="text-start">
                                    <div class="fw-semibold"><?= esc($u['user_name'] ?: ('#' . $u['user_id'])) ?></div>
                                    <?php if (! empty($u['user_email'])): ?><div class="erp-muted small"><?= esc($u['user_email']) ?></div><?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="fw-semibold"><?= number_format((int) $u['events']) ?></div>
                                    <div style="height:6px;border-radius:20px;background:#eef2f7;overflow:hidden;min-width:60px"><span style="display:block;height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#1769c2,#2f8fd6)"></span></div>
                                </td>
                                <td class="text-end"><?= (int) $u['menus'] ?></td>
                                <td class="text-start"><span class="erp-muted" title="<?= esc(date('d M Y, H:i:s', strtotime((string) $u['last_seen'])), 'attr') ?>"><?= esc($ago($u['last_seen'])) ?></span></td>
                                <td class="text-end"><a href="<?= site_url('admin/app-events?user=' . (int) $u['user_id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Top menus (scoped to the selected user when filtering) -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0"><i class="bi bi-bar-chart me-1"></i> Top Menus <small class="text-secondary">(30 days<?= $userId > 0 ? ', this user' : '' ?>)</small></h3></div>
            <div class="card-body">
                <?php if (empty($top)): ?>
                    <p class="text-secondary small mb-0">No taps recorded yet.</p>
                <?php else: foreach ($top as $t): $pct = $maxC > 0 ? round((int) $t['c'] / $maxC * 100) : 0; ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small fw-semibold"><span><?= esc($t['label']) ?></span><span class="erp-muted"><?= (int) $t['c'] ?></span></div>
                        <div style="height:8px;border-radius:20px;background:#eef2f7;overflow:hidden"><span style="display:block;height:100%;width:<?= $pct ?>%;background:linear-gradient(90deg,#1769c2,#2f8fd6)"></span></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent events (respects the selected user) -->
    <div class="col-12">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap gap-2 align-items-center justify-content-between">
                <h3 class="card-title mb-0"><i class="bi bi-activity me-1"></i> <?= $userId > 0 ? 'This User’s Menu Taps' : 'Recent Menu Taps' ?> <span class="erp-pill gray ms-1"><?= number_format($total) ?> total</span></h3>
                <?php if ($userId > 0): ?><a href="<?= site_url('admin/app-events') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg me-1"></i>Clear filter</a><?php endif; ?>
            </div>
            <div class="card-body p-0">
                <div class="erp-tbl-wrap">
                    <table class="erp-tbl auto">
                        <thead><tr>
                            <th class="text-start">When</th>
                            <?php if ($userId <= 0): ?><th class="text-start">User</th><?php endif; ?>
                            <th class="text-start">Menu</th>
                            <th class="text-start">Route</th>
                        </tr></thead>
                        <tbody>
                        <?php if (empty($rows)): ?>
                            <tr><td colspan="<?= $userId > 0 ? 3 : 4 ?>" class="erp-empty"><i class="bi bi-activity"></i><div>No usage events recorded yet.</div></td></tr>
                        <?php else: foreach ($rows as $r): ?>
                            <tr>
                                <td class="text-start"><span class="erp-muted" title="<?= esc(date('d M Y, H:i:s', strtotime((string) $r['created_at'])), 'attr') ?>"><?= esc($ago($r['created_at'])) ?></span></td>
                                <?php if ($userId <= 0): ?>
                                <td class="text-start">
                                    <a href="<?= site_url('admin/app-events?user=' . (int) $r['user_id']) ?>" class="text-decoration-none fw-semibold" title="Filter to this user"><?= esc($r['user_name'] ?: ('#' . $r['user_id'])) ?></a>
                                </td>
                                <?php endif; ?>
                                <td class="text-start"><span class="erp-pill"><?= esc($r['label'] ?: '—') ?></span></td>
                                <td class="text-start"><span class="erp-muted"><code><?= esc($r['route'] ?: '') ?></code></span></td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
